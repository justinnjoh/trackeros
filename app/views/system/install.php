<h4>
  Install tracker
</h4>

<?php

//print_r($this->params["install_result"]);

$errors = $this->get_errors($this->params["install_result"]);

$message = "Tracker appears to have installed ok";

$info = "";
if ( isset($this->params["install_result"]["info"]) ) {
  $info = $this->params["install_result"]["info"];

  strlen($info) > 0 && $message = "It seems some remedial actions are needed to complete installation of tracker";
}


if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {
?>

<div class="row">
  <div class="md-col-12">

    <?php echo $message; ?>

  </div>
</div>

<?php
}

if ( strlen($info) > 0 ) {
?>

<div class="row">
  <div class="md-col-12 alert alert-info">
    <?php echo $info; ?>
  </div>
</div>

<?php
}
?>
