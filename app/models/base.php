<?php

// base model - implements functionality common to all models
// query string variables are extracted with prefix QS

abstract class Base_Model {

  public $site_name = "";
  public $site_url = "";
  public $site_domain = "";

  public $mailer = 'default';
  public $mail = array();
  public $admin_email = "admin@tracker.co.uk";
  public $admin_name = "Tracker Admin";

  public $dblibrary = "";

  protected $clean_str = ""; // clean string pattern
  protected $robot_str = ""; // regexp to look for spiders
  protected $is_robot = 0; // this is set to 1 if the visit is from a robot

  protected $link = null;
  protected $db = null;

  protected $template = null;
  protected $query_string = array();

  protected $upload_file_extensions = array(); // acceptable file extensions
  protected $max_upload_file_size = 0;

  // error states from DB
  protected $db_error_id = null; // assume not set ie don't know
  protected $db_error = null; // assume not set ie don't know
  protected $db_debug = "";
  protected $db_resultsets = 0; // assume no results 

  // image sizes
  protected $image_sizes = array();

  private $server = null;
  private $database = null;
  private $username = null;
  private $password = null;

  // third party STARTS
  protected $providers = array();
  
  protected $google = array(); // holds google api and other details


  // third party ENDS

  public function __construct(Template_Lib $tmpl = null, $query_s = null) {

    $db_tokens = array(); // db connection tokens

  	!is_null($tmpl) && $this->template = $tmpl;
  	!is_null($query_s) && is_array($query_s) && $this->query_string = $query_s; 

    // read values from config file
    $file = SITE_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.php";
    if (is_readable($file)) {
      $config = include($file);

      if (array_key_exists('setup', $config)) {

        if ( array_key_exists('sys_admin', $config['setup']) ) {
          array_key_exists('email', $config['setup']["sys_admin"]) && $this->admin_email = $config['setup']["sys_admin"]["email"];
          array_key_exists('name', $config['setup']["sys_admin"]) && $this->admin_name = $config['setup']["sys_admin"]["name"];
        }

      }

      if (array_key_exists('site', $config)) {
      	array_key_exists('name', $config['site']) && $this->site_name = $config['site']['name'];
        array_key_exists('url', $config['site']) && $this->site_url = $config['site']['url'];
        array_key_exists('domain', $config['site']) && $this->site_domain = $config['site']['domain'];
        array_key_exists('dblibrary', $config['site']) && $this->dblibrary = $config['site']['dblibrary'];
        array_key_exists('mailer', $config['site']) && $this->mailer = $config['site']['mailer'];

        array_key_exists('mode', $config['site']) && $this->template->configs["mode"] = $config['site']['mode'];
      }

      if (array_key_exists('mail', $config)) {
      	$this->mail = $config['mail'];
      }

      // http headers (for security) & allowed html ?
      array_key_exists('http_headers', $config) && $this->template->configs["http_headers"] = $config['http_headers'];
      array_key_exists('html_tags', $config) && $this->template->configs["html_tags"] = $config['html_tags'];
      array_key_exists('modes', $config) && $this->template->configs["modes"] = $config['modes'];

      // buffer ?
      array_key_exists('buffer', $config) && $this->template->buffer = $config['buffer'];

      // clearn string pattern ?
      array_key_exists('clean_str', $config) && $this->clean_str = $config['clean_str'];

      // robots string pattern ?
      array_key_exists('robot_str', $config) && $this->robot_str = $config['robot_str'];

      // image sizes ?
      array_key_exists('image_sizes', $config) && $this->image_sizes = $config['image_sizes'];

      // db access tokens
      if (array_key_exists('db', $config)) {
      	array_key_exists($this->dblibrary, $config['db']) && $db_tokens = $config['db'][$this->dblibrary];

        array_key_exists('server', $db_tokens) && $this->server = $db_tokens['server'];
        array_key_exists('database', $db_tokens) && $this->database = $db_tokens['database'];
        array_key_exists('username', $db_tokens) && $this->username = $db_tokens['username'];
        array_key_exists('password', $db_tokens) && $this->password = $db_tokens['password'];

        $this->connect();

        //echo "<p>This is connected to : " . $this->dblibrary . "</p>";
      }

      array_key_exists('upload_file_extensions', $config) && $this->upload_file_extensions = $config['upload_file_extensions'];
      array_key_exists('max_upload_file_size', $config) && $this->max_upload_file_size = $config['max_upload_file_size'];

      // third party ?
      if ( array_key_exists('providers', $config) ) {
        $this->providers = $config['providers'];

        foreach (array_keys($this->providers) as $p) {
          $this->template->configs["providers"][$p] = 1;
        }
      }

      array_key_exists('google', $config) && $this->google = $config['google'];

    } // file is readable

    // was this visit from a robot ?
    $this->is_robot = $this->is_robot();

  } // construct

