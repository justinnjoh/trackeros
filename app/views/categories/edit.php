
<?php

//print_r($this->params["edit_category_result"]);
//print_r($this);

$errors = $this->get_errors($this->params["edit_category_result"]);
$protected = $this->get_protected_info($this->params["edit_category_result"]);

//print_r($protected);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {

  $posts_status = $this->get_value("cat_posts_status", 11);
  $status = $this->get_value("cat_status", 10);
  $type = $this->get_value("cat_type", 0);
  $id = $this->get_value("cat_id", 0);

?>

<form id="global-form" class="add-category-form">
  <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
  <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

  <div class="form-group row">
    <div class="col-md-12">
      <h5 class="text-success">
        Add / Edit Category
      </h5>
      <label class="text-muted">
        Categories are main menu items.
      </label>
    </div>
  </div>

  <fieldset class="form-group">
  	<label>
  	  Category
  	</label>
  	<input type="text" maxlength="100" class="form-control form-control-sm" name="category" placeholder="Enter a category" value="<?php echo $this->get_value('cat_category', ''); ?>">
  </fieldset>

  <fieldset class="form-group">
    <label>Description</label>
    <textarea type="text" class="form-control form-control-sm" name="description" rows="2"><?php echo $this->get_value('cat_description', ''); ?></textarea>
  </fieldset>

  <div class="form-group row">
    <div class="col-md-2 col-sm-12">
      <label>
        Position
        <input class="form-control form-control-sm" name="position" type="number" value="<?php echo $this->get_value('cat_position', 0); ?>">
      </label>
    </div>

    <div class="col-md-4 col-sm-12">
      <div class="radio">
        Type
        <label>
          <input type="radio" name="type" value="0" <?php if ( $type == 0 ) { echo 'checked'; }?> >
          Private - posts seen by registered users
        </label>
        <label>
          <input type="radio" name="type" value="10" <?php if ( $type == 10 ) { echo 'checked'; }?> >
          Public - posts seen by all
        </label>
      </div>
    </div>

    <div class="col-md-4 col-sm-12">
      <div class="radio">
        Publishing
        <label>
          <input type="radio" name="posts_status" value="11" <?php if ( $posts_status == 11 ) { echo 'checked'; }?> >
          Posts are validated first
        </label>
        <label>
          <input type="radio" name="posts_status" value="10" <?php if ( $posts_status == 10 ) { echo 'checked'; }?>>
          Posts are published immediately
        </label>
      </div>
    </div>

    <div class="col-md-2 col-sm-12">
      <div class="radio">
        Status
        <label>
          <input type="radio" name="status" value="10" <?php if ( $status == 10 ) { echo 'checked'; }?> >
          Active
        </label>
        <label>
          <input type="radio" name="status" value="1" <?php if ( $status == 1 ) { echo 'checked'; }?>>
          Inactive
        </label>

        <?php 
        if ( $id > 0 ) {
        ?>
          <label>
            <input type="radio" name="status" value="0" <?php if ( $status == 0 ) { echo 'checked'; }?>>
            Delete
          </label>
        <?php 
        }
        ?>

      </div>
    </div>

  </div>

  <div class="form-group row">
    <div class="col-md-9 col-sm-12">
      <span id='global-form-error' class='text-error'>
      </span>
    </div>

    <div class="col-md-3 col-sm-12">
      <fieldset class="form-group pull-right">
        <button type="button" class="btn btn-primary btn-sm add-category" data-action="close">
          <span class="fa fa-close"></span>
          Close
        </button>
        <button type="button" class="btn btn-primary btn-sm add-category" data-action="submit">
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

