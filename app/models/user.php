<?php

// user model
class User_Model extends Base_model {

  public function find_user($id = null, $provider = null, $user_name = null) {
    // returns the user's details; $id takes prcedence over provider and user_name
    // params assumed validated; not exposed - meant to be called by other functions

    $query = "select users.*, user_details.name, user_details.headline, " .
      "user_details.rights, user_details.image_url, user_details.organisation_id, " .
      "organisations.organisation, user_details.status from users inner join user_details " .
      "on users.id = user_details.user_id inner join organisations on " .
      "user_details.organisation_id = organisations.id where ";

    if ( !is_null($id) && $id > 0 ) {
      $query .= "users.id = " . $id;
    }
    else {
      $query .= "users.provider = '" . $provider . "' and " .
          "users.user_name = '" . $user_name . "';";
    }

    $res = $this->query($query);

    $error = $this->db_error;

    $debug = "";
    DEBUG > 0 && $debug = $this->db_debug;

    $user = strlen($error) < 1 && isset($res[0]) && count($res[0]) > 0 ? $res[0][0] : null;

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $user,
      'info' => ''
    );

    return ($result);
  }

  public function add_user($provider = null, $user_name = null, $password = null, $name = null, $status = null) {
    // add a user - can be from provider 'self' or other (eg facebook)
    // returns a result : { data: <user_id just added>, error: <errors object>}
    // params assumed validated; meant to be called from other functions

    $error = '';
    $debug = '';
    $user = null;
    $info = '';

    // these attributes may be available in the form for calling function
    is_null($name) && $name = '';
    strlen($name) < 1 && isset($_POST['name']) && $name = $_POST['name'];

    $email = '';
    isset($_POST['email']) && $email = $_POST['email'];

    // default email to user name if the user name looks like an email address
    strlen($email) < 1 && strpos('@', $user_name) >= 0 && $email = $user_name;

    // default provider to 'self'
    is_null($provider) && $provider = '';
    strlen($provider) < 1 && $provider = 'self'; 

    strlen($user_name) < 1 && $error = 'Cannot create account - a user ID or an email address is required';
    strlen($error) < 1 && strlen($name) < 1 && $error = 'Please enter your name';

    if ( strlen($error) < 1 && $provider == 'self' ) {
      strlen($password) < 1 && $error = "Cannot create account - please supply a valid password";
      strlen($error) < 1 && !filter_var($email, FILTER_VALIDATE_EMAIL) && $errors = "The email address provided does not appear to be valid";
    }

    if ( strlen($error) < 1 ) {
      // no validation errors - attempt to add account

      if ( is_null($status) || !is_numeric($status) || !array_key_exists($status, array(11 => 1, 10 => 1)) ) {
        $status = 11;
      }

      $terms = 0;
      if ( $provider != 'self' ) {
        $terms = 1;
        $status = 10;
        $password = $user_name;
      }

      $data = array(
        'provider' => $provider,
        'user_name' => $user_name,
        'password' => $this->codify($password)
      );

      $this->ensure_visit();

      $res = $this->insert('users', $data, true, false);
      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        $id = 0;
        isset($res[0]) && count($res[0]) >= 0 && $id = $res[0][0]['id'];
        $id > 0 || $error = "A technical problem occurred while creating an account - please inform the site administrator";
      }

      if ( strlen($error) < 1 ) {
        // attempt to add user details
        $data = array(
          'user_id' => $id,
          'name' => $name,
          'organisation_id' => $this->template->get_session_value('org_id', 0),
          'email' => $email,
          'status' => $status
        );

        if ( $terms > 0 ) {
          // if not self reg, then they've agreed terms
          $data['agreed_terms'] = $terms;
          $data['agreed_terms_at'] = $this->iso_datetime_now();
        }

        $image_url = "";

        switch ($provider) {

          case 'facebook':
            $image_url = "https://graph.facebook.com/" . $user_name . "/picture";
            break;

        }

        strlen($image_url) > 0 && $data["image_url"] = $image_url;

        $res = $this->insert('user_details', $data, false, true, 'user_id');
        $error = $this->db_error;
        DEBUG > 0 && $debug .= "; " . $this->db_debug;

        if ( strlen($error) < 1 ) {

          // success - if email validation needed, send an email
          if ( $status == 11 ) {

            $info = "We were unable to send an email to the address you supplied.";

            // set token to crypt of user_name
            $token = $this->codify($user_name . "_" . $id);
            $res = $this->set_user_token ($id, $token, 10);

            $error = $res["errors"][0]["message"];
            DEBUG > 0 && $debug .= $res["errors"][0]["debug"];

            if ( strlen($error) < 1 ) {
              // token set ok

              $subject = "New [Simple Tracker] account";
              $message = $this->make_verify_message($token, $name);

              $mailer = new Mailer_Lib();

              $ret = $mailer->swiftmail($this->mail[$this->mailer], $email, $subject,
                        $message, $this->mail['support'], $this->mail['support_name'],
                        $this->admin_email, null
                      );

              if ( $ret ) {

                $info = "Thank you for registering. An email has been sent to " .
                  $this->template->escape_string($email) .
                  ". Please, click on the link in it to verify your email address and complete your registration. " .
                  "You must be on the SAME device that you used to register for the link to work.";
              }
              else {
                $error = "There was a problem sending you a verification email message - so you will be unable to complete the registration. " .
                  "Please check the email address supplied for typos.";
              }

            } // error, token
            else {
              $info = "Programmer error - the verification process failed. Please refresh your browser and click the 'Forgot password' button.";
            }

          } // status = 11
          else {
            $info = "Thank you for registering";

            $res = $this->find_user($id, null, null, null);

            if ( isset($res["errors"][0]) && strlen($res["errors"][0]["message"]) > 0 ) {
              $error = $res["errors"][0]["message"] . '; ' . $res["errors"][0]["debug"];
            }
            else {
              $user = $res["data"];
            }

          } // else - status 10

        } // insert user details

      } // user

    } // validation errors

    $result = array(
      "errors" => array(
          array("message" => $error, "debug" => $debug)
        ),
      "data" => $user,
      "info" => $info
    );

    return ($result);
  }

  public function set_user_token ($user_id = null, $token = null, $status = null) {
    // store this token into the db for this user
    $error = "";
    $debug = "";

    if ( is_null($user_id) || !is_numeric($user_id) || $user_id < 1 ) {
      $error = "System error - a valid user ID is expected, please inform the system administrator";
    }

    if ( strlen($error) < 1 && ( is_null($token) || strlen($token) < 1 ) ) { 
      $error = "System error - a valid user token is expected, please inform the system administrator";
    }

    if ( strlen($error) < 1 ) {

      $res = $this->delete('user_tokens', $user_id, 'user_id', null);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;
    }

    if ( strlen($error) < 1 ) {
      // status : 10 (default) - set to auto login
      // 11 : reset password; show password change form if token is found
      is_null($status) && $status = 10;

      $data = array (
          "user_id" => $user_id,
          "token" => $token,
          "status" => $status
        );

      $res = $this->insert('user_tokens', $data, false, false, null);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;
    }

    if ( strlen($error) < 1 ) {

      $query = "select * from user_tokens where token = '" . 
        $token . "'; ";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      strlen($error) < 1 && isset($res[0][0]) && $token = $res[0][0];

    }

    $result = array(
      "errors" => array(
          array("message" => $error, "debug" => $debug)
        ),
      "data" => $token,
      "info" => null
    );

    return ($result);
  }

  public function manage() {
    // manage users for an organisation

    $error = "";
    $debug = "";

    $rights = $this->template->get_session_value("rights", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    $res = $this->check_right(30);
    $error = $res["errors"][0]["message"];

    $data = array();

    if ( strlen($error) < 1 ) {
      $query = "select id, organisation from organisations where [id] = " . $org_id . "; " .
        "select users.id, users.user_name, users.provider, user_details.* from users " .
        "inner join user_details on users.id = user_details.user_id " .
        "where user_details.organisation_id = " . $org_id .
        " and user_details.status in (10, 11, 1) " .
        " and (" . $rights . " >= 80 or users.id >= 100) order by user_details.name; ";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        $data["org_id"] = $org_id; 
        $data["organisation"] = isset($res[0][0]["id"]) ? $res[0][0]["organisation"] : "";
        $data["users"] = isset($res[1]) ? $res[1] : array();
      }
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $data,
      'info' => ""
    );

    $this->template->assign("manage_users", $result);
    
    // menu
    $this->get_menu();
    $this->get_helper_objects();

    return ($result);
  }

  public function edit ($user_id = null) {
    // edit user details - this process is NOT for adding new users;
    // non-admins can only edit their own profile
    // AJAX

    $debug = "";
    $error = "";

    $header = "Edit user details";

    is_null($user_id) && $user_id = 0;

    $rights = $this->template->get_session_value("rights", 0);

    $can_set_rights = 0;

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    $creator_id = $this->template->get_session_value("user_id", 0);
    $rights < 30 && $user_id = $creator_id;

 
    $creator_id < 1 && $error = "Please log in first";
    strlen($error) < 1 && $user_id < 1 && $error = "Invalid request - a user was not specified";
    strlen($error) < 1 && $org_id < 1 && $error = "An organisation is required";

    $user = array();
    
    if ( strlen($error) < 1 ) {
      $query = "select users.id, users.provider, user_details.* " .
        " from users inner join user_details on users.id = user_details.user_id " .
        " where users.id > 0 and users.id = " . $user_id .
          " and user_details.organisation_id = " . $org_id;

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $user = $res[0][0];
   
        $user_id == $this->template->get_session_value("user_id", -1) && $header = "Edit your profile";

        $rights >= 30 && $can_set_rights = 1;
      }
    }

    // protected data
    $protected_data = $user_id . "," . $org_id;
    $protected = array(
      "_p1_" => $protected_data,
      "_p2_" => $this->codify($protected_data)
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "user" => $user,
          "header" => $header,
          "can_set_rights" => $can_set_rights
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->template->assign("edit_user_result", $result);

    $this->template->assign("edit_user", $result["data"]["user"]);
    $this->template->extract_fields("edit_user", "usr_");

    // provide view for display
    $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "users" . DIRECTORY_SEPARATOR . "edit.php";
    $this->template->provide($view, 'user_edit');
    
    return ($result);

  }

  public function add_user_details () {
    // add / update user details
    // security values _p1_ and _p2_ are expected

    $debug = "";
    $error = "";
    $info = "";

    $user_id = 0;
    $org_id = 0;

    $status = -1;
    $name = "";
    $headline = "";
    $about = "";
    $rights = -1;
    $use_current = 0;

    $visit_id = $this->template->get_session_value('visit_id', 0);
    $creator_id = $this->template->get_session_value('user_id', 0);

    isset($_POST["status"]) && $status = $_POST["status"];
    isset($_POST["name"]) && $name = $_POST["name"];
    isset($_POST["headline"]) && $headline = $_POST["headline"];
    isset($_POST["about"]) && $about = $_POST["about"];
    isset($_POST["rights"]) && $rights = $_POST["rights"];

    $this_rights = $this->template->get_session_value('rights', 0);
    $this_org_id = $this->template->get_session_value('user_org_id', 0);
    $this_rights >= 80 && $this_org_id = $this->template->get_session_value('org_id', 0);

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $creator_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($user_id, $org_id) = explode(",", $p1, 2);

      $org_id < 1 && $error = "Sorry invalid request - an organisation was not specified";
    }

    strlen($error) < 1 && $rights < 30 && $org_id != $this_org_id && $error = "Sorry you do not have sufficient rights to take this action";

    if ( strlen($error) < 1 ) {
  
      $data = array (
        "name" => substr($name, 0, 199),
        "headline" => substr($headline, 0, 99),
        "about" => substr($about, 0, 799),
      );

      // only admins can change status and rights
      if ( $this_rights >= 30 ) {
        $rights > -1 && $data["rights"] = $rights;
        $status > -1 && $data["status"] = $status;
      }

      // if use_current not specified, check for uploaded image
      if ( $use_current < 1 ) {

        // pre-uploaded files - only the first one is used
        $new_files = array();
        if ( isset($_POST['new_files']) ) {
          $new_files = explode(",", $_POST['new_files']);
        }

        // in case of AJAX call, new files info will be passed via 'new_files_info'
        // <id>,,,<file name>,,,<caption>,,,<file type>,,,<size>,<pos>##<id>,,,<file name>,,, ...
        $new_files_info = "";
        isset($_POST['new_files_info']) && $new_files_info = $_POST['new_files_info'];
        $new_files_info = strlen($new_files_info) > 0 ? explode('##', $new_files_info) : array();

        if ( count($new_files) > 0 ) {

          $image_path = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "assets" .
            DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "usr"; 

          // <id>,,,<file name>,,,<caption>,,,<file type>,,,<size>,<pos>##<id>,,,<file name>,,,<main> ...
          $one_file = explode(',,,', $new_files_info[0]);
          $fields = count($one_file);

          $preup_file_id = $fields > 0 ? $one_file[0] : 0;
          !is_numeric($preup_file_id) && $preup_file_id = 0;

          $file_name = $fields > 1 ? $one_file[1] : "";
          $file_size = $fields > 4 ? $one_file[4] : 0;
          !is_numeric($file_size) && $file_size = 0;

          if ( $preup_file_id > 0 && strlen($file_name) > 0 ) {

            $file_ext = strtolower(substr(strrchr($file_name, '.'), 1, 10));

            // is it an image file ?
            $is_image = 0;
            $this->valid_upload_extension($file_ext, array("jpeg", "jpg", "png", "gif")) && $is_image = 1;

            if ( $is_image > 0 ) {
              // only images are allowed here

              $preup_file_name = SITE_ROOT  . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR .
                "temp" . DIRECTORY_SEPARATOR . "preups" . DIRECTORY_SEPARATOR . "raw" .
                DIRECTORY_SEPARATOR . $preup_file_id . "." . $file_ext;

              // resize the image
              $image_name = $image_path . DIRECTORY_SEPARATOR . $user_id . "." . $file_ext;

              $img = $this->resize_image($preup_file_name, 'profile', 100, $image_name);
              $error = $img["error"]["message"];

              if ( strlen($error) < 1 ) {
                // set image url
                $data["image_url"] = "/assets/images/usr/" . $user_id . "." . $file_ext;

                // attempt to delete the raw file
                file_exists($preup_file_name) && !unlink($preup_file_name) && $info = "Could not delete raw pre-upload file : " . $preup_file_name;
              }
            }
            else {
              $error = "Only image file types are allowed";
            }

          } // pre_up file_id > 0

        } // count new_files > 0

      } // use_current < 1

      if ( strlen($error) < 1 ) {
        $checks = " and organisation_id = " . $org_id;

        $res = $this->update('user_details', $data, $user_id, true, 'user_id', $checks);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;
      }

    } // len(error)

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "user_id" => $user_id,
          "organisation_id" => $org_id
        ),
      'info' => $info,
    );

    $this->template->assign("add_user_details_result", $result);

    strlen($info) > 0 && $this->template->flash($info, "alert alert-success");
    strlen($error) > 0 && $this->template->flash($error, "alert alert-warning");
    
    return ($result);

  }

  public function show ($user_id = null) {
    // show user - defaults to logged in user

    $debug = "";
    $error = "";

    $user = array();
    $org_id = 0;

    is_null($user_id) && $user_id = 0;
    $creator_id = $this->template->get_session_value('user_id', 0);
    $user_id < 1 && $user_id = $creator_id;

    $rights = $this->template->get_session_value('rights', 0);

    $user_id < 1 && $error = "There is no user to show";
    strlen($error) < 1 && $rights < 80 && $user_id < 100 && $user_id != $creator_id && $error = "You do not have sufficient rights to access this function";

    if ( strlen($error) < 1 ) {

      $query = "select user_details.*, organisations.organisation " .
        " from user_details left join organisations on user_details.organisation_id = organisations.id " .
        " where user_details.user_id > 0 and user_details.user_id = " . $user_id . " and ( (" . $rights . " >= 80) or " .
        " (user_details.user_id = " . $user_id . ") or " .
        " (user_details.status in (10)) ); ";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $user = $res[0][0];

        if ( count($user) > 0 ) {
          $user["can_edit"] = $rights >= 80 || $creator_id == $user["user_id"] ? 1 : 0;
        }

      }
    }

    // protected data
    $protected_data = $user_id . "," . $org_id;
    $protected = array(
      "_p1_" => $protected_data,
      "_p2_" => $this->codify($protected_data)
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "user" => $user,
        ),
      'info' => "",
      'protected' => $protected
    );

    // get menu and reset this cat in session
    $this->get_menu();

    $this->template->assign("show_user", $result);
    
    return ($result);

  }










  
  private function make_verify_message ($token = null, $name = null) {
    // make a pwd reset message whose key is token
    // token & name are assumed validated already

    $url = $this->get_site_url();
    $verify = $url . "/verify";

    // set cookie OR pass token in URL
    $this->unset_cookie(null);
    $this->set_cookie(null, $token, null) || $verify .= "/?t=" . urlencode($token);

    $message = "Hi " . $name . ",\r\n\r\n";
    $message .= "Thank you for using Simple Tracker.\r\n\r\n";
    $message .= "To validate your email or reset your password click on the link below. If needed, cut and paste the whole URL into your browser.\r\n";
    $message .= "<a href='" . $verify . "'>" . $verify . "</a>\r\n\r\n";
    $message .= "NOTE\r\nYou must click the 'verify' link from the SAME device that you used to register with. If you are on a different device " .
      "<a href='" . $url . "/forgot'>click here</a> to get a password reset and verification email sent to you.\r\n";
    $message .= "\r\nSupport\r\nSimple Tracker\r\n<a href='" . $url . "'>" . $url . "</a>\r\n";
 
    return ($message);
  }









}


?>
