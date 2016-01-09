<?php
// session model

class Session_Model extends Base_model {

  public function save_session_info() {
    // save this infor for later; if user is doing certain things later, a 'proper' visit log is made in the DB
    // activities requiring visit logs are usually ones that require data writes, update, delete

    $method = $_SERVER["REQUEST_METHOD"];
    $request_url = $_SERVER["REQUEST_URI"];
    $host = $_SERVER['HTTP_HOST'];

    // these may not always be present
    $referrer_url = "";
    isset($_SERVER["HTTP_REFERER"]) && $referrer_url = $_SERVER["HTTP_REFERER"];

    $agent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";

    // add visits data to session
    $_SESSION['method'] = $method;
    $_SESSION['request_uri'] = $request_url;
    $_SESSION['referrer_url'] = $referrer_url;
    $_SESSION['agent'] = $agent;
    $_SESSION['host'] = $host;

    // get organisation from host OR set default organisation
    $this->get_organisation($host);

    // attempt to log in user automatically
    $res = $this->getuser_from_token();
    if ( !is_null($res) && isset($res["data"]) ) {
      $user = $res["data"];

      if ( count($user) > 0 && $user["token_status"] == 10 && $user["status"] == 10 ) {

        $res = $this->login_user($user);

        if ( isset($res["info"]) ) {
          $this->template->flash($res["info"], "alert alert-success");
        } // login_user
      } // user found & active - for auto login
    } // user found

  }

  public function add_visit() {
    // add visit and place visit_id in session
    // some of the visits data will have been cached in session
    $error = "";
    $debug = "";
    $visit = null;

    if ( !isset($_SESSION['visit_id']) && $this->is_robot < 1 ) {

      //$method = $_SERVER["REQUEST_METHOD"];
      $method = $_SESSION['method'];

      //$request_url = $_SERVER["REQUEST_URI"];
      $request_url = $_SESSION['request_uri'];

      //$agent = $_SERVER["HTTP_USER_AGENT"];
      $agent = $_SESSION['agent'];

      $host = $_SESSION['host'];

      //isset($_SERVER["HTTP_REFERER"]) && $referrer_url = $_SERVER["HTTP_REFERER"];
      $referrer_url = $_SESSION['referrer_url'];

      $server_ip = $_SERVER["LOCAL_ADDR"];
      $remote_ip = $_SERVER["REMOTE_ADDR"];
      $remote_host = $_SERVER["REMOTE_HOST"];

      // if user had logged in automatically, get them from here
      $user_id = $this->template->get_session_value('user_id', 0);

      $data = array(
        "user_id" => $user_id,
        "method" => $method,
        "request_url" => substr($request_url, 0, 199),
        "user_agent" => substr($agent, 0, 199),
        "server_ip" => $server_ip,
        "remote_ip" => $remote_ip,
        "remote_host" => substr($remote_host, 0, 199),
        "referrer_url" => substr($referrer_url, 0, 199),
      );

      $res = $this->insert('visits', $data, true, false);
      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 && isset($res[0]) && count($res[0]) >= 0 ) {

        $visit = $res[0][0];
        $_SESSION['visit_id'] = $visit['id'];

        unset($_SESSION['method']);
        unset($_SESSION['request_uri']);
        unset($_SESSION['referrer_url']);
        unset($_SESSION['agent']);
      }

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $visit,
      'info' => ""
    );

