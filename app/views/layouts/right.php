<?php
if ( !isset($_SESSION["user_id"]) ) {

  $socials = 0;
  $self = isset($this->configs["providers"]["self"]) ? 1 : 0;

  if ( isset($this->configs["providers"]["facebook"]) ) {
    $socials += 1;
?>
    <div class='social-login'>

      <button class="login-signup" data-provider='facebook'>
        <span class="fa fa-facebook"></span> Login with facebook
      </button>

    </div>

  <?php
  }

  if ( $socials > 0 && $self > 0 ) {
  ?>

    <hr class='blue-line' />

    <h5>
      OR
    </h5>
  <?php
  }

  if ( $self > 0 ) {
  ?>

  <form id='login-signup'>

    <fieldset class="form-group">
      <span class='fa fa-envelope'></span>
      <input type='email' name='user_name' maxlength='100' placeholder='email address'>
    </fieldset>

    <fieldset class="form-group">
      <span class='fa fa-key'></span>
      <input type='password' name='password' maxlength='50' placeholder='password'>
    </fieldset>

    <fieldset class="form-group">
      <span class='fa fa-user'></span>
      <input type='text' name='name' maxlength='200' placeholder='Your name if registering'>
    </fieldset>

    <div class="block" id="login-signup-msg">
    </div>

    <button data-provider='self' type='button' class='login-signup btn btn-primary  btn-sm'>
      <span class="fa fa-play"></span> 
      Login
    </button>

    <button data-provider='self' data-create="1" type='button' class='login-signup btn btn-primary btn-sm'>
      <span class="fa fa-play"></span> 
      Register
    </button>

  </form>

  <div class="p-t">
    <span>
      <a href="/forgot" title='Click here to reset your password'>
        Forgot password
      </a>
    </span>
  </div>

<?php
  } // self

}
else {
  // logged in
  $views_path = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR .
    "views";

  $featured = $views_path . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "featured.php";
  $assigned_to_me = $views_path . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "posts_assigned.php";
  $stats = $views_path . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "_stats.php";

  include($stats);
  include($assigned_to_me);
  include($featured);
}
?>

