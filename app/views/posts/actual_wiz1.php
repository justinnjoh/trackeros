
<?php

$errors = $this->get_errors($this->params["actual_wiz1"]);
$protected = $this->get_protected_info($this->params["actual_wiz1"]);

//print_r($protected);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {

  $post = array();
  isset($this->params["actual_wiz1"]["data"]["post"]) && $post = $this->params["actual_wiz1"]["data"]["post"];

  $start_date_actual = null;
  $end_date_actual = null;
  $days_actual = -1;
  $title = "Set actual dates";

  if ( count($post) > 0 ) {
    $start_date_actual = $post["start_date_actual"];
    $end_date_actual = $post["end_date_actual"];
    $days_actual = $post["days_actual"];
    $title .= ": " . $post["title"];
  }

?>

<form id="global-form" class="add-post-form">
  <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
  <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

  <div class="form-group row">
    <div class="col-md-12">
      <?php
        echo $title;
      ?>
    </div>
  </div>

  <div class="form-group row">
    <div class="col-md-3 col-xs-12">
  	  <label>
  	    Actual start date
        <input type="text" class="datetime form-control form-control-sm" name="start_date_actual" value="<?php echo $this->get_datetime_string($start_date_actual, '', 'Y-m-d'); ?>">
  	  </label>
    </div>

    <div class="col-md-3 col-xs-12">
      <label>
        Actual end date
        <input type="text" class="datetime form-control form-control-sm" name="end_date_actual" value="<?php echo $this->get_datetime_string($end_date_actual, '', 'Y-m-d'); ?>">
      </label>
    </div>

    <div class="col-md-3 col-xs-12">
      <label>
        Actual days
        <input type="number" class="form-control form-control-sm" name="days_actual" value="<?php echo $days_actual; ?>">
      </label>
    </div>

    <div class="col-md-3 col-xs-12 m-t-md">
      <button type="button" class="btn btn-primary btn-sm add-post" data-action="close">
        <span class="fa fa-close"></span>
        Close
      </button>

      <button type="button" class="btn btn-primary btn-sm add-post" data-action="actual">
        <span class="fa fa-play"></span> Submit
      </button>
    </div>

  </div>

</form>

<?php
}

?>

