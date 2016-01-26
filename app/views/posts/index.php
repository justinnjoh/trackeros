
<?php
  $errors = $this->get_errors($this->params["posts_index"]);
  $protected = $this->get_protected_info($this->params["posts_index"]);

  $posts = isset($this->params["posts_index"]["data"]["posts"]) ? $this->params["posts_index"]["data"]["posts"] : array();
  $category = isset($this->params["posts_index"]["data"]["category"]) ? $this->params["posts_index"]["data"]["category"] : array();
  $status_codes = isset($this->params["posts_index"]["data"]["status_codes"]) ? $this->params["posts_index"]["data"]["status_codes"] : array();

  $btn_label = isset($this->params["posts_index"]["data"]["btn_label"]) ? $this->params["posts_index"]["data"]["btn_label"] : "";

  include("_posts_header.php");
  include("_list_posts.php");
?>

