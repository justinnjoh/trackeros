<?php
  $errors = $this->get_errors($this->params["featured_posts_result"]);
  $protected = $this->get_protected_info($this->params["featured_posts_result"]);

  $posts = isset($this->params["featured_posts_result"]["data"]["posts"]) ? $this->params["featured_posts_result"]["data"]["posts"] : array();

  if ( count($posts) > 0 || strlen($errors) > 0 ) {

    $header = "Featured";
    isset($this->params["featured_posts_result"]["data"]["header"]) && $header = $this->params["featured_posts_result"]["data"]["header"];
?>

    <h5 class="bg-info side-header">
      <?php echo $header; ?>
    </h5>

    <?php
      if ( strlen($errors) > 0 ) {
  	    echo $errors;
      }
    else {
      include("_list_posts_minimal.php");
    }
  }
?>

