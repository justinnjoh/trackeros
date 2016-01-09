
<?php

$errors = $this->get_errors($this->params["assign_wiz1"]);
$protected = $this->get_protected_info($this->params["assign_wiz1"]);

//print_r($protected);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {

  $post = array();
  isset($this->params["assign_wiz1"]["data"]["post"]) && $post = $this->params["assign_wiz1"]["data"]["post"];

  $users = array();
  isset($this->params["assign_wiz1"]["data"]["users"]) && $users = $this->params["assign_wiz1"]["data"]["users"];

  $current_assigned_to = 0;
  $count = 0;
?>

<form id="global-form" class="add-post-form">
  <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
  <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

  <?php
    if ( count($post) > 0 ) {
      $current_assigned_to = $post["assigned_to"];
  ?>

      <div class="form-group row">
        <div class="col-md-12">
          <?php
            echo $post["title"];
          ?>
        </div>
    </div>
  <?php
    }
  ?>

  <fieldset class="form-group">
  	<label>
  	  Assign to
  	</label>
    <select type="select" name="usr">
      <?php 
        foreach ( $users as $item ) {
          if ( $item["id"] != $current_assigned_id ) {
            $count += 1;

            echo " <option value='" . $item["id"] . "'>" .
              $this->escape_string($item["name"]) .
              "</option>" . PHP_EOL;
          }
        }
      ?>
    </select>

    <button type="button" class="btn btn-primary btn-sm add-post" data-action="close">
      <span class="fa fa-close"></span>
      Close
    </button>

    <?php 
      if ( $count > 0 ) {
    ?>
        <button type="button" class="btn btn-primary btn-sm add-post" data-action="assign-user">
          <span class="fa fa-play"></span> Submit
        </button>
    <?php 
      }
    ?>

  </fieldset>

</form>

<?php
}

?>