  private function connect() {

    switch ($this->dblibrary) {

      case "pdo_sqlsrv":
        $dsn = 'sqlsrv:server=' . $this->server . ';database=' . $this->database;

        try {
          $this->link = new PDO ( $dsn, $this->username, $this->password );
          $this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        }
        catch (PDOException $e) {
          $msg = $e->getMessage();
          throw new Exception($msg);
        }
        break;

      case "sqlsrv":

        $info = array (
            "UID" => $this->username,
            "PWD" => $this->password,
            "Database" => $this->database,
            "ReturnDatesAsStrings" => true,
          );

        $this->link = sqlsrv_connect( $this->server, $info);

        if ( !$this->link ) {
          $msg = "Could not connect to server - sqlsrv";

          $errs = sqlsrv_errors(SQLSRV_ERR_ERRORS);
          if ( $errs != null ) {
            $msg = "";
            foreach ($errs as $item) {
              $msg .= $item['code'] . " : " . $item['message'] . "; ";
            }
          }

          throw new Exception($msg);
        }
        break;

      case "mysqli":

        $this->link = mysqli_connect( $this->server, $this->username, $this->password, $this->database );

        if ( !$this->link ) {
          $msg = 'Could not connect through driver mysqli : ' .
            mysqli_connect_error();

          throw new Exception($msg);
        }
        break;

      case "pdo_mysql":

        $dsn = 'mysql:host=' . $this->server . ';dbname=' . $this->database;
        
        try {
          $this->link = new PDO ( $dsn, $this->username, $this->password );
          $this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        }

        catch (PDOException $e) {
          $msg = $e->getMessage();
          throw new Exception($msg);
        }
        break;

      default:
        throw new Exception('Unrecognised driver');
        break;

    }
  } 



  public function __wakeup() {
  	$this->connect();
  } 

  public function query($query) {
    // run a query as per the specified (active) driver

    $result = array(); // result will be an array of rowsets
    $this->db_error = "";

    DEBUG > 0 && $this->db_debug = $query;

    $this->link || $this->connect();

    switch ($this->dblibrary) {

      case 'pdo_sqlsrv':
        try {
          $result = $this->pdo_query($query);
        }
        catch (Exception $e) {
          $this->db_error = "DB error : " . $e->getMessage();
        }

        break;

      case 'sqlsrv':
        try {
          $result = $this->sqlsrv_query($query);
        }

        catch (Exception $e) {
          $this->db_error = "DB error : " . $e->getMessage();
        }

        break;

      case 'mysqli':
        try {
          $result = $this->mysqli_query($query);
        }

        catch (Exception $e) {
          $this->db_error = "DB error : " . $e->getMessage();
        }

        break;



    } // switch
    
    return $result;
  }



  public function pdo_exec($query) {
    // always returns number of rows affected
    // NOT WORKING
    $this->link || $this->connect();

    $res = $this->link->exec($query);
    $last_id = $this->link->lastInsertId();

    $result = array(
        "rows_affected" => 10,
        "last_id" => $last_id,
      );

    return $result;
  }



  public function quote_db_string($var = '') {
    // escapes ' by adding another ' to it
    $res = str_replace("'", "''", $var);

    return $res;
  }

  public function is_provider($prov = null) {
    // is $prov a providers ?
    return !is_null($prov) && strlen($prov) > 0 && count($this->providers) > 0 && array_key_exists($prov, $this->providers);
  }

