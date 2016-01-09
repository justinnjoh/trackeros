<?php

  $errors = $this->get_errors($this->params["verify_result"]);
  $protected = $this->get_protected_info($this->params["verify_result"]);


  //print_r($this->params["verify_result"]);

  $data = array();
  isset($this->params["verify_result"]["data"]) && $data = $this->params["verify_result"]["data"];

  $token_status = isset($data["token_status"]) ? $data["token_status"] : 10;

  $header = "Reset your password";
  $token_status == 10 && $header = "Verify your registration";
?>

<h4>
  <?php echo $header; ?>
</h4>

<?php
if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {

  if ( $token_status == 10 ) {
?>

  <div class="row">
    <div class="col-md-12 alert alert-info">
      <?php echo $this->params["verify_result"]["info"]; ?>
    </div>
  </div>

<?php

  }
  else {

?>

  <div class="row">
    <div class="col-md-12">
  	  Your information was found - please enter your new password below.
  	</div>
  </div>

  <div class="row">
    <div class="col-md-4 col-sx-12">

      <form id="global-form" class="">
        <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
        <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

        <fieldset class="form-group">
  	      <label>
  	        New Password
  	      </label>
  	      <input type="password" maxlength="40" class="form-control form-control-sm" name="password1" placeholder="Enter new password" value="">
        </fieldset>

        <fieldset class="form-group">
  	      <label>
  	        Confirm Password
  	      </label>
  	      <input type="password" maxlength="40"  class="form-control form-control-sm" name="password2" placeholder="Confirm new password" value="">
        </fieldset>

        <div class="row">
          <div class="col-md-4 col-sx-12">
            <button type="button" class="btn btn-primary btn-sm forgot" data-action="update">
              <span class="fa fa-play"></span>
              Submit
            </button>
          </div>

          <div class="col-md-8 col-sx-12">
            <div id='global-form-error' class='text-warning'>
            </div>

          </div>
        </div>

      </form>
    </div>
  </div>

<?php
  }

}
?>
