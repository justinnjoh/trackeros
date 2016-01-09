
<?php

//print_r($this->params["edit_post_result"]);
//print_r($this);

$errors = $this->get_errors($this->params["edit_post_result"]);
$protected = $this->get_protected_info($this->params["edit_post_result"]);

//print_r($protected);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {
  $commenting = $this->get_value('post_commenting', 11);
  $id = $this->get_value('post_id', 0);
  $header = $id > 0 ? "Edit post" : "Add new post";

  $start_date_proposed = $this->get_value("post_start_date_proposed", null);
  $end_date_proposed = $this->get_value("post_end_date_proposed", null);

  $images = array();
  isset($this->params["edit_post_result"]["data"]["images"]) && $images = $this->params["edit_post_result"]["data"]["images"];

?>

<form id="global-form" class="add-post-form">
  <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
  <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

  <div class="form-group row">
    <div class="col-md-12">
      <h5 class="text-success">
        <?php echo $header; ?>
      </h5>
    </div>
  </div>

  <fieldset class="form-group">
  	<label>
  	  Title
  	</label>
  	<input type="text" maxlength="200" class="form-control form-control-sm" name="title" placeholder="Enter post title" value="<?php echo $this->get_value('post_title', ''); ?>">
  </fieldset>

  <fieldset class="form-group">
    <label>Post</label>
    <textarea type="text" class="form-control form-control-sm" name="post" rows="8"><?php echo $this->get_value('post_post', ''); ?></textarea>
  </fieldset>

  <fieldset class="form-group">
    <label>
      URL
    </label>
    <input type="url" maxlength="100" class="form-control form-control-sm" name="url" placeholder="Enter url for more infomation" value="<?php echo $this->get_value('post_url', ''); ?>">
  </fieldset>

  <div class="form-group row">
    <div class="col-md-12">
      <div class="radio">
        <label>
          <input type="radio" name="commenting" value="0" <?php if ( $commenting == 0 ) { echo 'checked'; }?> >
          Comments are not allowed
        </label>
        <label>
          <input type="radio" name="commenting" value="11" <?php if ( $commenting == 11 ) { echo 'checked'; }?>>
          Comments are validated first
        </label>
        <label>
          <input type="radio" name="commenting" value="10" <?php if ( $commenting == 10 ) { echo 'checked'; }?>>
          Comments are published immediately
        </label>
      </div>
    </div>
  </div>

  <?php
    if ( $this->configs["mode"] == 'tracker' ) {
      $priority = $this->get_value('post_priority', 0);
  ?>

  <div class="form-group row">
    <div class="col-md-12">
      <label>
        Priority
      </label>

      <div class="radio">
      <?php
        //$priorities = array_slice(array_keys($this->app_helper->priorities), 1);
        $priorities = array_keys($this->app_helper->priorities);
        foreach ( $priorities as $key) {
          $checked = $priority == $key ? " checked" : "";
          $value = $this->app_helper->priorities[$key];
      ?>
          <label title="<?php echo $value[1]; ?>" class="m-r">
            <input type="radio" name="priority" value="<?php echo $key; ?>" <?php echo $checked; ?>>
            <?php echo $value[0]; ?>
          </label>
      <?php
        }
      ?>
      </div>

    </div>
  </div>

  <div class="form-group row">
    <div class="col-md-4 col-xs-12">
      <label title='Guestimate a start date - it can always be changed'>
        Proposed start date
        <input type="text" class="datetime form-control form-control-sm" name="start_date_proposed" value="<?php echo $this->get_datetime_string($start_date_proposed, '', 'Y-m-d'); ?>">
      </label>
    </div>

    <div class="col-md-4 col-xs-12">
      <label title='Guestimate an end date - it does not have to be exact'>
        Proposed end date
        <input type="text" class="datetime form-control form-control-sm" name="end_date_proposed" value="<?php echo $this->get_datetime_string($end_date_proposed, '', 'Y-m-d'); ?>">
      </label>
    </div>

    <div class="col-md-4 col-xs-12">
      <label title='Guestimate number of days - should not take you past the proposed end date'>
        Proposed time in days
        <input type="number" class="form-control form-control-sm" name="days_proposed" value="<?php echo $this->get_value('post_days_proposed', -1); ?>">
      </label>
    </div>
  </div>

  <?php
    }
  ?>

  <div class="form-group row">

    <div class="col-md-6 col-sm-12">
      <h5>Gallery - current files</h5>

      <?php 
        if ( count($images) > 0 ) {
      ?>

          <span class='text-muted'>
            To remove a file from the gallery, uncheck it
          </span>

          <div>

          <?php 
            foreach ($images as $item) {
              $checked = $item["main"] > 0 ? "checked" : "";

              $thumb = "/assets/images/posts/0.jpg";
              $item["is_image"] > 0 && $thumb = "/assets/images/posts/" . $item['post_id'] . "-" . $item['comment_id'] . "-" . $item['id'] . "-th." . $item['file_ext'];

              echo "<fieldset class='form-group uploads'>" . 
                "<label class='clearfix bg-info'>" .
                "<input type='checkbox' class='old_files' name='old_files[]' value='" . $item['id'] . "' checked>" . 
                "<img class='thumb' src='" . $thumb . "'> " . 
                $item['file_name'] . ", " . $item['file_size'] . " bytes" .
                "</label>" .
                "<label><input class='form-control form-control-sm' type='text' maxlength='200' title='Enter a caption for this file' placeholder='caption' name='old_caption" . $item['id'] . "' value='" . $item['caption'] . "'></label>" . 
                "<label><input type='number' title='Enter position' name='old_position" . $item['id'] . "' value='" . $item['position'] . "'></label>" .
                "<label title='Use as main image when post is displayed ?'><input type='checkbox' name='old_main" . $item['id'] . "' value='1' " . $checked . "> Use as main image</label>" .
                "</fieldset>";
            }
      ?>

          </div>

      <?php
        }
        else {
          echo "No files thus far";
        }
      ?>
    
    </div>

    <div class="col-md-6 col-sm-12">

      <h5>
        Gallery - upload new files (2G max)
      </h5>

      <span class="text-muted">
        To remove a file image, uncheck it
      </span>

      <input type="file" multiple accept="image/*" name="file" id="images" data-info="1">
      <div id="file_list">
      </div>

    </div>

  </div>

  <div class="form-group row">
    <div class="col-md-9 col-sm-12">
      <div id='global-form-error' class='text-warning'>
      </div>
    </div>

    <div class="col-md-3 col-sm-12">
      <fieldset class="form-group pull-right">
        <button type="button" class="btn btn-primary btn-sm add-post" data-action="close">
          <span class="fa fa-close"></span>
          Close
        </button>
        <button type="button" class="btn btn-primary btn-sm add-post" data-action="submit">
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

