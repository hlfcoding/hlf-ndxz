<?php if (!defined('SITE')) exit('No direct script access allowed');

/**
 * Exhibit CMS Section
 * @version 1.1
 * @package Indexhibit
 * @subpackage Indexhibit CMS
 * @author Vaska 
 * @author Peng Wang <peng@pengxwang.com>
 * @todo remove views
 * @todo convention over configuration
 */
 
class Exhibits extends Router implements ICMSPageController, ICMSAjaxController
{
    public $publishing;
    public $error;
    public $error_msg;
    public $pub_status;
    public $page_id;
    
    public $settings;
    public $view_bin_path;
    
    public function __construct ($view_bin_path = null)
    {
        parent::__construct();
        
        $this->publishing = false;
        $this->error = false;
        $this->pub_status = 0;
        
        define('OBJECT', 'exhibit');
        
        $this->view_bin_path = is_null($view_bin_path) 
            ? dirname(__FILE__) . DS . self::DEFAULT_VIEW_BIN_PATH
            : $view_bin_path;
        
        // which object are we accessing?
        $this->settings = $this->db->get_site_settings();
        
        // library of $_POST options
        $submits = array('upd_view','img_upload','publish_x',
            'add_page','delete_x','publish_page','upd_ord','upd_img_ord',
            'upd_section','upd_cbox','upd_settings','upd_delete','unpublish_x',
            'del_bg_img','bg_img_upload', 'upd_jxs', 'upd_jximg', 'upd_jxdelimg',
            'upd_jxtext', 'add_sec', 'del_sec', 'edit_sec');
        
        // from $_POST to method
        $this->posted($this, $submits);
    }
    
    //---------------------------------------
    // INTERFACE METHODS
    //---------------------------------------
    
    public function load_pjs ($name, $vars = array()) 
    {
        return $this->load_template('pjs', $name, $vars, '<script type="text/javascript">%s</script>');
    }
    
    public function load_phtml ($name, $vars = array()) 
    {
        return $this->load_template('phtml', $name, $vars);
    }
    
    /**
     * @param string
     * @param string
     * @param array view model
     * @param string
     * @return string
     */
    protected function load_template ($_type, $_name, $_vars = array(), 
                                      $_wrapper = '%s') 
    {
        $_path = $this->view_bin_path . DS . "$_name.$_type";
        if (!file_exists($_path)) {
            throw new RuntimeException("no php-$_type file at $_path");
        }
        // populate template environment
        extract($_vars);
        $error = ($this->error === true) ? $this->error_msg : '';
        $lang = $this->lang;
        // run template
        ob_start();
        require $_path;
        $_contents = ob_get_contents();
        ob_end_clean();
        // process template results
        return sprintf($_wrapper, $_contents);
    }
    // temp location until an html filter object is created
    public function deserialize_html ($html) 
    {
        $html = preg_replace('/<\/?(p|br)\s?\/?>/', '', $html);
        $html = htmlspecialchars($html);
        $html = mb_decode_numericentity($html, UTF8EntConvert('1'), 'utf-8');
        $html = str_replace(array('&gt;', '&lt;'), array('>', '<'), $html);
        return $html;
    }
    
    // TODO
    public function serialize_html ($html) {
        
    }
    
    public function load_master_js () {
        $this->template->add_js(array(
            'underscore.js',
            'underscore.namespace.js',
            'backbone.js',
            'jquery.js',
            'mootools.core.js',
            'mootators.jquery.js',
            'ndxz.header.js',
            'toolman.dragdrop.js', 
            'ndxz.exhibit-edit.js'
        ));
    }
    
    //---------------------------------------
    // PAGE ACTIONS
    //---------------------------------------

    public function page_index ()
    {
        global $go, $default;
        
        $go['page'] = getURI('page', 0, 'digit', 5);

        $this->template->location = $this->lang->word('main');
        $this->template->sub_location[] =
            array($this->lang->word('settings'), "?a=$go[a]&amp;q=settings");
        $this->template->sub_location[] = array($this->lang->word('new'),
            '#', "onclick=\"toggle('add-page'); return false;\"");
        
        $this->load_master_js();
        $this->template->add_js(array(
            'iutil.js',
            'idrag.js',
            'idrop.js',
            'isortables.js',
            'jquery.inplace.js'
        ));        
        
        $this->lib_class('organize');
        $this->organize->obj_org = $this->settings['obj_org'];
        
        $this->template->add_script = $this->load_pjs('index', array( 
            // template vars 
            'action' => $go['a'],
            'special_js' => $this->template->get_special_js()
        ));
        
        $this->template->body = $this->load_phtml('index', array(
            // template vars
            'sections' => $this->db->get_sections(),
            'sortable_exhibits_grid' => $this->organize->order(),
            'current_year' => date('Y'),
            'last_year' => date('Y') + 1,
            'first_year' => $default['first_year']
        ));
        
        return;
    }
    
