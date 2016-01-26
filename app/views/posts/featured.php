<?php
  $errors = $this->get_errors($this->params["featured_posts_result"]);
  $protected = $this->get_protected_info($this->params["featured_posts_result"]);

  $posts = isset($this->params["featured_posts_result"]["data"]["posts"]) ? $this->params["featured_posts_result"]["data"]["posts"] : array();

  if ( count($posts) > 0 || strlen($errors) > 0 ) {

    $header = "Featured";
    isset($this->params["featured_posts_result"]["data"]["header"]) && $header = $this->params["featured_posts_result"]["data"]["header"];
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

