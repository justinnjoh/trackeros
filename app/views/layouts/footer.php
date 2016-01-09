
<div class="container-fluid">
  <div class="row footer m-t">

    <div class="col-md-4 col-xs-6">
      <strong class='text-info'>
        Simple Tracker
      </strong>

      <span class='text-muted block'>
        This program is free software. <br />
        <a href="/about">Click here</a> to learn more.
      </span>

    </div>

    <div class="col-md-4 col-xs-6">
      <strong class='text-info'>
        Credits
      </strong>
      
      <span class='block'>
        <a target="_new" href="http://v4-alpha.getbootstrap.com/getting-started/introduction/">
          Twitter's Bootstrap
        </a>
      </span>

      <span class='block'>
        <a target="_new" href="https://jquery.org/">
          jQuery
        </a>
      </span>

      <span class='block'>
        <a target="_new" href="http://fortawesome.github.io/Font-Awesome/">
          Font Awesome
        </a>
      </span>

      <span class='block'>
        <a target="_new" href="http://swiftmailer.org/">
          Swift Mailer
        </a>
      </span>

    </div>

    <div class="col-md-4 col-xs-6">
      <strong class='text-info'>
        Contact
      </strong>

      <span class='block'>
        tracker [at] lisol [dot] co [dot] uk
      </span>
    </div>

  </div>

</div>

<?php
if (DEBUG > 0) {
  echo "<br />";
  $d = new Debug_lib();
  $d->print_hash($_SESSION, "_SESSION");

  echo "<p />";

  if (DEBUG == 1) { 
    $d->print_object($this->params);
  }
  else {
    $d->print_object($this);
  }
  
  echo "<p />";
  $d->print_hash($_COOKIE, "_COOKIE");

  //echo "<p />";
  //print_r($this);

  var_dump($_COOKIE);
  //print_r($_REQUEST);
}

?>
