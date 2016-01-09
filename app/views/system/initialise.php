<h4>
  Initialise tracker
</h4>
<span class="text-muted">
  Truncates all tables and inserts default data.
</span>

<?php
$errors = $this->get_errors($this->params["initialise_result"]);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {
?>

<div class="row">
  <div class="md-col-12">
    Tracker appears to have initialised ok.
  </div>
</div>

<?php
}
?>
