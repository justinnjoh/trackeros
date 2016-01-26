<!DOCTYPE html>
<html lang="en">
<head>

  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <?php echo $this->get_page_meta_tags(); ?>

  <link href="/assets/stylesheets/fontawesome440/css/font-awesome.css" rel="stylesheet" media="all" type="text/css">
  <link href="/assets/stylesheets/bootstrap400/css/bootstrap.css" rel="stylesheet" media="all" type="text/css">

  <link href="/assets/stylesheets/jquery-ui/jquery-ui.css" rel="stylesheet" media="all" type="text/css">

  <link href="/assets/stylesheets/custom.css" rel="stylesheet" media="all" type="text/css">

  <script src="/assets/javascripts/jquery214/jquery.js" type="text/javascript"></script> 
  <script src="/assets/stylesheets/bootstrap400/js/bootstrap.js" type="text/javascript"></script> 

  <script src="/assets/javascripts/jquery-ui/jquery-ui.js" type="text/javascript"></script>

  <script src="/assets/javascripts/tracker.js" type="text/javascript"></script>
  <script src="/assets/javascripts/custom.js" type="text/javascript"></script>

  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>  
  <![endif]-->


  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
  
  <title><?php echo $this->get_page_title(); ?></title>
  
</head>

<body>

<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '532860120196988',
      cookie     : true,
      xfbml      : true,
      version    : 'v2.4'
    });
  };

  (function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/sdk.js";
      fjs.parentNode.insertBefore(js, fjs);
    }
    (document, 'script', 'facebook-jssdk')
  );

</script>



<?php include("header.php") ?>

<div class="container-fluid">

  <div class="row">

    <div class="col-xs-12 col-sm-6 col-md-9 col-lg-9 p-l-0">

      <?php 
        if ( isset($_SESSION['flash']) ) {
      ?>
          <?php foreach ($_SESSION['flash'] as $item ) { ?>
          <?php echo "<div class='" . $item["class"] . "'>" . $item["text"] . "</div>" . PHP_EOL ?> 
          <?php } ?>

      <?php
          unset($_SESSION['flash']);
        }
      ?>

      <?php
        // inject html content
        if ( isset($this->inject) && count($this->inject) > 0 ) {
          foreach ( $this->inject as $inj => $typ ) {
            if ( $typ != 'file' && isset($this->params[$inj]) ) {
              echo $this->params[$inj];
            }
          }
        }
      ?>

      <?php

        //print_r($this->inject);
        // inject file content
        if ( isset($this->inject) && count($this->inject) > 0 ) {
          foreach ( $this->inject as $inj => $typ ) {
            if ( $typ == 'file') {
              include ($inj);
            }
          }
        }

      ?>

      <!-- yield -->
      <?php
        isset($this->yield) && strlen($this->yield) > 0 && include ($this->yield);
      ?>   

    </div> <!-- c10 -->


    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3 p-r-0">

      <?php
        include("right.php");
      ?>

    </div> <!-- c2 -->

  </div> <!-- row -->

</div> <!-- container fluid -->

<?php include("footer.php"); ?>

</body>
</html>