  public function check_right($right = null) {
    // check this right for the logged in user as follows:
    // if right is not presented, fail; if user not logged in fail; if no org fail
    // right 80 => super user; right 30 => org admin
    $error = is_null($right) ? "Sorry, invalid right specified" : "";
    strlen($error) < 1 && !isset($_SESSION["user_id"]) && $error = "Please, log in first";
    strlen($error) < 1 && !isset($_SESSION["org_id"]) && $error = "Incorrect use - a user must always have an organisation";
    strlen($error) < 1 && !isset($_SESSION["rights"]) && $error = "Incorrect use - rights are not defined for this user";
    strlen($error) < 1 && $_SESSION["rights"] < $right && $error = "Sorry you do not have sufficient rights to access this resource";

    if ( strlen($error) < 1 && $right < 80 ) {
      // org admin
      $query = "select rights from user_details where user_id = " . $_SESSION["user_id"] .
          " and organisation_id = " . $_SESSION["org_id"] .
          " and rights >= " . $right;
      $res = $this->query($query);

      $error = "Sorry you not have sufficient right to manage this resource for this organisation";
      if ( strlen($this->db_error) < 1 && isset($res[0][0]["rights"]) ) {
        $error = "";
      }
    }

    $result = array (
        "errors" => array (
          array("message" => $error, "debug" => null)
        ),
        "info" => "",
        "data" => null
      );

    return ($result);
  }



  // experimental SIMPLE CRUD functions - START
  // it will get too complicated if trying to account for too many variations

  public function insert($table = null, $data = null, $last_id = false, $std = false, $id_field_name = null) {
    // accepts table names and a hast of field => value pairs

    $query = '';

    switch ($this->dblibrary) {

      case 'pdo_sqlsrv':
        $query = $this->make_insert_mssql($table, $data, $last_id, $std, $id_field_name);
        break;

      case 'mysqli':
        $query = $this->make_insert_mysqli($table, $data, $last_id, $std, $id_field_name);
        break;
    }

    return $this->query($query);
  }

  public function update($table = null, $data = null, $id = null, $std = false, $id_field_name = null, $checks = null, $append = null) {
    // accepts table names and a hast of field => value pairs

    $query = '';

    switch ($this->dblibrary) {

      case 'pdo_sqlsrv':
        $query = $this->make_update_mssql($table, $data, $id, $std, $id_field_name, $checks, $append);
        break;

      case 'mysqli':
        $query = $this->make_update_mysqli($table, $data, $id, $std, $id_field_name, $checks, $append);
        break;
    }

    return $this->query($query);
  }

  public function delete($table = null, $id = null, $id_field_name = null, $checks = null) {

    $query = '';

    switch ($this->dblibrary) {

      case 'pdo_sqlsrv':
        $query = $this->make_delete_mssql($table, $id, $id_field_name, $checks);
        break;

      case 'mysqli':
        $query = $this->make_delete_mysqli($table, $id, $id_field_name, $checks);
        break;
    }

    return $this->query($query);
  }

  public function get($table = null, $id = null, $id_field_name = null, $checks = null, $order = null, $limit = null) {
    // SIMPLE select from a single table

    $query = '';

    switch ($this->dblibrary) {

      case 'pdo_sqlsrv':
        $query = $this->make_get_generic($table, $id, $id_field_name, $checks, $order, $limit);
        break;

      case 'mysqli':
        $query = $this->make_get_generic($table, $id, $id_field_name, $checks, $order, $limit);
        break;
    }

    return $this->query($query);
  }

  public function add_limit_clause ($sql = null, $limit = null) {
    // given an sql statement, add a limit ( top x) clause to it

    $query = '';

    switch ($this->dblibrary) {

      case 'pdo_sqlsrv':
        $query = $this->add_limit_clause_mssql($sql, $limit);
        break;

      case 'mysqli':
        $query = $this->add_limit_clause_mysql($sql, $limit);
        break;
 
    }

    return ($query);
  }



  // experimental SIMPLE CRUD functions - END




  public function valid_upload_extension($ext = null, $extensions = null) {
    // valid file extensions for uploading
    is_null($extensions) && $extensions = $this->upload_file_extensions;

    $res = $ext && strlen($ext) > 0 && in_array($ext, $extensions);

    return ($res);
  }