    public function page_settings ()
    {
        global $go, $default;
        
        load_helpers(array('output'));
        
        $this->template->location = $this->lang->word('settings');
        $this->template->sub_location[] = array($this->lang->word('main'),"?a=$go[a]");
        
        $this->load_master_js();
        
        $sections = $this->db->get_sections();
        foreach ($sections as &$s) {
            $s['edit_url'] = "?a=exhibits&q=section&id={$s['secid']}";
            $s['is_project'] = ($s['sec_proj'] == 1);
        }
        
        $this->template->body = $this->load_phtml('settings', array(
            // view model
            'name' => $this->settings['obj_name'],
            'cms_mode' => $this->settings['obj_mode'],
            'show_advanced' => ($this->settings['obj_mode'] == 1),
            'theme' => $this->settings['obj_theme'],
            'nav_method' => $this->settings['obj_org'],
            'pre_nav' => $this->deserialize_html($this->settings['obj_itop']),
            'post_nav' => $this->deserialize_html($this->settings['obj_ibot']),
            'cms_modes' => $this->db->get_cms_modes(),
            'nav_methods' => $this->db->get_site_nav_methods(),
            'themes' => $this->get_themes(),
            'sections' => $sections,
            'last_position' => $sections[count($sections) - 1]['sec_ord'],
            'form_keys' => array(
                'name' => 'obj_name',
                'cms_mode' => 'obj_mode',
                'theme' => 'obj_theme',
                'nav_method' => 'obj_org',
                'pre_nav' => 'obj_itop',
                'post_nav' => 'obj_ibot',
                'section_name' => 'sec_desc',
                'section_folder' => 'section',
                'last_position' => 'hsec_ord'
            )
        ));
        
        return;
    }
    
    /**
     * @todo global `ide` -> `id`
     * @return void
     */
    public function page_edit ()
    {
        global $go, $default;

        load_helpers(array('output'));

        $this->template->location = $this->lang->word('edit');
        $this->template->sub_location[] = array($this->lang->word('main'), "?a=$go[a]");            
        $this->template->add_css('plugin.css');

        $this->load_master_js();
        $this->template->add_js(array(
            'jquery.inplace.js', 
            'alexking.quicktags.js'
        ));
        if ($default['color_picker'] === true) {
            $this->template->add_js('plugin.js');
        }
        $this->template->add_js_globals(array(
            'action' => $go['a'],
            'ide' => (int)$go['id']
        ), true); 

        $exhibit = $this->db->get_exhibit_by_id($go['id']);
        $exhibit_images = $this->db->get_exhibit_images_by_id($go['id']);
        foreach ($exhibit_images as &$i) {
            $i['media_file_path'] = BASEURL . GIMGS . "/sys-{$i['media_file']}";
        }
        
        $this->template->body = $this->load_phtml('edit', array(
            'action' => $go['a'],
            'id' => $go['id'],
            'show_advanced' => ($this->settings['obj_mode'] == 1),
            'show_default_picker' => ($default['color_picker'] === true),
            'order' => $exhibit['ord'],
            'section_id' => $exhibit['section_id'],
            'section_name' => $exhibit['sec_desc'],
            'title' => $exhibit['title'],
            'status' => $exhibit['status'],
            'format' => $exhibit['format'],
            'thumb_size' => $exhibit['thumbs'],
            'image_size' => $exhibit['images'],
            'bg_tiling' => $exhibit['tiling'],
            'process_html' => $exhibit['process'],
            'hide_page' => $exhibit['hidden'],
            'content' => $this->deserialize_html($exhibit['content']),
            'bg_color' => (empty($exhibit['color']) ? 'ffffff' : $exhibit['color']),
            'user_mode' => $this->access->prefs['user_mode'],
            'statuses' => $this->db->get_exhibit_statuses(), 
            'formats' => $this->get_plugins(),
            'thumb_sizes' => $default['thumbsize'],
            'image_sizes' => $default['imagesize'],
            'on_off' => $this->db->get_on_off(),
            'exhibit_images_list' => $this->load_phtml('images', array(
                'images' => $exhibit_images
            )),
            'form_keys' => array(
                'format' => 'obj_present',
                'hidden_order' => 'hsec_ord',
                'hidden_section_id' => 'hsection_id'
            )
        ));
        
        return;
    }

    public function page_section()
    {
        global $go, $default;
        
        load_helpers(array('output'));
        
        $this->template->location = $this->lang->word('section');
        $this->template->sub_location[] = array($this->lang->word('settings'),"?a=$go[a]&q=settings");
        $this->template->sub_location[] = array($this->lang->word('main'),"?a=$go[a]");
        
        $section = $this->db->get_section_by_id($go['id']);
        $sections = $this->db->get_sections();
        $orders = array();
        foreach ($sections as &$s) {
            $orders[] = $s['sec_ord'];
        }
        
        $this->template->body = $this->load_phtml('section', array(
            'is_root' => ($section['secid'] == 1),
            'path' => BASEURL . $section['sec_path'],
            'name' => $section['sec_desc'],
            'folder' => $section['section'],
            'order' => $section['sec_ord'],
            'id' => $section['secid'],
            'show_projects' => $section['sec_proj'],
            'show_title' => $section['sec_disp'],
            'orders' => $orders,
            'on_off' => $this->db->get_on_off(),
            'form_keys' => array(
                'name' => 'sec_desc',
                'folder' => 'section',
                'order' => 'sec_ord',
                'show_projects' => 'sec_proj',
                'show_title' => 'sec_disp',
                'delete' => 'del_sec',
                'update' => 'edit_sec',
                'hidden_id' => 'hsecid',
                'hidden_id' => 'hsec_ord'
            )
        ));
        
        return;
    }   
        
