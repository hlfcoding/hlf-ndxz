<!DOCTYPE html>
<!-- Thanks for looking at my source code. It's hand-coded and auto-formatted. -->
<!--[if lt IE 7 ]> <html class="no-js ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]>    <html class="no-js ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]>    <html class="no-js ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <meta name="expires" content="<% pxw_cache_expires %>" />
  <meta name="verify-v1" content=" <% data_goog_webm_key %>" />
  <meta name="profile" content="http://gmpg.org/xfn/11" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black" />

  <meta name="description" content="<% data_description %>" />
  <meta name="keywords" content="<% data_keywords %>" />
  <meta name="author" content="<% data_name %>" />
  <meta name="owner" content="<% data_email %>" />
  <meta name="build" content="<% data_site_version %>" />
  <meta name="copyright" content="(cc) <% data_copyright_start %>-<% pxw_current_year %> <% data_name %>. <% data_copyright_meta %>" />

  <title><% title %> {<% obj_name %>}</title>

  <link rel="author" href="<% baseurl %>" />
  <link rel="license content-license" href="<% data_content_license_link %>" />
  <link rel="license source-license" href="<% data_source_license_link %>" />
  <link rel="shortcut icon" href="<% baseurl %><% baseurlname %>/site/<% obj_theme %>/favicon.ico" />
  <link rel="apple-touch-icon" href="<% baseurl %>/favicon.png" />

  <!-- custom styles -->
  <link rel="stylesheet" href="<% baseurl %><% baseurlname %>/site/<% obj_theme %>/css/style.css" media="all" />

  <!-- indexhibit styles -->
  <plug:front_lib_css />
  <plug:front_dyn_css />

  <!-- custom scripts -->
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
  <script type="text/javascript">
  if (typeof jQuery == 'undefined') {
    document.write(unescape("%3Cscript src='<% baseurl %><% baseurlname %>/site/js/jquery.js' type='text/javascript'%3E%3C/script%3E"));
  }
  </script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/modernizr.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/underscore.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/jquery.template.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/jquery.cookie.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/jquery.extension.hlf.core.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/jquery.extension.hlf.event.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/jquery.hlf.tip.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/jquery.hlf.foldable.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/cookie.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/js/swfobject.js"></script>
  <script type="text/javascript" src="<% baseurl %><% baseurlname %>/site/pengxwang/js/site.js"></script>
  <plug:api_urls />
  <plug:news_feed section_id="<% section_id %>", feeds="github_posterous_twitter_deviantart" />
  <!--[if lt IE 9]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

  <!-- indexhibit scripts -->
  <plug:front_lib_js />
  <plug:front_dyn_js />

  <plug:backgrounder color="<% color %>", img="<% bgimg %>", tile="<% tiling %>" />
</head>
<body id="html" class="section-<% section_id %> <% pxw_browser %> <% pxw_browser_and_version %> <% pxw_browser_platform %>">
  <div id="menu">
    <div class="container">
      <div id="hd" class="box">
        <div class="in">
          <h1 id="logo"><a class="replaced" href="<% baseurl %>" title="Home"><% obj_name %></a></h1>
          <!-- <%obj_itop%> -->
        </div>
      </div>
      <div id="navigation" class="box">
        <nav class="mn-v">
          <plug:front_index />
        </nav>
      </div>
      <div id="ft" class="box copy">
        <div class="in">
          <!-- <%obj_ibot%> -->
          <p>
            <a class="copyright-symbol" href="<% data_content_license_link %>" title="<% data_copyright_tip %>">
              &copy;</a>
              <% data_copyright_start %>&ndash;<% pxw_current_year %>
            <plug:the_email
                address="<% data_email %>"
              , name="<% data_name %>"
              , title="<% data_email_tip %>"
              />
          </p>
          <p id="colophon">
            <em>Curation and plumbing via <a href="http://www.indexhibit.org/" title="<% data_ndxz_tip %>">Indexhibit</a><br/>
            with custom JS &amp; PHP plugins<br/>
            and a custom <a href="<% pxw_html_validation %>" title="W3C validation">HTML5</a> &amp; <a href="<% pxw_css_validation %>" title="W3C validation">SCSS</a> theme<br/>
            Hosting via <a href="<% data_dreamhost_link %>" title="<% data_dreamhost_tip %>">DreamHost</a></em>
          </p>
        </div>
      </div>
    </div><!-- .container -->
  </div><!-- #menu -->
  <div id="content" role="main">
    <div class="container">
      <div id="bd" class="copy">
        <plug:front_template section_id="<% section_id %>", template_name="feed" />
        <plug:front_exhibit />
      </div>
    </div><!-- .container -->
  </div><!-- #content -->
  <script type="text/javascript">
    var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
    document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
    try {
      var pageTracker = _gat._getTracker("UA-10346713-1");
      pageTracker._trackPageview();
    } catch (err) {}
  </script>
</body>
</html>

