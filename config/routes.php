<?php
// routes take the form: <controller> => <controller>/<action>
// these routes will be matched FIRST

return array(

  'layouts' => array(
      'default' => array(
      	  'layout' => 'application',
      	  'sections' => array(
            "posts/postsassigned",
            "posts/stats",
            "posts/featured",
      	  )
        ),
      'member' => array(
      	  'layout' => 'application',
      	  'sections' => array(
            "posts/postsassigned",
            "posts/stats",
            "posts/featured",
      	  )
        )
    ),

  'routes' => array(
      "/" => "home/index",
      "login" => "sessions/login",
      "logout" => "sessions/logout",
      "forgot" => "sessions/forgot",
      "reset" => "sessions/reset",
      "verify" => "sessions/verify",
      "about" => "home/about",

     "install" => "system/install",
     "initialise" => "system/initialise",
     "howto" => "system/howto",

    ),

)

?>


