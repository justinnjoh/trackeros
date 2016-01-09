
<?php
  $errors = $this->get_errors($this->params["posts_assigned_result"]);
  $protected = $this->get_protected_info($this->params["posts_assigned_result"]);

  $posts = isset($this->params["posts_assigned_result"]["data"]["posts"]) ? $this->params["posts_assigned_result"]["data"]["posts"] : array();

  if ( count($posts) > 0 || strlen($errors) > 0 ) {

    $header = "Assigned to me";
    isset($this->params["posts_assigned_result"]["data"]["header"]) && $header = $this->params["posts_assigned_result"]["data"]["header"];
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

