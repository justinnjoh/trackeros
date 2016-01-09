<?php 
  $errors = $this->get_errors($this->params["posts_top10"]);
  $protected = $this->get_protected_info($this->params["posts_top10"]);

  $posts = isset($this->params["posts_top10"]["data"]["posts"]) ? $this->params["posts_top10"]["data"]["posts"] : array();

  $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "_list_posts.php";
?>

<h4 class='m-b'>
  Top 10 posts in priority order
</h4>

<?php
  if ( strlen($errors) > 0 ) {
  	echo $errors;
  }
 
  else {
 
    if ( count($posts) > 0 ) {
  	  include($view);
    }
    else {
  	  echo "No posts found";
    }
  }
?>
