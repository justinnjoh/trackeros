
<?php
  $errors = $this->get_errors($this->params["my_posts_result"]);
  $protected = $this->get_protected_info($this->params["my_posts_result"]);

  $posts = isset($this->params["my_posts_result"]["data"]["posts"]) ? $this->params["my_posts_result"]["data"]["posts"] : array();

  if ( strlen($errors) > 0 ) {
  	echo $errors;
  }
  else {
  	$header = "My Posts";
    isset($this->params["my_posts_result"]["data"]["header"]) && $header = $this->params["my_posts_result"]["data"]["header"];

    include("_list_posts.php");
  }

?>