    return ($result);
  }

  public function login() {
    // login or add user, then log in
    // user details may come from different poviders, etc facebook
    // returns details for a session

    $user = null;
    $error = '';
    $info = '';
    $debug = '';

    $create = 0;

    $provider = 'self'; 
    $user_name = '';
    $password = '';

    isset($_POST['provider']) && $provider = $_POST['provider'];
    isset($_POST['user_name']) && $user_name = $_POST['user_name'];
    isset($_POST['password']) && $password = $_POST['password'];

    isset($_POST['create']) && $create = $_POST['create'];

    // login requests from providers other than 'self' have already been authenticated
    // if 'self', then user_name and password are required

    !$this->is_provider($provider) && $error = 'Invalid login request';
    strlen($error) < 1 && strlen($user_name) < 1 && $error = 'Please, enter a user name or an email address';
    $provider == 'self' && strlen($error) < 1 && strlen($password) < 1 && $error = 'Please, enter a password';

    if ( strlen($error) < 1 ) {

      $u = new User_model($this->template, $this->query_string);
      $res = $u->find_user(null, $provider, $user_name); 

      $error = $res["errors"][0]["message"];
      $info = $res["info"];
      DEBUG > 0 && $debug = "login > find_user: " . $res["errors"][0]["debug"];

      strlen($error) < 1 && $user = $res["data"];

      // users authenticated from other sites are added automatically
      // for 'self', the 'create' POST param must be set to create an account
      if ( strlen($error) < 1 &&
           (is_null($user) || count($user) < 1) &&
           ($create == 1 || $provider != 'self')
          )
      {
        // visit log is required for this
        $res = $u->add_user($provider, $user_name, $password);

        $error = $res["errors"][0]["message"];
        $info .= strlen($info) > 0 ? "; " . $res["info"] : $res["info"];
        DEBUG > 0 && $debug .= "; login > add_user: " . $res["errors"][0]["debug"];

        strlen($error) < 1 && $user = $res["data"];
      }
    }

    if ( strlen($error) < 1 && count($user) > 0 ) {

      // if provider is self, then autenticate password
      if ( $provider == 'self' ) {
        !$this->is_equal($password, $user["password"]) && $error = "Sorry the user name and password supplied did not match"; 
      }

      if ( strlen($error) < 1 ) {
        $res = $this->login_user($user);
        $error = $res["errors"][0]["message"];
        $info .= strlen($info) > 0 ? "; " . $res["info"] : $res["info"];
      }
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $user,
      'info' => $info
    );

    strlen($info) > 0 && $this->template->flash($info, "alert alert-success");

    $this->template->assign("login", $result);

    return (true);
  }

  public function logout () {
    // unset remember the cookie if any 
    $this->unset_cookie(null);

    // destroy session
    session_unset();

    $info = "Thank you for visiting - please, come again soon !";
    $this->template->flash($info, "alert alert-success");

    $result = array (
      'errors' => array (
          array('message' => null, 'debug' => null)
        ),
      'data' => null,
      'info' => $info
    );

    return($result);
  }

  public function reset() {
    // send a pwd reset msg; only valid for 'self' registrations
    $debug = "";
    $error = "";
    $info = "";

    $user = array();

    $url = $this->get_site_url();
    $u = new User_model($this->template, $this->query_string);

    $email = "";
    isset($_POST['email']) && $email = $_POST['email'];

    strlen($email) < 1 && $error = "Please enter your email address";

    if ( strlen($error) < 1 && !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
      $error = "Please enter a valid email address";
    }

    if ( strlen($error) < 1 ) {

      $token = $this->codify($email);

      $res = $u->find_user(null, 'self', $email); 

      if ( isset($res["data"]) && !is_null($res["data"]) ) {

        $error = $res["errors"][0]["message"];
        $user = $res["data"];

        DEBUG > 0 && $debug = $res["errors"][0]["debug"];

        strlen($error) < 1 && count($user) < 1 && $error = "Sorry the email provided was not found";
      }
      else {
        $error = "Your account could not be found.";
      } 
    }

    if ( strlen($error) < 1 ) {
      // user was found
      $res = $u->set_user_token ($user["id"], $token, 11);

      $error = $res["errors"][0]["message"];
      DEBUG > 0 && $debug = $res["errors"][0]["debug"];
    }

    if ( strlen($error) < 1 ) {
      // token set - now send email
      $verify = $url . "/verify";

      // set cookie OR pass token in URL
      $this->unset_cookie(null);
      $this->set_cookie(null, $token, null) || $verify .= "/?t=" . urlencode($token);

      $subject = "Simple Tracker";

      $message = "Thank your for using Simple Tracker\r\n\r\n";
      $message .= "Please click on the link below to reset your password. " . 
        "If the link is not clickable copy and paste it directly into your browser.\r\n\r\n";
      $message .= "<a href='" . $verify . "'>" . $verify . "</a>";
      $message .= "\r\n\r\nNOTE\r\nYou must click the 'verify' link from the SAME device that you requested the password reset email from.\r\n\r\n";
      $message .= "Support\r\nSimple Tracker\r\n" . $url . "\r\n";

      $mailer = new Mailer_Lib();

      $ret = $mailer->swiftmail($this->mail[$this->mailer], $email, $subject, $message, $this->mail['support'], $this->mail['support_name'], $this->admin_email, null);

      if ( $ret ) {

        $info = "Thank you. " .
          "Information has been sent to " . $this->template->escape_string($email) .
          " on how to reset your password";
      }
      else {

        $error = "There was a problem sending an email to " .
          $this->template->escape_string($email);
      }

    } // send email

    $result = array(
      "errors" => array(
          array("message" => $error, "debug" => $debug)
        ),
      "data" => null,
      "info" => $info
    );

    strlen($error) < 1 && strlen($info) > 0 && $this->template->flash($info, "alert alert-success");

    $this->template->assign("reset_result", $result);

    return ($result);
  }

  public function verify () {
    // some one has come in on the verify URL
    // find user from token and show email reset form if token status = 11; acknowledge if token status = 10 (ie after reg)

    $debug = "";
    $error = "";
    $info = "";

    $user = null;
    $protected = array();

    $res = $this->getuser_from_token();

    is_null($res) && $error = "Technical error - sorry there was a problem finding your account. Please inform support.";

    if ( strlen($error) < 1 ) {
      $error = $res["errors"][0]["message"];
      $debug = $res["errors"][0]["debug"];
      $user = $res["data"];
      $info = $res["info"];

      if ( is_null($user) || count($user) < 1 ) {
        $error = "Sorry your account details were not found. " .
          "Please make sure you clicked from the same device you requested the password reset from";

      }
    }

    if ( strlen($error) < 1 ) {

      // protected data
      $protected_data = $user["user_id"] . "," . $user["status"] . "," . $user["token_status"] . "," . $user["token"];
      $protected = array(
        "_p1_" => $protected_data,
        "_p2_" => $this->codify($protected_data)
      );

      $user_details_data = null;
      $user_tokens_data = null;

      switch ( $user["token_status"] ) {
        case 11: // pwd reset was issued
          //$user_tokens_data["status"] = 10;

          break;

        case 10: // user has just been registered
          $user_details_data["status"] = 10;
          $info = "Thank you - your registration is verified and now active";

          break;

      } // switch

      if ( !is_null($user_details_data) ) {
        $res = $this->update('user_details', $user_details_data, $user["user_id"], true, 'user_id', null);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;
      }

      if ( strlen($error) < 1 && !is_null($user_tokens_data) ) {
        $check = " and user_id = " . $user_id;

        // TO DO: below not efficient - use a straight query
        $res = $this->update('user_tokens', $user_tokens_data, $user["user_id"], false, 'user_id', null);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;

      }

   }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "status" => $user["status"],
          "token_status" => $user["token_status"]
        ),
      'info' => $info,
      'protected' => $protected
    );

    $this->get_menu();

    $this->template->assign("verify_result", $result);
    
    return($result);
  }




  public function get_organisation ($domain = null) {
    // on first landing to site, get organisation
    is_null($domain) && $domain = "";

    // use default if domain organisation is not found
    $org_id = 9;
    $org = 'Simple Tracker - default';

    $query = "select id, organisation from organisations where domain = '" . $domain . "'; " .
        "select id, organisation from organisations where id = 9;";
    
    $res = $this->query($query);

    if ( strlen($this->db_error) < 1 ) {

      if ( isset($res[0][0]["id"]) ) {
        $org_id = $res[0][0]["id"];
        $org = $res[0][0]["organisation"];
      }
      else {
        if ( isset($res[1][0]["id"]) ) {
          $org_id = $res[1][0]["id"];
          $org = $res[1][0]["organisation"];
        }
      }

    }

    $_SESSION['org_id'] = $org_id; 
    $_SESSION['org'] = $org; 

    return (true);
  } 

  public function update_password () {
    // change status of a post

    $debug = "";
    $error = "";
    $info = "";

    $password1 = "";
    $password2 = "";

    $user_id = 0;
    $status = -1;
    $token_status = -1;
    $token = '';

    isset($_POST["password1"]) && $password1 = $_POST["password1"];
    isset($_POST["password2"]) && $password2 = $_POST["password2"];

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($user_id, $status, $token_status, $token) = explode(",", $p1, 4);

      $user_id < 1 && $error = "Sorry your account details could not be deduced";
    }

    strlen($error) < 1 && !array_key_exists($status, array("10" => 1, "11" => 1)) && $error = "Sorry invalid account status specified";
    strlen($error) < 1 && strlen($password1) < 1 && $error = "Please enter a password";
    strlen($error) < 1 && $password1 != $password2 && $error = "The passwords you entered do not match";

    if ( strlen($error) < 1 ) {

      $data = array (
        "password" => $this->codify($password1),
      );

      $res = $this->update('users', $data, $user_id, false, 'id', null);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

    }

    if ( strlen($error) < 1 && $status == 11 ) {
      // activating account
      $data = array (
        "status" => 10,
      );

      $res = $this->update('user_details', $data, $user_id, true, 'user_id', null);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;
    }

    if ( strlen($error) < 1 ) {

      // if tag status is 11, change it to 10 to enable auto logins
      if ( $token_status == 11  && strlen($token) > 0 ) {
          $data = array (
            "status" => 10,
          );

          $check = " and user_id = " . $user_id;
          $res = $this->update('user_tokens', $data, $token, false, 'token', $check);
      }

      $info = "Thank you - your account details have been saved";
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "category_id" => $category_id,
          "sts" => $status,
          "post_id" => $post_id,
        ),
      'info' => $info,
    );

    strlen($info) > 0 && $this->template->flash($info, "alert alert-info");

    $this->template->assign("update_password_result", $result);
    
    return ($result);

  }













  private function login_user ( $res = array() ) {
    // place this user's details into session if they are active- log them in
    is_null ($res) && $res = array();

    $error = count($res) > 0 ? "" : "System error - there are no user details to process";
    $info = "";

    if ( strlen($error) < 1 ) {

      switch ( $res["status"] ) {
        case 10:
          break;

        case 11:
          //$error = "Your account has yet to be validated.";
          $info = "Your account has yet to be validated.";
          break;

        case 3:
          $error = "Your account has been suspended - please contact the site administrator";
          break;

        default:
          $error = "Your account is not active - please contact the site administrator";
          break;

      }
    }

    if ( strlen($error) < 1 && strlen($info) < 1 ) {

      $_SESSION['user_id'] = $res['id'];

      //$_SESSION['prov_id'] = $res['user_name'];

      $_SESSION['name'] = $res['name'];
      $_SESSION['rights'] = $res['rights'];
      $_SESSION['user_org_id'] = $res['organisation_id'];

      $_SESSION['image'] = $res['image_url'];

      if ( $res['rights'] < 80 ) {
        // if not admin, they can only log into their org
        $_SESSION["org_id"] = $res["organisation_id"];
        $_SESSION["org"] = $res["organisation"];
      }

      $this->ensure_visit();
      
      // update last login date/time
      $data = array (
          "last_logged_in_at" => $this->iso_datetime_now(),
          "update_visit_id" => $this->template->get_session_value('visit_id', 0)
        );

      $r = $this->update('user_details', $data, $res['id'], false, 'user_id');

      $info = "Hello " . $_SESSION['name'] . ", welcome to [Simple Tracker] !";
      //$error = $this->db_error;
      //$info = $this->db_debug;
    }

    $result = array(
      "errors" => array(
          array("message" => $error, "debug" => null)
        ),
      "data" => null,
      "info" => $info
    );

    return ($result);
  }





  private function getuser_from_token () {
    // attempt to get user from cookie / token

    $debug = "";
    $error = "";

    $user = array();

    $token = "";
    isset($_COOKIE['trkr_rst']) && $token = $_COOKIE['trkr_rst'];
  
    if ( strlen($token) > 0 ) {
      $query = "select user_tokens.token, user_tokens.status as token_status, " .
        " user_details.user_id as id, user_details.* from " .
        " user_tokens inner join user_details on user_tokens.user_id = user_details.user_id " .
        " where user_tokens.token = '" . $token . "'";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $user = $res[0][0];
      }

    }

    $result = array(
      "errors" => array(
          array("message" => $error, "debug" => null)
        ),
      "data" => $user,
      "info" => ""
    );

    return ($result);
  }





}


?>
