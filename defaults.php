<?php if (!defined('SITE')) exit('No direct script access allowed');

// --------------------------------------------------
// constants
// modify as needed if you know what you're doing
// --------------------------------------------------
// assume modrewrite is being used
define('MODREWRITE', true);
// database prefix
define('PX', 'ndxz_'); // @todo deprecated
// version
define('VERSION', '0.71');
// language to default to in case of error
define('LANGUAGE', 'en-us');
// util
define('DS', DIRECTORY_SEPARATOR);
// application development and testing and release lifecycle
define('DEVELOPMENT', 9999);
define('PRODUCTION', 'production'); // doubles as subdirectory name

// --------------------------------------------------
// config
// modify as needed if you know what you're doing
// --------------------------------------------------
$tables = array(
    'object' => 'objects',
    'object_meta' => 'objects_prefs',
    'section' => 'sections',
    'setting' => 'settings',
    'statistic' => 'stats',
    'user' => 'users',
    'media' => 'media'
);
foreach ($tables as &$table) {
    $table = PX . $table;
}
// note: `default` not `defaults`
$default = array(
    'img_quality' => 100, // image quality
    'ignore_ip' => array(), // things you don't want stats to track
    'module' => 'exhibits',
    'statistics' => false,
    'editor' => 'markdown++',
    'cache_time' => 24, // hours
    'first_year' => 1994, // TODO what's this for
    'encoding' => 'UTF-8',
    'thumbsize' => array(100 =>100, 150 => 150, 200 => 200), // basic sizes for images and thumbnails uploading
    'imagesize' => array(200 => 200, 400 => 400, 600 => 600, 'full' => 9999),
    'exhibit_imgs' => 6, // max exhibit images upload
    'standard_formats' => array('grow', 'grow_no_title', 'over_and_over', 'no_thumbs_w_captions'),
    'color_picker' => true,
    'sql' => 'mysql',
    'helpers' => array('time'),
    'reporting' => false,
    'content_xml_filename' => 'data',
    'content_xml_namespace' => 'data',
    'run_tests' => false,
    'run_traces' => true,
);
$uploads = array(
    'images' => array('jpg', 'gif', 'png'),
    'media' => array('mov', 'mp3', 'jar'),
    'files' => array('txt', 'pdf', 'doc', 'xls'),
    'flash' => array('swf')
);

// --------------------------------------------------
// paths
// modify as needed if you know what you're doing
// --------------------------------------------------
// core paths
define('LIBPATH', 'lib');
define('HELPATH', 'helper');
define('MODPATH', 'module');
define('DBPATH', 'db');
define('LANGPATH', 'lang');
define('EXTPATH', 'extend');
define('SERVPATH', EXTPATH . '/service');
define('SITEPATH', 'site');
define('PLUGPATH', SITEPATH . '/plugin');
define('CMSPATH', 'module');
$core_paths = array(
    LIBPATH => LIBPATH,
    DBPATH => DBPATH,
    HELPATH => HELPATH,
    MODPATH => MODPATH,
    LANGPATH => LANGPATH,
    EXTPATH => EXTPATH,
    PLUGPATH => PLUGPATH,
    'mod' => CMSPATH
);

// cms paths
define('ASSET', 'asset/');
define('CSS', ASSET . 'css/');
define('JS', ASSET . 'js/');
define('IMG', ASSET . 'img/');
define('TPLPATH', ASSET . 'tpl/');

// other paths
define('TESTPATH', 'test');

// app path segments
define('BASENAME', DS . 'ndxz-studio');
define('BASEURLNAME', str_replace("\\", '/', BASENAME));
define('DIRNAME', str_replace(BASENAME, '', realpath(dirname(__FILE__))));

// app url segments
$self = (dirname($_SERVER['PHP_SELF']) === '/') ? '' : dirname($_SERVER['PHP_SELF']);
$self = str_replace('/_main', '', $self);
$base = str_replace('/ndxz-studio', '', "http://{$_SERVER['HTTP_HOST']}$self/");
define('BASEURL', preg_replace("/\/$/i", '', $base));

// media paths
define('BASEFILES', '/files');
define('GIMGS', BASEFILES . '/gimgs');