  public function get_helper_objects () {
    // get dynamic helper objects - currently status_codes and priorities

    $error = "";
    $debug = "";

    $query = "select * from status_codes; " .
      "select * from priorities;";

    $res = $this->query($query);
    $error = $this->db_error;
    DEBUG > 0 && $debug = $this->db_debug;

    $status_codes = array();
    $priorities = array();

    if ( strlen($error) < 1 ) {

      $object = isset($res[0]) ? $res[0] : array(); // status codes
      if ( count($object) > 0 ) {
        foreach ($object as $item) {
          $status_codes[$item["id"]] = array(
            $item["code"],
            $item["description"],
            $item["icon"],
            $item["class"],
          );
        }

        $this->template->app_helper->status_codes = $status_codes;
      }

      $object = isset($res[1]) ? $res[1] : array(); // priorities
      if ( count($object) > 0 ) {
        foreach ($object as $item) {
          $priorities[$item["id"]] = array(
            $item["priority"],
            $item["description"],
            $item["icon"],
            $item["class"],
          );
        }

        $this->template->app_helper->priorities = $priorities;
      }

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "status_codes" => $status_codes,
          "priorities" => $priorities,
        ),
      'info' => "",
      'protected' => ""
    );

    return ($result);
  }

  public function get_image_sizes ($image_type = null) {
    // get sizes configured for this type of image - return 0, 0 if not found
    is_null($image_type) && $image_type = "";

    $sizes = array("0", "0");
    strlen($image_type) > 0 && array_key_exists($image_type, $this->image_sizes) && count($this->image_sizes[$image_type]) > 1 && $sizes = $this->image_sizes[$image_type];

    return ($sizes);
  }

  public function resize_image ($source = "", $image_type = "", $quality = 100, $destination = "") {
    // resize the source image and save it into destination
    // image_type is assumed defined in image_sizes config variable
    $error = "";
    $width = 0;
    $height = 0;
    $sizes = array("0", "0");

    strlen($source) > 0 || $error = "Source image file is required";
    strlen($error) < 1 && strlen($destination) < 1 && $error = "Destination image file name is required";
    strlen($error) < 1 && strlen($image_type) < 1 && $error = "Image type (eg thumb or item_image) is required";

    if ( strlen($error) < 1 ) {
      $sizes = $this->get_image_sizes($image_type);
      $sizes[0] > 0 || $sizes[1] > 0 || $error = "Destination file sizes could not be found - please, check the image type specified";
    }

    if ( strlen($error) < 1 ) {
      $gd2 = new GD2_lib($source);

      // check if error occurred loading the source file
      $error = $gd2->error;
      strlen($error) < 1 && $gd2->resize($sizes[0], $sizes[1], $quality, $destination);

      // check if error occurred while resizing
      $error = $gd2->error;

    }
    
    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => '')
        ),
      'data' => null,
      'info' => ''
    );

    return ($result);

  }

  public function check_url ($url = '') {
    // validate this url and default protocol to http:// if none is passed; place (error) message in variable 'warning'
    $result = $url;

    // default protocol to http://
    $components = parse_url($url);
    array_key_exists('scheme', $components) && strlen($components['scheme']) > 0 || $result = "http://" . $result;

    if ( !filter_var($result, FILTER_VALIDATE_URL) ) {
      $result = "";
    }

    return($result);
  }

  public function get_site_url () {
    $protocol = $this->template->get_session_value('protocol', 'http');
    $url = $this->template->get_session_value('base_domain', '');
    strlen($url) < 1 && $url = $this->template->get_session_value('host', '');
    strlen($url) < 1 && $url = $this->site_url;

    strlen($url) > 0 && $url = $protocol . "://" . $url;

    return ($url);
  }


  public function clean_string($str = null, $replace = null) {
    // cleans this string as per the pattern set in config
    // returns the cleaned string
    $new_str = $str; // assume the function was not applied and return the original string

    is_null($replace) && $replace = "";

    strlen($this->clean_str) > 0 && $new_str = preg_replace($this->clean_str, $replace, $str);

    return ($new_str);
  }

  public function is_robot($str = null) {
    // see if this agent string is from a robot; returns 1 (true) or 0 (false)
    $return = 0; // assume it is not a robot

    // if string is not passed, get session's agent value
    is_null($str) && $str = $this->template->get_session_value('agent', '');

    if ( strlen($this->robot_str) > 0 && strlen($str) > 0 && preg_match($this->robot_str, $str) ) {
      $return = 1;
    }
    
    return ($return);
  }

  function iso_datetime_now () {
    // returns ISO timestamp now

    return date("Y-m-d H:i:s");
  }

  function ensure_visit () {
    // ensure visit is captured

    if ( !isset($_SESSION['visit_id']) ) {
      $u = new Session_model($this->template, $this->query_string);
      $u->add_visit(); 
    }

    return (true);
  }

  function get_menu () {
    // menu cats will be needed each time full page refresh is made

    $u = new Category_model($this->template, $this->query_string);
    $u->index(); 

    return (true);
  }

  public function unset_cookie ($name = null) {
    // removes the tracker cookie named trkr_rst
    is_null($name) && $name = 'trkr_rst';
    setcookie($name, "", time() - 3600);

    return (true);
  }

  public function set_cookie ($name = null, $value = null, $time = null) {
    // sets the tracker cookie named trkr_rst

    is_null($time) && $time = 86400*1; // default to 1 day
    is_null($name) && $name = 'trkr_rst';

    $result = setcookie($name, $value, time() + $time, "/");

    return ($result);
  }

  public function get_cookie ($name = null) {
    // get the tracker cookie named trkr_rst
    is_null($name) && $name = 'trkr_rst';

    $result = isset($_COOKIE[$name]) ? isset($_COOKIE[$name]) : null;

    return ($result);
  }

  public function codify ($data = null, $algo = null) {
    // this function may change in time

    is_null($algo) && $algo = PASSWORD_DEFAULT;
    !is_numeric($algo) && $algo = PASSWORD_DEFAULT;

    //return crypt($data, $salt);
    return password_hash($data, $algo);
  }

  public function is_equal ($codified = null, $raw_data = null) {
    // this function may change in time

    return crypt($codified, $raw_data) ==  $raw_data;
  }









  private function sqlsrv_query($query) {
    // run a query using the SQLSRV driver
    // always returns an associative array - SQLSRV_FETCH_ASSOC
    $result = array();

    $stmt = sqlsrv_query( $this->link, $query);

    if ($stmt) {

      do {
        $resultset = array();

        // get all rows of each set
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ) {
          $resultset[] = $row;
        }

        $result[] = $resultset;
        $this->db_resultsets = $this->db_resultsets + 1;
      }
      while ( sqlsrv_next_result($stmt) );

      sqlsrv_free_stmt($stmt);
    }

    return $result;
  }

  private function pdo_query($query) {
    // used for all pdo_ drivers
    // always returns an associative array - PDO::FETCH_ASSOC
    $result = array(); // result will be an array of rowsets
    
    $stmt = $this->link->query($query);

    if ($stmt) {

      do {
        $resultset = $stmt->fetchall(PDO::FETCH_ASSOC);
        $result[] = $resultset;
        $this->db_resultsets = $this->db_resultsets + 1;
      }
      while ( $stmt->nextRowset() );
    }

    $stmt = null;
    
    return $result;
  }


  private function mysqli_query($query) {
    // always returns an associative array
    $result = array(); // result will be an array of rowsets

    $rsets = $this->link->multi_query($query);

    $qry = 0;

    if ($rsets) {

      do {
        $qry += 1;

        // quit on error in any statement
        if ( $this->link->errno ) {
          $err = "Error on statement " . $qry . ": " . $this->link->error;
          throw new Exception ($err); 
          break;
        }

        $resultset = $this->link->store_result();

        if ( $resultset ) {
          $result[] = $resultset->fetch_all(MYSQLI_ASSOC);

          //$resultset->free_result();
          $resultset->free();

          $this->db_resultsets = $this->db_resultsets + 1;
        }

      }
      while ( $this->link->more_results() && $this->link->next_result() );
    }

    $rsets = null;

    return $result;
  }





  // Experimental CRUD stuff - starts

  private function make_insert_mssql($table = null, $data = null, $last_id = false, $std = false, $id_field_name = null) {
    // MS SQL server
    // accepts table names and a hast of field => value pairs
    // everything expected to go in as strings
    // params assumed validated for presence
    // if $last_id is passed, attempt to get the last row inserted, else retrieve empty row

    $fields = '(';
    $values = '(';

    is_null($last_id) && $last_id = false; // if not requested, don't retrive id / row just inserted
    is_null($std) && $std = true; // assume 'standard fields' exists
    is_null($id_field_name) && $id_field_name = 'id';

    foreach ($data as $k => $v) {
      $fields .= strlen($fields) > 1 ? ', ' . $k : $k; 

      $value = $this->quote_db_string($this->clean_string($v, ''));
      $values .= strlen($values) > 1 ? ", '" . $value . "'" : "'" . $value . "'";
    }

    $query = '';
    if ( strlen($fields) > 1 ) {

      if ( $std ) {
        $fields .= ", create_visit_id, created_by";
        $values .= ", " . $this->template->get_session_value("visit_id", 0) .
          ", " . $this->template->get_session_value("user_id", 0);
      }

      $query = 'set nocount on; ' .
          'insert into ' . $table . ' ' . $fields . ') values ' . $values . '); ';

      if ( $last_id ) {
        $query .= 'select * from ' . $table . ' where ' . $id_field_name . ' = ident_current(\'' . $table . '\'); ';
      }
      else {
        $query .= 'select * from ' . $table . ' where 1 = 2;';
      }
    }

    return ($query);
  }

  private function make_insert_mysqli($table = null, $data = null, $last_id = false, $std = false, $id_field_name = null) {
    // mysqli
    // accepts table names and a hast of field => value pairs
    // everything expected to go in as strings
    // params assumed validated for presence
    // if $last_id is passed, attempt to get the last row inserted, else retrieve empty row

    $fields = '(';
    $values = '(';

    is_null($last_id) && $last_id = false; // if not requested, don't retrive id / row just inserted
    is_null($std) && $std = true; // assume 'standard fields' exists
    is_null($id_field_name) && $id_field_name = 'id';

    foreach ($data as $k => $v) {
      $fields .= strlen($fields) > 1 ? ', ' . $k : $k; 

      $value = $this->quote_db_string($this->clean_string($v, ''));
      $values .= strlen($values) > 1 ? ", '" . $value . "'" : "'" . $value . "'";
    }

    $query = '';
    if ( strlen($fields) > 1 ) {

      if ( $std ) {
        $fields .= ", create_visit_id, created_by";
        $values .= ", " . $this->template->get_session_value("visit_id", 0) .
          ", " . $this->template->get_session_value("user_id", 0);
      }

      $query = 'insert into ' . $table . ' ' . $fields . ') values ' . $values . '); ';

      if ( $last_id ) {
        $query .= 'select * from ' . $table . ' where ' . $id_field_name . ' = last_insert_id(); ';
      }
      else {
        $query .= 'select * from ' . $table . ' where 1 = 2;';
      }

    }

    return ($query);
  }


  private function make_delete_mssql($table = null, $id = null, $id_field_name = null, $checks = null) {
    // MS SQL server
    // accepts table names and an ID, along with id field name
    // it is assumed the table has a primary key called 'id' if id_field_name is not specified
    // params assumed validated for presence
    // checks = additional checks for the where clause;
    // return an empty recordset after

    is_null($checks) && $checks = "";
    is_null($id) && $id = 0; 
    is_null($id_field_name) && $id_field_name = 'id';

    $where = "";
    $id > 0 && $where = " where " . $id_field_name . " = '" . $id .
      "' " . $checks . "; " ;

    $query = '';
    if ( strlen($where) > 1 ) {

      $query = "set nocount on; " .
          "delete from " . $table . $where . "; " .
          "select * from " . $table . " where 1 = 2;";
    }

    return ($query);
  }

  private function make_delete_mysqli($table = null, $id = null, $id_field_name = null, $checks = null) {
    // delete - mysqli
    // accepts table names and an ID, along with id field name
    // it is assumed the table has a primary key called 'id' if id_field_name is not specified
    // params assumed validated for presence
    // checks = additional checks for the where clause;
    // return an empty recordset after

    is_null($checks) && $checks = "";
    is_null($id) && $id = 0; 
    is_null($id_field_name) && $id_field_name = 'id';

    $where = "";
    $id > 0 && $where = " where " . $id_field_name . " = '" . $id .
      "' " . $checks . "; " ;

    $query = '';
    if ( strlen($where) > 1 ) {

      $query = "delete from " . $table . $where . "; " .
          "select * from " . $table . " where 1 = 2;";
    }

    return ($query);
  }



  private function make_update_mssql($table = null, $data = null, $id = null, $std = false, $id_field_name = null, $checks = null, $append = null) {
    // update statement - sql server (both drivers)
    // accepts table names and a hast of field => value pairs, along with ID of row to be updated
    // it is assumed the table has a primary key called 'id' if id_field_name is not specified
    // everything expected to go in as strings
    // params assumed validated for presence
    // checks : additional checks for the where clause eg 'and rights > 10'
    // append : a query to run after the update

    is_null($checks) && $checks = ''; 
    is_null($append) && $append = ''; 

    $set = '';
    is_null($std) && $std = true; // assume 'standard fields' exists
    is_null($id_field_name) && $id_field_name = 'id';

    foreach ($data as $k => $v) {
      $value = $this->quote_db_string($this->clean_string($v, ''));

      $set .= strlen($set) > 1 ? ', ' : ''; 
      $set .= $k . " = '" . $value . "'";
    }

    $query = '';
    if ( strlen($set) > 1 ) {

      $std && $set .= ", update_visit_id = " . $this->template->get_session_value('visit_id', 0) .
        ", updated_by = " . $this->template->get_session_value("user_id", 0) .
        ", updated_at = '" . $this->iso_datetime_now() . "'"; 

      $query = "set nocount on; " .
          "update " . $table . " set " . $set . " where " . $id_field_name . " = '" . $id .
          "' " . $checks . "; " .
          "select * from " . $table . " where " . $id_field_name . " = '" . $id . "'; ";

      strlen($append) > 0 && $query .= $append;
    }

    return ($query);
  }

  private function make_update_mysqli($table = null, $data = null, $id = null, $std = false, $id_field_name = null, $checks = null, $append = null) {
    // update statement - mysqli
    // accepts table names and a hast of field => value pairs, along with ID of row to be updated
    // it is assumed the table has a primary key called 'id' if id_field_name is not specified
    // everything expected to go in as strings
    // params assumed validated for presence
    // checks : additional checks for the where clause eg 'and rights > 10'
    // append : a query to run after the update

    is_null($checks) && $checks = ''; 
    is_null($append) && $append = ''; 

    $set = '';
    is_null($std) && $std = true; // assume 'standard fields' exists
    is_null($id_field_name) && $id_field_name = 'id';

    foreach ($data as $k => $v) {
      $value = $this->quote_db_string($this->clean_string($v, ''));

      $set .= strlen($set) > 1 ? ', ' : ''; 
      $set .= $k . " = '" . $value . "'";
    }

    $query = '';
    if ( strlen($set) > 1 ) {

      $std && $set .= ", update_visit_id = " . $this->template->get_session_value('visit_id', 0) .
        ", updated_by = " . $this->template->get_session_value("user_id", 0) .
        ", updated_at = '" . $this->iso_datetime_now() . "'"; 

      $query = "update " . $table . " set " . $set . " where " . $id_field_name . " = '" . $id .
          "' " . $checks . "; " .
          "select * from " . $table . " where " . $id_field_name . " = '" . $id . "'; ";

      strlen($append) > 0 && $query .= $append;
    }

    return ($query);
  }

  private function add_limit_clause_mssql ($sql = null, $limit = null) {
    // given an sql statement, add a limit ( top x) clause to it

    is_null($sql) && $sql = "";
    is_null($limit) && $limit = 0;
    !is_numeric($limit) && $limit = 0;

    $result = $sql;

    if ( $limit > 0 ) {
      $sql = preg_replace('/^\w*select/i', '', $sql);
      $result = "select top " . $limit . " " . $sql;

    }

    return ($result);
  }

  private function add_limit_clause_mysql ($sql = null, $limit = null) {
    // given an sql statement, add a limit x clause to it

    is_null($sql) && $sql = "";
    is_null($limit) && $limit = 0;
    !is_numeric($limit) && $limit = 0;

    $result = $sql;

    if ( $limit > 0 ) {
      $result .= " limit " . $limit . "; ";

    }

    return ($result);
  }



  private function make_get_generic($table = null, $id = null, $id_field_name = null, $checks = null) {
    // standard generic - SIMPLE select from a single table
    // accepts table names and an ID, along with id field name;
    // params assumed validated for presence
    // checks = additional checks for the where clause

    is_null($checks) && $checks = "";
    is_null($id) && $id = 0; 
    is_null($id_field_name) && $id_field_name = 'id';

    $where = "";
    $id != 0 && $where = " where " . $id_field_name . " = '" . $id .
      "' " . $checks;

    $query = "";
    if ( strlen($where) > 1 ) {

      $query = "select * from " . 
        $table . $where;

      $query .= ";";
    }

    return ($query);
  }


  // Experimental CRUD stuff - ends





}

?>
