
<?php

$errors = $this->get_errors($this->params["edit_user_result"]);
$protected = $this->get_protected_info($this->params["edit_user_result"]);

//print_r($protected);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {

  $status = $this->get_value("usr_status", 11);
  $rights = $this->get_value("usr_rights", 0);
  $id = $this->get_value("usr_id", 0);

  $image_url = $this->get_value("usr_image_url", "");

  $header = "Edit user details";
  isset($this->params["edit_user_result"]["data"]["header"]) && $header = $this->params["edit_user_result"]["data"]["header"];

  $can_set_rights = 0;
  isset($this->params["edit_user_result"]["data"]["can_set_rights"]) && $can_set_rights = $this->params["edit_user_result"]["data"]["can_set_rights"]; 
?>

<form id="global-form" class="add-user-form">
  <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
  <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

  <div class="form-group row">
    <div class="col-md-12">
      <h5 class="text-success">
        <?php
          echo $header;
        ?>
      </h5>
    </div>
  </div>

  <fieldset class="form-group">
  	<label>
  	  Name
  	</label>
  	<input type="text" maxlength="200" class="form-control form-control-sm" name="name" placeholder="Enter a name" value="<?php echo $this->get_value('usr_name', ''); ?>">
  </fieldset>

  <fieldset class="form-group">
    <label>
      Headline
    </label>
    <input type="text" maxlength="100" class="form-control form-control-sm" name="headline" placeholder="Job title or what you're about" value="<?php echo $this->get_value('usr_headline', ''); ?>">
  </fieldset>

  <fieldset class="form-group">
    <label>
      About

      <span class="text-muted block">
        800 characters maximum.
      </span>
      <span class="block text-info" id="about-msg">
      </span>

    </label>
    <textarea type="text" class="form-control form-control-sm count-chars" data-max="800" name="about" id="about" rows="6" placeholder="Enter more about yourself"><?php echo $this->get_value('usr_about', ''); ?></textarea>
  </fieldset>

  <?php
  if ( $can_set_rights > 0 ) {
  ?>

  <div class="form-group row">

    <div class="col-md-6 col-sm-12">

      Status

      <div class="radio">
        <label>
          <input type="radio" name="status" value="10" <?php if ( $status == 10 ) { echo 'checked'; }?> >
          Active
        </label>

        <?php 
          if ( $id > 0 ) {
        ?>

        <label>
          <input type="radio" name="status" value="1" <?php if ( $status == 1 ) { echo 'checked'; }?>>
          Inactive
        </label>
        <label>
          <input type="radio" name="status" value="0" <?php if ( $status == 0 ) { echo 'checked'; }?>>
          Delete
        </label>

        <?php
          }
          else {
        ?>

        <label>
          <input type="radio" name="status" value="11" <?php if ( $status == 11 ) { echo 'checked'; }?>>
          New
        </label>

        <?php
          }
        ?>

      </div>
    </div>

    <div class="col-md-6 col-sm-12">

      Rights

      <div class="radio">
        <label>
          <input type="radio" name="rights" value="0" <?php if ( $rights == 0 ) { echo 'checked'; }?> >
          User
        </label>

        <label>
          <input type="radio" name="rights" value="30" <?php if ( $rights == 30 ) { echo 'checked'; }?>>
          Administrator
        </label>

      </div>
    </div>

  </div>

  <?php 
  }
  ?>

  <div class="form-group row">
    <div class="col-md-6 col-sm-12">
      <label>
        Upload a new image
      </label>
      <input type="file" multiple accept="image/*" name="file" id="images" data-info="0">
      <div id="file_list">
      </div>
    </div>

    <div class="col-md-6 col-sm-12">
      <?php
      if ( strlen($image_url) > 0 ) {
      ?>
        <div class="checkbox">
          <img src="<?php echo $image_url; ?>" class="thumb" title="Your current image" alt="Your current image" />
          <label>
            <input type="checkbox" name="use_current" value="1" checked>
            Use current image
          </label>
        </div>

      <?php
      }
      ?>

    </div>

  </div>

  <div class="form-group row">
    <div class="col-md-9 col-sm-12">
      <span id='global-form-error' class='text-error'>
      </span>
    </div>

    <div class="col-md-3 col-sm-12">
      <fieldset class="form-group pull-right">
        <button type="button" class="btn btn-primary btn-sm add-user" data-action="close">
          <span class="fa fa-close"></span>
          Close
        </button>
        <button type="button" class="btn btn-primary btn-sm add-user" data-action="submit">
          <span class="fa fa-play"></span>
          Submit
        </button>
      </fieldset>
    </div>
  </div>

</form>

<?php
}

?>

