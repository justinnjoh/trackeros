<?php

  $errors = $this->get_errors($this->params["show_user"]);

  $user = array();
  isset($this->params["show_user"]["data"]["user"]) && $user = $this->params["show_user"]["data"]["user"];

  strlen($errors) < 1 && count($user) < 1 && $errors = "Sorry user record was not found"; // UNLIKELY
?>

<div class="row">
  <div id="global-item" class="col-md-12 global-item">
  </div>
</div>

<?php
if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {
  $name = $this->escape_string($user["name"]);
  $rights = $this->escape_string($user["rights"]);
  $about = $this->escape_string($user["about"]);

  $links = "";
  if ( $user["can_edit"] > 0 ) {
    $links = "<a href='#' class='add-user' data-id='" . $user["user_id"] . "' data-target='' " .
      "data-action='edit'>edit</a>";
  }

?>

<div class="row">
  <div class="col-md-12">

    <div class="media">
      <a class="media-left" href="#">
        <img class="media-object" src="<?php echo $user["image_url"]; ?>" alt="<?php echo $name; ?>" title="<?php echo $name; ?>">
      </a>
      <div class="media-body">
        <h4 class="media-heading">
          <?php echo $name; ?>
        </h4>
        <span class="text-muted block">
          <?php 
            echo $user["headline"];
          ?>
        </span>

        <ul class="list-inline bg-info pad4">
          <li>
            <?php echo ucfirst($this->app_helper->get_right($rights, $rights)[0]); ?>
          </li>
          <li>
            <?php
              echo "Member since " .
              $this->get_datetime_string($user['created_at']); 
            ?>
          </li>

          <li class="pull-right">
            <?php echo $links; ?>
          </li>
        </ul>

      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <?php 
      echo $about;
    ?>
  </div>
</div>

<?php 
}
?>
