
<?php

// configuration settings
//      tested ok : 'dblibrary' => 'pdo_sqlsrv'
//      tested ok: 'dblibrary' => 'mysqli'
return array (
  'buffer' => 1,
  
  'clean_str' => '/\<script|\<\!|\#\!|\$\.|\document\.|\.on/',
  'robot_str' => '/Googlebot|bingbot|Baiduspider|MJ12bot/',

  'site' => array (
      'name' => 'Simple Issue Tracker',
      'url' => 'tracker.vi',
      'domain' => 'vi',
      'dblibrary' => 'pdo_sqlsrv',
      'mailer' => 'default',
      'mode' => 'tracker',
    ),
  
  'setup' => array(
      'sys_admin' => array(
        'email' => "someone@somedomain.com",
        'name' => "Tracker Admin",
      ),

    ),

  'modes' => array(
      'tracker' => 'Simple issue tracking',
      'blog' => 'Simple secure blogging site',
      'gallery' => 'Simple gallery site for artists and photographers',
    ),

  'image_sizes' => array (
      'site_logo' => array("0", "0"),
      'thumb' => array("0", "80"),
      'image' => array("0", "180"),
      'profile' => array("0", "80"),
    ),

  'mail' => array (
      'admin' => 'admin@somedomain.com',
      'support' => 'support@somedomain.com',
      'support_name' => 'Tracker Support',

      'default' => array (
          'host' => 'mail.somedomain.com',
          'port' => 25,
          'user_name' => '',
          'password' => '',
        ),

      'mandrill' => array(
          'host' => 'smtp.mandrillapp.com',
          'port' => 587,
          'user_name' => 'some user name',
          'password' => 'some password',
        ),

  	),

  'db' => array(
            'pdo_sqlsrv' => array (
                'server' => 'instance name',
                'database' => 'tracker',
  	            'username' => 'db user name',
  	            'password' => 'db password'
  	        ),

            'mysqli' => array(
                'server' => 'instance name',
                'database' => 'tracker',
                'username' => 'db user name',
                'password' => 'db password'
            ),

    ),

  'upload_file_extensions' => array(
      "doc", "docx", "jpg", "jpeg", "png", "rtf", "txt", "pdf", "zip", "gz", 
      "xls", "xlsx"
    ),

  'max_upload_file_size' => 2000000,

  'http_headers' => array(
    "X-Frame-Options" => "SAMEORIGIN",
  ),

  'html_tags' => array(
    "\]\]" => ">",
    "\[\[(\/?)h" => "<$1h",
    "\[\[(\/?)img" => "<$1img",
    "\[\[(\/?)p" => "<$1p",
  ),

  'providers' => array(

    'self' => array(),

    'facebook' => array(
      'app' => array(
        'appId' => 'your app id',
        'secret' => 'your ap secret'
      ),
    ),

    'twitter' => array(
      'app' => array(
        'appId' => '',
        'secret' => ''
      ),
    ),
  ),

  'google' => array(
      'api_key' => '',
    ),

);

?>


