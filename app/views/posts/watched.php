<?php
  $errors = $this->get_errors($this->params["posts_watched_result"]);
  $protected = $this->get_protected_info($this->params["posts_watched_result"]);

  $posts = isset($this->params["posts_watched_result"]["data"]["posts"]) ? $this->params["posts_watched_result"]["data"]["posts"] : array();

  if ( count($posts) > 0 || strlen($errors) > 0 ) {

    $header = "Watched";
    isset($this->params["posts_watched_result"]["data"]["header"]) && $header = $this->params["posts_watched_result"]["data"]["header"];
?>
    <div class="card">
      <div class="card-header card-info">
        <?php echo $header; ?>
      </div>

      <div class="card-block">

    <?php
      if ( strlen($errors) > 0 ) {
  	    echo $errors;
      }
    else {
      include("_list_posts_minimal.php");
    }
?>
      </div>
    </div>

<?php
  }
?>