    function page_view()
    {
        global $go;

        // the record
        $rs = $this->db->fetchRecord("SELECT * 
            FROM ".PX."media 
            WHERE media_id = '{$go['id']}' 
            AND media_obj_type = '".OBJECT."'");
        
        // ++++++++++++++++++++++++++++++++++++++++++++++++++++
        
        $body = "<div style='width:125px; float:left;'><img src='" . BASEURL . GIMGS . "/th-$rs[media_file]' width='100' /><br /><br /><a href='" . BASEURL . GIMGS . "/$rs[media_file]' target='_new'>" . $this->lang->word('view full size') . "</a></div>\n";
        
        $body .= "\n";
        $body .= "<div style='width:495px; float:left;'>\n";
        $body .= ips($this->lang->word('image title'), 'input', 'media_title', 
            $rs['media_title'], "id='media_title' maxlength='35'", 'text');
        $body .= ips($this->lang->word('image caption'), 'input', 'media_caption', 
            $rs['media_caption'], "id='media_caption' maxlength='35'", 'text');
            
        // buttons
        $body .= "<input type='button' value='" . $this->lang->word('cancel') . "' onclick=\"getExhibit(); return false;\" />\n";
        $body .= "<input type='button' value='" . $this->lang->word('delete') . "' onclick=\"deleteImage('$rs[media_file]'); return false;\" />\n";
        $body .= "<input type='button' value='" . $this->lang->word('update') . "' onclick=\"updateImage($rs[media_id]); return false;\" />\n";
        $body .= "</div>\n";
        
        $body .= "<div class='cl'><!-- --></div>\n";

        header ('Content-type: text/html; charset=utf-8');
        echo $body;
        exit;
    }
    
    function page_prv()
    {
        global $go;

        $this->template->location = $this->lang->word('preview');
        
        // sub-locations
        $this->template->sub_location[] = array($this->lang->word('main'), "?a=$go[a]");
        
        // the record
        $rs = $this->db->fetchRecord("SELECT title, sec_desc  
            FROM ".PX."objects, ".PX."sections  
            WHERE id = '{$go['id']}' 
            AND object = '".OBJECT."'
            AND section_id = secid");
        
        // ++++++++++++++++++++++++++++++++++++++++++++++++++++
        
        $title_area = div(div("<h3><span class='sec-title'>$rs[sec_desc]</span> $rs[title]</h3><br />\n","class='col'").
            div(href($this->lang->word('edit'), "?a=$go[a]&amp;q=edit&amp;id={$go['id']}"), "class='col txt-right'").
            "<div class='cl'><!-- --></div>","class='c2'");
        
        $body = div($title_area.
            "<iframe class='prv-text' src='?a=system&amp;q=prv&amp;id={$go['id']}'></iframe>\n".
            "<div class='cl'><!-- --></div>","class='c1 bg-grey'");
        
        
        $this->template->body = $body;
    }
    
    function page_jximg()
    {
        global $go;
        load_module_helper('files', $go['a']);
        $exhibit_images = $this->db->get_exhibit_images_by_id($go['id']);
        foreach ($exhibit_images as &$i) {
            $i['media_file_path'] = BASEURL . GIMGS . "/sys-{$i['media_file']}";
        }        
        header ('Content-type: text/html; charset=utf-8');
        echo $this->load_phtml('images', array(
            'images' => $exhibit_images
        ));
        exit;
    }
    
    function page_jxload()
    {
        global $go, $default;
        
        load_module_helper('files', $go['a']);
        
        if (isset($_POST['jxload']))
        {
            // perform the upload
            $this->sbmt_img_upload();
            
            $more = "<script type='text/javascript'>
            $(document).ready(function(){   
                parent.getExhibit();
            });
            </script>\n";
        }
        
        $more = (!isset($more)) ? '' : $more;
        
        $this->load_master_js();
        $this->template->add_js('jquery.multifile.js');

        $script = "<style type='text/css'>
        #uploader input { font-size: 9px; }
        #files_list div, #files_list input { margin: 0 0 1px 0; padding: 0; }
        </style>
        $more";

        $this->template->add_script = $script;
        
        $body = "<div style='text-align:left;' id='uploader'>\n";
        $body .= "<form enctype='multipart/form-data' action='?a=$go[a]&q=jxload&id={$go['id']}' method='post'>\n";
        
        $body .= "<div style='float:left; width:200px;'>\n";
        $body .= "<input id='my_file_element' type='file' name='filename[]' >\n";
        $body .= "<input type='submit' name='jxload' value='" . $this->lang->word('upload') . "'>\n";
        $body .= "</form>\n";
        $body .= p('<strong>' . $this->lang->word('filetypes') . ':</strong> ' . $this->lang->word('allowed formats') . br() . '<strong>' . $this->lang->word('max file size') . ':</strong> ' . getLimit(), "class='red'");
        $body .= "</div>\n";
        
        $body .= "<div style='float:left; width:400px; text-align:right;'>\n";
        $body .= "<div id='files_list'></div>\n";
        $body .= "</div>\n";
        
        $body .= "<div class='cl'><!-- --></div>\n";
        $body .= "</div>\n";
        
        $body .= "<script>\n";
        // this tells us how many we can upload at a time
        $body .= "var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), " . $default['exhibit_imgs'] . " );\n";
        $body .= "multi_selector.addElement( document.getElementById( 'my_file_element' ) );\n";
        $body .= "</script>\n";
        
        $this->template->body = $body;
        
        $this->template->output('iframe');
        exit;
    }
    
    function page_jxbg()
    {
        global $go;
        
        if (isset($_POST['upload']))
        {
            if (isset($_POST['deletion']))
            {
                load_module_helper('files', $go['a']);
                $clean['bgimg'] = '';
                $this->db->updateArray('object', $clean, "id='{{$go['id']}}'");
                
                $filename = $_POST['filename'];

                // we need to delete the picture too...
                if (file_exists(DIRNAME . '/files/' . $filename))
                {
                    unlink(DIRNAME . '/files/' . $filename);
                }
            }
            else
            {
                // perform the upload
                $this->sbmt_bg_img_upload();
            }
        }

        
        $this->template->add_js('jquery.js');

        $script = "<style type='text/css'>
        body { text-align: left; }
        </style>
        
        <script type='text/javascript'>
        $(document).ready(function()
        {
            $('#iform').change( function() { 
                $('#iform')[0].submit();
                parent.updating(\"<span class='notify'>" . $this->lang->word('updating') . "</span>\");
            });

            $('#iform #delete').click( function() { 
                $('#iform')[0].submit();
                parent.updating(\"<span class='notify'>" . $this->lang->word('updating') . "</span>\");
            });
        });
        </script>";

        $this->template->add_script = $script;
        
        // the record
        $rs = $this->db->fetchRecord("SELECT bgimg  
            FROM ".PX."objects   
            WHERE id = '{$go['id']}'");
            
        if ($rs['bgimg'] !== '')
        {
            $body = "<form action='?a=$go[a]&q=jxbg&id={$go['id']}' method='post' name='iform' id='iform'>\n";      
            $body .= "<div>\n";
            $body .= "<a href='" . BASEURL . BASEFILES . "/$rs[bgimg]' target='_new'><img src='" . BASEURL . BASEFILES . "/$rs[bgimg]' width='25' style='padding-top:2px;' valign='center' border='0' /></a>";
            $body .= " <input type='button' name='delete' id='delete' value='" . $this->lang->word('delete') . "' style='padding-top:0;' />\n";
            $body .= "<input type='hidden' name='upload' value='1' />\n";
            $body .= "<input type='hidden' name='deletion' value='1' />\n";
            $body .= "<input type='hidden' name='filename' value='$rs[bgimg]' />\n";
            $body .= "</div>\n";
            $body .= "</form>\n";
        }
        else
        {
            $body = "<form enctype='multipart/form-data' action='?a=$go[a]&q=jxbg&id={$go['id']}' method='post' name='iform' id='iform'>\n";        
            $body .= "<div>\n";
            $body .= "<input type='file' id='jxbg' name='jxbg' />\n";
            $body .= "<input type='hidden' name='upload' value='1' />\n";
            $body .= "</div>\n";
            $body .= "</form>\n";
        }   
        
        $this->template->body = $body;
        
        $this->template->output('iframe');
        exit;
    }
    
    function publisher()
    {
        ($this->pub_status == 1) ? $this->sbmt_publish_x() : $this->sbmt_unpublish_x();
    }

    //---------------------------------------
    // AJAX ACTIONS
    //---------------------------------------
    
    // we need a way to protect these page from outside access
    function sbmt_add_page()
    {
        $OBJ->template->errors = true;
        global $go;
        
        // can we do this better?
        $processor =& load_class('processor', true, 'lib');
    
        $clean['title'] = $processor->process('title',array('notags', 'reqNotEmpty'));
        $clean['section_id'] = $processor->process('section_id',array('notags', 'reqNotEmpty'));
        $clean['year'] = $processor->process('year',array('notags' ,'reqNotEmpty'));

        if ($processor->check_errors())
        {
            // get our error messages
            $error_msg = $processor->get_errors();
            $this->errors = true;
            $GLOBALS['error_msg'] = $error_msg;
            $this->template->special_js = "toggle('add-page');";
            return;
        }
        else
        {
            // we need to deal with the order of things...
            $this->db->updateArray('object', 
                array('ord' => 'ord + 1'), 
                "section_id = {$clean['section_id']}");
            
            // a few more things
            $clean['udate']     = getNow();
            $clean['object']    = OBJECT;
            $clean['ord']       = 1;
            $clean['creator']   = $this->access->prefs['ID'];
            
            $last = $this->db->insertArray('object', $clean);
            
            system_redirect("?a=$go[a]&q=edit&id=$last");
        }
        
        return;
    }
    
    
    // we need a way to protect these page from outside access
    function sbmt_add_sec()
    {
        $OBJ->template->errors = true;
        global $go;
        
        // can we do this better?
        $processor =& load_class('processor', true, 'lib');
    
        $clean['sec_desc'] = $processor->process('sec_desc',array('notags','reqNotEmpty'));
        $clean['section'] = $processor->process('section',array('notags','reqNotEmpty'));
        $temp['hsec_ord'] = $processor->process('hsec_ord',array('digit'));

        if ($processor->check_errors())
        {
            // get our error messages
            $error_msg = $processor->get_errors();
            $this->errors = true;
            $GLOBALS['error_msg'] = $error_msg;
            $this->template->special_js = "toggle('add-sec');";
            return;
        }
        else
        {
            // a few more things
            $clean['sec_date']  = getNow();
            $clean['sec_ord']   = $temp['hsec_ord'] + 1;
            
            // we need to romanize the path based upon 'section'
            load_helpers( array('output', 'romanize') );
            $folder_name = load_class('publish', true, 'lib');
            $folder_name->title = trim($clean['section']);
            $clean['section'] = $folder_name->processTitle();
            $clean['sec_path'] = '/' . $clean['section'];
            
            $last = $this->db->insertArray('section', $clean);
            
            system_redirect("?a=$go[a]&q=section&id=$last");
        }
        
        return;
    }
    
    
    function sbmt_edit_sec()
    {
        global $go;
        
        $processor =& load_class('processor', true, 'lib');
        
        $temp['hsec_ord'] = $processor->process('hsec_ord',array('digit'));
        $temp['hsecid'] = $processor->process('hsecid',array('digit'));
    
        $clean['sec_desc'] = $processor->process('sec_desc',array('notags', 'reqnotempty'));
        $clean['section'] = $processor->process('section',array('nophp', 'reqnotempty'));
        $clean['sec_proj'] = $processor->process('sec_proj',array('boolean'));
        $clean['sec_report'] = $processor->process('sec_report',array('boolean'));
        $clean['sec_disp'] = $processor->process('sec_disp',array('boolean'));
        $clean['sec_ord'] = $processor->process('sec_ord',array('digit'));


        if ($processor->check_errors())
        {
            // get our error messages
            $error_msg = $processor->get_errors();
            $this->errors = true;
            $GLOBALS['error_msg'] = $error_msg;
            return;
        }
        else
        {
            if ($clean['sec_proj'] === 1) {
                // update all sections with sec_proj = 0
                $this->db->updateArray('section', array('sec_proj' => 0));
            }
            
            // so nice and messy!
            if ($clean['sec_ord'] !== $temp['hsec_ord']) {
                // we need to reorder things
                if ($clean['sec_ord'] > $temp['hsec_ord']) {
                    $this->db->updateArray('section', array('sec_ord' => 'sec_ord - 1'),
                        "(sec_ord > '{$temp['hsec_ord']}') AND (sec_ord <= '{$clean['sec_ord']}')");
                } elseif ($clean['sec_ord'] < $temp['hsec_ord']) {
                    $this->db->updateArray('section', array('sec_ord' => 'sec_ord + 1'),
                        "(sec_ord < '{$temp['hsec_ord']}') AND (sec_ord >= '{$clean['sec_ord']}')");
                } else { 
                    // do nothing here 
                }
            }

            // we need to romanize the path based upon 'section'
            load_helpers( array('output', 'romanize') );
            $folder_name = load_class('publish', true, 'lib');
            $folder_name->title = trim($clean['section']);
            $clean['section'] = $folder_name->processTitle();

            if ($go['id'] !== 1)
            {
                // you can update the sec_path
                $clean['sec_path'] = '/' . $clean['section'];
            }
            
            
            $this->db->updateArray('section', $clean, "secid='{$go['id']}'"); 
            
            // send an update notice
            $this->template->action_update = 'updated';
        }
    }
    
    
    function sbmt_del_sec()
    {
        global $go;
        
        $processor =& load_class('processor', true, 'lib');
        
        $temp['hsec_ord'] = $processor->process('hsec_ord',array('digit'));
        
        // delete section
        $this->db->deleteArray('section', "secid = {$go['id']}");
        
        // delete pages
        $this->db->deleteArray('object', "section_id = {$go['id']}");
        
        // so nice and messy!
        $this->db->updateArray('section', array('sec_ord' => 'sec_ord - 1'), 
            "(sec_ord > {$temp['hsec_ord']})");
        
        system_redirect("?a=$go[a]&q=settings");
    }
    
    
    function sbmt_publish_x()
    {
        global $default;
        
        $this->publishing = true;
        
        // get record
        $rs = $this->db->fetchRecord("SELECT id, title, secid, sec_path, status, report, 
            obj_apikey, obj_email, sec_report   
            FROM ".PX."objects, ".PX."objects_prefs, ".PX."sections 
            WHERE id = '".$this->page_id."' 
            AND object = '".OBJECT."' 
            AND obj_ref_type = object 
            AND section_id = secid");
        
        // not again
        if ($rs['status'] == 1) return;
            
        load_helper('output');
        load_helper('romanize');
        $URL =& load_class('publish', true, 'lib');

        // make the url
        $URL->title = $rs['title'];
        $URL->section = $rs['sec_path'];
        $check_url = $URL->makeURL();
        
        // check for dupe
        $check = $this->db->selectArray('object', array('url' => $check_url), Db::FETCH_RECORD, 'id');
        
        // if dupe alert
        if ($check)
        {
            // let's just append things
            $previous = count($check);
            $previous = $previous + 1 . '/';
        }
        else
        {
            $previous = '';
        }
        
        $clean['url']       = $check_url . $previous;
        
        // need to update table
        $clean['status']    = 1;
        $clean['udate']     = getNow();
        $clean['pdate']     = getNow();
        $clean['url']       = $clean['url'];
        $clean['object']    = OBJECT;

        $this->db->updateArray('object', $clean, "id={$this->page_id}");

    }
    
    
    function sbmt_unpublish_x()
    {
        // need to update table
        $clean['status']    = 2;
        $clean['udate']     = getNow();
        $clean['pdate']     = '0000-00-00 00:00:00';
        $clean['url']       = '';

        $this->db->updateArray('object', $clean, "id='".$this->page_id."'");
    }
    
    
    function sbmt_delete_x()
    {
        global $go;
        
        if ($go['id'] == 1) 
        {
            system_redirect("?a=$go[a]"); // this can not be deleted
            return;
        }
        
        $processor =& load_class('processor', true, 'lib');
    
        $clean['hsection_id'] = $processor->process('hsection_id',array('notags','digit'));
        $clean['hord'] = $processor->process('hord',array('notags','digit'));
        
        $this->db->deleteArray('object', "id='{$go['id']}'");
        
        // we need to deal with the order of things...
        $this->db->updateArray('object', 
            array('ord' => 'ord - 1'), 
            "section_id = {$clean['hsection_id']} AND ord >= " . $this->db->escape($clean['hord'])."");
        
        system_redirect("?a=$go[a]");       
    }
    
    
    function sbmt_upd_delete()
    {
        global $go;
        
        $file = $this->db->fetchRecord("SELECT media_id,media_ref_id,media_file 
            FROM ".PX."media 
            WHERE media_id='{$go['id']}'");
        
        if ($file)
        {
            if (file_exists(DIRNAME . GIMGS . '/' . $file['media_file']))
            {
                unlink(DIRNAME . GIMGS . '/' . $file['media_file']);
                $this->db->deleteArray('media', "media_id='$file[media_id]'");
            }
        }
        
        system_redirect("?a=$go[a]&q=edit&id=$file[media_ref_id]");     
    }
    
    
    function sbmt_upd_view()
    {
        global $go;
        
        $processor =& load_class('processor', true, 'lib');
    
        $clean['media_title'] = $processor->process('media_title', array('notags'));
        $clean['media_caption'] = $processor->process('media_caption', array('nophp'));


        if ($processor->check_errors())
        {
            // get our error messages
            $error_msg = $processor->get_errors();
            $this->errors = true;
            $GLOBALS['error_msg'] = $error_msg;
            return;
        }
        else
        {
            $clean['media_udate'] = getNow();

            $this->db->updateArray('media', $clean, "media_id='{$go['id']}'"); 
            
            system_redirect("?a=$go[a]&q=view&id={$go['id']}");
        }
    }
    
    /**
     * @todo update year logic
     */
    function sbmt_upd_ord()
    {
        $vars = explode('&', $_POST['name']);

        foreach ($vars as $next) {
            $var[] = explode('=', $next);
        }

        foreach ($var as $out) {
            // perhaps this preg can be better...
            $out[0] = preg_replace('/[^[:digit:]]/', '', $out[0]);
            $out[1] = preg_replace('/[^[:digit:]]/', '', $out[1]);
            $blah[$out[0]][] = $out[1];
        }
        
        foreach ($blah as $key => $do) {
            $i = 1;
            foreach ($do as $it) {
                // it must be a year
                // unless you have 1001 or more pages
                $params = array('ord' => $i);
                if (strlen($key) > 4) {
                    // get the year - it's at the end
                    $params['year'] = substr($key, -4);
                    // get the section_id...everything but the year
                    $params['section_id'] = preg_replace("/{$params['year']}$/", '', $key);
                } else {
                    // no year
                    // need the section id
                    $params['section_id'] = $key;
                }
                $this->db->updateArray('object', $params, "id = $it");
                $i++;
            }
        }
        
        // make this better later
        header ('Content-type: text/html; charset=utf-8');
        echo "<span class='notify'>".$this->lang->word('updated')."</span>";
        exit;
    }
    
    
    function sbmt_upd_cbox()
    {
        // make a boolean validator
        $clean['sec_disp'] = $_POST['checked'];
        $cleaned['secid'] = str_replace('b', '', $_POST['element_id']);
        
        $this->db->updateArray('section', $clean, "secid = {$cleaned['secid']}");
        
        if ($clean['sec_disp'] == 1) {
            header ('Content-type: text/html; charset=utf-8');
            echo input('boxy', 'checkbox', "checked='checked' id='b$cleaned[secid]'", 1);
        } else {
            header ('Content-type: text/html; charset=utf-8');
            echo input('boxy', 'checkbox', "id='b$cleaned[secid]'", 0);
        }

        exit;
    }
    
    
    function sbmt_upd_section()
    {
        if ($_POST['update_value'] === '') { 
            echo 'Error'; exit; 
        }
        
        $clean['sec_desc'] = $_POST['update_value'];
        $clean['secid'] = str_replace('s', '', $_POST['element_id']);
        
        $this->db->updateArray('section', $clean, "secid=$clean[secid]");
        
        // back to our page
        header ('Content-type: text/html; charset=utf-8');
        echo $clean['sec_desc'];
        exit;
    }
    

    
    function sbmt_bg_img_upload()
    {
        global $go, $uploads;
        $dir = DIRNAME . BASEFILES . '/';
        $types = $uploads['images'];
        
        $IMG =& load_class('media', true, 'lib');
        
        $thetype = explode('.', strtolower($_FILES['jxbg']['name']));
        $thetype = array_pop($thetype);
        
        $name = $go['id'] . '_background' . '.' . $thetype;
        
        if (in_array($thetype, $types)) {
            if ($_FILES['jxbg']['size'] < $IMG->upload_max_size) {
                // if uploaded we can work with it
                if (move_uploaded_file($_FILES['jxbg']['tmp_name'], $dir . '/' . $name)) {
                    $clean['bgimg']     = $name;
                    $this->db->updateArray('object', $clean, "id='{$go['id']}'");
                    @chmod($dir . '/' . $name, 0755);
                    return;
                } else {
                    // error on upload
                }
            } else {
                // too big
            }
        }
    }

    
    
    function sbmt_upd_img_ord()
    {
        // make this more safe
        $vars = explode(',', $_POST['order']);

        foreach ($vars as $out) {
            $out = preg_replace('/[^[:digit:]]/', '', $out);
            $order[] = $out;
        }
        
        if (is_array($order)) {
            $i = 1;
            foreach ($order as $do) {
                $this->db->updateArray('media', array('media_order' => $i), "media_id = $do");
                $i++;
            }
        }
        
        // make this better later
        header ('Content-type: text/html; charset=utf-8');
        echo "<span class='notify'>".$this->lang->word('updated')."</span>";
        exit;
    }
    
    
    function sbmt_upd_settings()
    {
        global $go, $default;
        
        $processor =& load_class('processor', true, 'lib');
        load_helper('textprocess');
    
        $clean['obj_name'] = $processor->process('obj_name',array('notags','reqNotEmpty'));
        $clean['obj_itop'] = $processor->process('obj_itop',array('nophp'));
        $clean['obj_ibot'] = $processor->process('obj_ibot',array('nophp'));
        $clean['obj_theme'] = $processor->process('obj_theme', array('notags'));
        $clean['obj_org']   = $processor->process('obj_org', array('notags'));
        $clean['obj_mode']  = $processor->process('obj_mode', array('notags', 'boolean'));
        //$user['writing']  = $processor->process('writing', array('digit'));
        
        // defaults!
        $clean['obj_org'] = ($clean['obj_mode'] == 1) ? $clean['obj_org'] : 1;
        
        $theme = ($clean['obj_theme'] === '') ? 'eatock' : $clean['obj_theme'];
        $clean['obj_theme'] = ($clean['obj_mode'] == 1) ? $theme : 'eatock';
        
        // process the text...
        $clean['obj_itop'] = textProcess($clean['obj_itop'], 1);
        $clean['obj_ibot'] = textProcess($clean['obj_ibot'], 1);


        if ($processor->check_errors()) {
            // get our error messages
            $error_msg = $processor->get_errors();
            $this->errors = true;
            $GLOBALS['error_msg'] = $error_msg;
            return;
        } else {
            // redundant...but we need it.
            $user['user_mode'] = $clean['obj_mode'];
            if ($user['user_mode'] !== 1) {
                // language?
                // but what if this file was deleted?
                $clean['obj_itop'] = "<p><%obj_name%><br />
<a href=\'<%baseurl%><plug:ndxz_rewriter url=\'/about-this-site/\' />\'>" . $this->lang->word('about this site') . "</a></p>";
            } else {
                if ($clean['obj_itop'] === '') {
                    $clean['obj_itop'] = "<p><%obj_name%><br />
<a href=\'<%baseurl%><plug:ndxz_rewriter url=\'/about-this-site/\' />\'>" . $this->lang->word('about this site') . "</a></p>";
                }
            }
            
            $this->db->updateArray('objects_meta', $clean, "obj_ref_type='".OBJECT."'");
            $this->db->updateArray('user', $user, "ID={$this->access->prefs['ID']}");
            
            // send an update notice
            $this->template->action_update = 'updated';
        }       
    }
    
    
    // only images, nothing fancy here...
    function sbmt_img_upload()
    {
        global $go, $uploads, $default;
        
        $OBJ->template->errors = true;
        
        load_module_helper('files', $go['a']);
        $IMG =& load_class('media', true, 'lib');
        
        // we'll query for all our defaults first...
        $rs = $this->db->fetchRecord("SELECT thumbs, images  
            FROM ".PX."objects    
            WHERE id = '{$go['id']}' 
            AND object = '".OBJECT."'");
            
            
        // we need to get these from some defaults someplace
        $IMG->thumbsize = ($rs['thumbs'] !== '') ? $rs['thumbs'] : 200;
        $IMG->maxsize = ($rs['images'] !== '') ? $rs['images'] : 9999;
        $IMG->quality = $default['img_quality'];
        $IMG->makethumb = true;
        $IMG->path = DIRNAME . GIMGS . '/';

        load_helper('output');
        $URL =& load_class('publish', true, 'lib');
            
        // +++++++++++++++++++++++++++++++++++++++++++++++++++
        
        // oh so messy
        // our input array is a mess - clean out empty elements
        $_FILES['filename']['name'] = array_diff($_FILES['filename']['name'], array(""));
        $_FILES['filename']['tmp_name'] = array_diff($_FILES['filename']['tmp_name'], array(""));
        $_FILES['filename']['size'] = array_diff($_FILES['filename']['size'], array(""));
        
        // rewrite arrays
        foreach ($_FILES['filename']['tmp_name'] as $key => $file)
        {
            $new_images[] = array('temp'=>$file, 'name'=>$_FILES['filename']['name'][$key],
                'size'=>$_FILES['filename']['size'][$key]);
        }
        
        if (empty($new_images)) {
            return;
        }
        
        // reverse the array
        rsort($new_images);


        $x = 0;
        $added_x = array();
        
        foreach ($new_images as $key => $image)
        {
            if ($image['size'] < $IMG->upload_max_size)
            {
                $test = explode('.', strtolower($image['name']));
                $thetype = array_pop($test);
                
                $URL->title = implode('_', $test);
                $new_title = $URL->processTitle();
            
                $IMG->type = '.' . $thetype;
                $IMG->filename = $IMG->checkName($go['id'] . '_' . $new_title) . '.' . $thetype;
            
                if (in_array($thetype, $uploads['images']))
                {
                    // if uploaded we can work with it
                    if (move_uploaded_file($image['temp'], 
                        $IMG->path . '/' . $IMG->filename)) 
                    {
                        $x++;
                    
                        $IMG->image = $IMG->path . '/' . $IMG->filename;
                        $IMG->uploader();

                        $clean['media_id'] = 'null';
                        $clean['media_order'] = $x;
                        $clean['media_ref_id'] = $go['id'];
                        $clean['media_file'] = $IMG->filename;
                        $clean['media_mime'] = $thetype;
                        $clean['media_obj_type'] = OBJECT;
                        $clean['media_x'] = $IMG->out_size['x'];
                        $clean['media_y'] = $IMG->out_size['y'];
                        $clean['media_kb'] = $IMG->file_size;

                        $added_x[$x] = $this->db->insertArray(PX.'media', $clean);
                        
                        @chmod($IMG->path . '/' . $IMG->filename, 0755);
                    }
                    else
                    {
                        // file not uploaded
                    }
                }
                else
                {
                    // need to report back if things don't work
                    // not a valid format
                }
            }
            else
            {
                // nothing, it's too big
            }
        }

        // update the order of things
        if ($x > 0)
        {
            $this->db->updateRecord("UPDATE ".PX."media SET
                media_order = media_order + $x 
                WHERE 
                (media_id NOT IN (" .implode(',', $added_x). ")) 
                AND media_ref_id = '{$go['id']}'");
        }
    }
    
    
    function sbmt_upd_jximg()
    {
        global $go;
                
        load_module_helper('files', $go['a']);
        
        header ('Content-type: text/html; charset=utf-8');
        
        $clean['media_id'] = (int) $_POST['id'];
        $clean['media_title'] = ($_POST['v'] === '') ? '' : utf8Urldecode($_POST['v']);
        $clean['media_caption'] = ($_POST['x'] === '') ? '' : utf8Urldecode($_POST['x']);
        
        $this->db->updateArray('media', $clean, "media_id={$clean[media_id]}");
        
        header ('Content-type: text/html; charset=utf-8');
        echo "<span class='notify'>" . $this->lang->word('updating') . "</span>";
        exit;
    }


    function sbmt_upd_jxtext()
    {
        global $go;
        
        header ('Content-type: text/html; charset=utf-8');
        
        load_module_helper('files', $go['a']);
        
        $clean['id'] = (int) $_POST['id'];
        $_POST['content'] = ($_POST['v'] === '') ? '' : utf8Urldecode($_POST['v']);
        
        //$_POST['content'] = ($_POST['v'] === '') ? '' : $_POST['v'];
        
        //echo $_POST['content']; exit;
        
        // we need $clean['id'] on processing
        $rs = $this->db->fetchRecord("SELECT process  
            FROM ".PX."objects    
            WHERE id = '$clean[id]'");
        
        $processor =& load_class('processor', true, 'lib');
        load_helper('textprocess');
        
        $clean['content'] = $processor->process('content', array('nophp'));
        $clean['content'] = textProcess($clean['content'], $rs['process']);

        $clean['udate']     = getNow();
        $clean['object']    = OBJECT;

        $this->db->updateArray('object', $clean, "id={$clean['id']}");
        
        header ('Content-type: text/html; charset=utf-8');
        echo "<span class='notify'>" . $this->lang->word('updating') . "</span>";
        exit;
    }
    
    function sbmt_upd_jxdelimg()
    {
        global $go;
        
        load_module_helper('files', $go['a']);
        
        // id here really is the name of the file
        $clean['media_id'] = $_POST['id'];
        
        $this->db->deleteArray('media', "media_file='{$clean[media_id]}'");
        
        deleteImage($clean['media_id']); // image
        deleteImage($clean['media_id'], 'th'); // thumbnail
        deleteImage($clean['media_id'], 'sys'); // system thumbnail
        
        header ('Content-type: text/html; charset=utf-8');
        echo "<span class='notify'>" . $this->lang->word('updating') . "</span>";
        exit;
    }
    
    
    function sbmt_upd_jxs()
    {
        $clean['id'] = (int) $_POST['id'];

        switch ($_POST['x']) {
        case 'ajx-status':
            if ($clean['id'] == 1) break;
            $clean['status'] = (int) $_POST['v'];
            $this->pub_status = $clean['status'];
            $this->page_id = $clean['id'];
            $this->publisher();
            break;
        case 'ajx-images':
            $clean['images'] = (int) $_POST['v'];
            break;
        case 'ajx-thumbs':
            $clean['thumbs'] = (int) $_POST['v'];
            break;
        case 'ajx-process':
            $clean['process'] = (int) $_POST['v'];
            break;
        case 'ajx-hidden':
            $clean['hidden'] = (int) $_POST['v'];
            break;
        case 'ajx-tiling':
            $clean['tiling'] = (int) $_POST['v'];
            break;
        case 'color':
            $clean['color'] = $_POST['v'];
            break;
        case 'year':
            $clean['year'] = $_POST['v'];
            break;
        case 'present':
            $clean['format'] = $_POST['v'];
            break;
        case 'break':
            $clean['break'] = (int) $_POST['v'];
            break;
        case 'title':
            if ($_POST['update_value'] === '') { echo 'Error'; exit; }
            $clean['title'] = $_POST['update_value'];
            $this->db->updateArray('object', $clean, "id={$clean['id']}");
            
            header ('Content-type: text/html; charset=utf-8');
            echo $clean['title'];
            exit;
            break;
        }
        
        if ($clean['id'] > 0) $this->db->updateArray('object', $clean, "id='{$clean['id']}'");
        
        header ('Content-type: text/html; charset=utf-8');
        echo "<span class='notify'>" . $this->lang->word('updating') . "</span>";
        exit;
    }
    
    protected function get_themes () 
    {
        $path = DIRNAME . BASENAME . DS . SITEPATH;
        $themes = array();
        if ($fp = opendir($path)) {
            while (($t = readdir($fp)) !== false) {
                if (preg_match('/^(_|CVS$)/i', $t) === 0 &&
                    preg_match('/\.(php|html)$/i', $t) === 0 &&
                    preg_match('/\.(|DS_Store|svn|git|backup)$/i', $t) === 0 &&
                    preg_match('/plugin|css|js|img/', $t) === 0) {
                    $themes[] = $t;
                }
            } 
        }
        closedir($fp);
        return $themes;
    }
    
    protected function get_plugins ()
    {
        $path = DIRNAME . BASENAME . DS . PLUGPATH;
        if ($this->settings['obj_mode'] == 1) {
            $plugins = array();
            if (is_dir($path)) {
                if ($fp = opendir($path)) {
                    while (($p = readdir($fp)) !== false) {
                        if (strpos($p, 'exhibit') === 0) {
                            $plugins[] = str_replace(array('exhibit.', '.php'), '', $p);
                        }
                    } 
                }
                closedir($fp);
            }
            sort($plugins);
        } else {
            global $default;
            $plugins = $default['standard_formats'];
            foreach ($plugins as $k => $p) {
                if (!file_exists($path . DS . "exhibit.$p.php")) {
                    unset($plugins[$k]);
                }
            }
        }
        return $plugins;
    }
}
