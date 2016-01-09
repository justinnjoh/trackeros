<?php
// template engine - with following functionality:
// - render : ouput file (ie include the file or output the params array)
// - yield : return result of specified file in HTML / JSON, of its params array as is or JSON

class Template_Lib {

  // $layout - default layout is set from the router; 
  // it is the application layout file (template) in /app/views/layouts/
  public $layout = "application"; 

  // $yield - set from the controller (using the controller's set_view function) : FULL path is expected
  // it is the PARTIAL view to be 'included' from the layout file
  public $yield = null; 

  // $inject is a hash of content that can be injected in the layout; 
  // Each item to be injected is a KEY in the hash and the value holds the TYPE of injection
  // there are 2 types of injections, namely:
  // a) file - the key is the FILE PATH and the value is 'file' 
  // b) text - this is text (HTML) - the KEY is the name of the PARAMS key to be injected; the value may be blank - it not important at the moment
  public $inject = array(); 

  // $format - set from the router IF different from html 
  // this is the format to be output - currently HTML (default) and JSON
  public $format = "html";

  // configs - these are specified in the config file and set during model base class initialisation
  public $configs = array (
    "http_headers" => array(), // security headers; 
    "html_tags" => array(), // allowed html tags
    "mode" => "tracker", // defaults to 'Simple Tracker'
    "modes" => array(), // acceptable modes
    "providers" => array(), // login / account providers
  );

  // $params - every other variable/value pairs are stored here - see functions assign and provide
  public $params = array(); // params array

  // the application helper - this is always defined
  public $app_helper = null;

  // the controller-specific helper, if defined
  public $helper = null;

  // redirect to this URL if one is not passed in the function call
  public $redirect_url = null;

  // this stores the http_referer (ie the link that was clicked to get here)
  public $referer_url = "";
  public $go_back = ""; // if set, show this 

  // this is the village url
  public $site_url = "";

  // this is the server name (host header) from the url the user typed in
  public $host = "";
  
  // this variable says whether to buffer (1) render output or not (0); default = do not buffer
  public $buffer = 0;

  public function __construct() {
  }

  public function assign($var = null, $value = null) {
    // assigns a key/value to the params hash
    $var && strlen($var) > 0 && $this->params[$var] = $value;
  }

  public function flash($text = null, $class = null) {
    // flash are one time JSON messages passed throgh session variable 'flash' (an array)
    // a flash item has structure: {text: <text>, class (optional): <css class> }
    if ( !is_null($text) && strlen($text) > 0 ) {
      is_null($class) && $class = "";

      $_SESSION['flash'][] = array( "text" => $text, "class" => $class);
    }
  }

  public function provide($file = null, $name = null) {
    // evaluate and return results of $file as a string
    is_null($name) && $name = $file;

    $file && strlen($file) > 0 && $this->assign($name, $this->eval_get_file($file));
  }

  public function extract_params($keys = array(), $prefix = "") {
    /*
    // uses php's extract(array, mode, prefix) function to extract the data in $params into variables - ready for views to consume
    extract($this->params, EXTR_SKIP, null); // skip where there's a collision; don't use prefixes 

    // php extract doesn't seem to work - so, iterate and create the variables
    */
    if ( is_null($keys) ) {
      // extract all
      $this->extract_from ($this->params, $prefix);
    }
    else {
      // extract only the keys specified
      foreach ($keys as $k) {
        //array_key_exists($k, $this->params) && $this->extract_from ($this->params[$k], $prefix); 
        !isset($this->{$prefix . $k}) && array_key_exists($k, $this->params) && $this->{$prefix . $k} = $this->params[$k];
      }
    }
  }

  public function extract_params_overwrite($keys = array(), $prefix = "") {
    /*
    // uses php's extract(array, mode, prefix) function to extract the data in $params into variables - ready for views to consume
    extract($this->params, EXTR_SKIP, null); // OVER-WRITE where there's a collision

    // php extract doesn't seem to work - so, iterate and create the variables
    */
    if ( is_null($keys) ) {
      // extract all
      $this->extract_from_overwrite ($this->params, $prefix);
    }
    else {
      // extract only the keys specified
      foreach ($keys as $k) {
        //array_key_exists($k, $this->params) && $this->extract_from ($this->params[$k], $prefix); 
        array_key_exists($k, $this->params) && $this->{$prefix . $k} = $this->params[$k];
      }
    }
  }

  public function extract_fields($key = null, $prefix = null) {
    // this is designed to make items from a record available as variables - ready for a view (eg edit form)
    // the array is presumed to be in the $params array
    $key && array_key_exists($key, $this->params) && is_array($this->params[$key]) && $this->extract_from ($this->params[$key], $prefix); 
  }


  public function dump() {
    // intended to be used for debug purposes - dump the template class instance
    echo "<p>-----------------------<br />" . PHP_EOL;
    //print_r($this->params);
    print_r($this);
    echo PHP_EOL . "<br />-----------------------</p>" . PHP_EOL;
  }

  public function redirect($url = null) {
    // redirect to this URL
    $url || $url = $this->redirect_url;
    
    if ($url && strlen($url) > 0) {
      //ob_clean();
      //ob_start(); // start buffering

      header("Location: $url");

      //ob_end_flush();
    }
  }

  public function getfile() {
    // get / download a file; file path and other related items are in $params
    $path = null;
    $file = array();

    $errors = $this->get_errors($this->params["get_file"]);

    if ( strlen($errors) < 1 ) {
      isset($this->params["get_file"]["data"]["file"]) && $file = $this->params["get_file"]["data"]["file"];
      count($file) < 1 && $errors = "File not found";
    }

    if ( strlen($errors) < 1 ) {
      isset($file["file_path"]) && $path = $file["file_path"];
      strlen($path) < 1 && $errors = "File path not specified";
    }

    if ( strlen($errors) < 1 ) {
      ob_clean();
      ob_start();

      $disposition = "attachment; ";
      isset($file['file_name']) && strlen($file['file_name']) > 0 && $disposition .= "filename=" . preg_replace('/\W/', '_', $file['file_name']);

      isset($file['file_type']) && strlen($file['file_type']) > 0 && header('Content-Type: ' . $file['file_type']);
      //header('Content-Type: text');

      header('Content-Disposition: ' . $disposition);
      header('Expires: 0');
      header('Cache-Control: no-cache, must-revalidate');
      header('Pragma: public');

      readfile($path);

      ob_end_flush();
      exit;

    }
    else {
      echo $errors;
    }

  }

  public function render($item = null, $format = 'html') {
    // this function does 2 things depending in $format, namely:
    // 1) html: outputs the file specified in $item OR the layout file
    // 2) JSON: outputs the $params item specified in $item in JSON
    $format || $format = $this->format;
    $format || $format = 'html';
    $format = strtolower($format);

    // if format is NOT JSON then assume it's normall HTML - so extract variables
    // TO DO: need below line ?
    // $format == 'json' || $this->extract_params();

    //ob_flush();

    if ($this->buffer == 1) {
      ob_clean();
      ob_start(); // start buffering
    }
    
    switch ($format) {

      case 'json':
        $res = "";
        $item && array_key_exists($item, $this->params) && $res = $this->params[$item];
        //header('Content-Type: text/html; charset=utf-8');
        session_cache_limiter('nocache');
        header('Content-Type: application/json; charset=utf-8');

        //echo json_encode($res, JSON_FORCE_OBJECT);
        echo json_encode($res);
        break;

      default: // assume everything else is HTML - a file MUST be specified
        $item || $item = $this->layout;
        strpos($item, '.php') || $item .= '.php'; // add .php at end of file if needed

        // if item specified and no path specified, assume it to be in /app/views/app (ie layouts folder)
        $item && strlen($item) > 0 && strpos($item, DIRECTORY_SEPARATOR) || $item = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "layouts" . DIRECTORY_SEPARATOR . strtolower($item);

        session_cache_limiter('nocache');
        header('Content-Type: text/html; charset=utf-8');
        $this->add_headers();
        $item && is_readable($item) && include($item);

    } // switch

    $this->buffer == 1 && ob_end_flush();
  }

  public function get_errors ($result = null) {
    // returns errors as a string from result
    // result => array ( "errors" = array() )
    $errors = !is_null($result) && isset($result["errors"]) ? "" : "Invalid or no results data";

    if ( strlen($errors) < 1 && count($result["errors"]) > 0 ) {

      foreach ( $result["errors"] as $item ) {

        if ( !is_null($item["message"]) && strlen($item["message"]) > 0 ) {
          $errors .= " <div class='bg-danger'>" . $item["message"];

          DEBUG > 1 && isset($item["debug"]) && !is_null($item["debug"]) && strlen($item["debug"]) > 0 && $error .= $item["debug"];
          $errors .= "</div>" . PHP_EOL; 
        }
      }
    }

    return ($errors);
  }

  public function show_error ($error_string = null, $error_number = null) {
    // displays error string through a flash message and an error message template (error_number)

    is_null($error_string) && $error_string = "";
    is_null($error_number) && $error_number = "0";

    if ( strlen($error_string) > 0 ) {
      $this->flash($error_string, 'bg-danger');
      $this->redirect("/errors/index/" . $error_number);
    }  
  }


  // output functions

  public function get_protected_info ($result = null) {
    // protected info is passed with results as results[protected]{_p1_:x, _p2_:y}
    // guarantees return of object {__p1 => x, __p2 => y}
    $protected = array( "_p1_" => "", "_p2_" => "" );
    !is_null($result) && isset($result["protected"]) && $protected = $result["protected"];

    return $protected;
  }

  public function get_value ($var = null, $alt = null) {
    // get a value from a variable (attribute) of this class
    $res = $alt;
    isset($this->{$var}) && strlen($this->{$var}) > 0 && $res = $this->{$var};
    return $res;
  }

  public function get_value_secure ($var = null, $alt = null) {
    // get a value from a variable (attribute) of this class
    $res = $alt;
    isset($this->{$var}) && strlen($this->{$var}) > 0 && $res = $this->{$var};
    //return htmlentities($res, ENT_HTML5);
    //string htmlspecialchars ( string $string [, int $flags = ENT_COMPAT | ENT_HTML401 [, string $encoding = 'UTF-8' [, bool $double_encode = true ]]] )

    return htmlspecialchars($res, ENT_HTML5);
  }

  public function get_param_value ($key = null, $alt = null) {
    // get a value from the $params array of this class
    $res = $alt;
    $key && array_key_exists($key, $this->params) && strlen($this->params[$key]) > 0 && $res = $this->params[$key];
    return $res;
  }

  public function get_param_value_secure ($key = null, $alt = null) {
    // get a value from the $params array of this class
    $res = $alt;
    $key && array_key_exists($key, $this->params) && strlen($this->params[$key]) > 0 && $res = $this->params[$key];

    //return htmlentities($res, ENT_HTML5);
    return htmlspecialchars($res, ENT_HTML5);
  }

  public function get_http_value ($key = null, $hash = null, $alt = null) {
    // get a global array value (ie a session variable)
    $res = $alt;
    $key && $hash && isset(${$hash}[$key]) && strlen(${$hash}[$key]) > 0 && $res = ${$hash}[$key];
    return $res;
  }

  public function get_http_value_secure ($key = null, $hash = null, $alt = null) {
    // get a global array value (ie a session variable)
    $res = $alt;
    $key && $hash && isset($hash[$key]) && strlen($hash[$key]) > 0 && $res = $hash[$key];

    //return htmlentities($res, ENT_HTML5);
    return htmlspecialchars($res, ENT_HTML5);
  }

  public function get_session_value ($key = null, $alt = null) {
    // get value from session if one exists
    $res = $alt;
    !is_null($key) && isset($_SESSION[$key]) && $res = $_SESSION[$key];

    return ($res);
  }

  public function get_session_value_secure ($key = null, $alt = null) {
    // get value from session if one exists - secure it
    $res = $alt;
    !is_null($key) && isset($_SESSION[$key]) && $res = $_SESSION[$key];

    return htmlspecialchars($res, ENT_HTML5);
  }

  public function escape_string ($str = null) {
    // return escaped string - espaces html characters &, <, >, ' and "

    //return htmlentities($str, ENT_HTML5);
    $result = htmlspecialchars($str, ENT_HTML5);
    $result = $this->substitude($this->configs["html_tags"], $result);

    return $result;
  }




  // date-related functions
  public function get_datetime_string($date = null, $alt = 'never', $format = 'Y-m-d H:i') {
    // get date time string
    $ret = $alt;
    is_null($format) && $format = 'Y-m-d H:i';

    if ( !is_null($date) ) {
      try {
        $d = new DateTime($date);
        $d && $ret = $d->format($format);
      }
      catch (Exception $e) {
        $ret = $e->getMessage();
      }
    }

    return ($ret);
  }

  public function time_diff ($to = null, $from = null) {
    // date difference between 'From' (defaults to now) and $to (required)
    // returns x years, x months, x days, x hours, x minutes or x seconds
    $ret = "";
    $intervals = array("year", "month", "day", "hour", "minute", "second");

    if ( !is_null($to) ) {
      $d = new DateTime($to);
      $diff = is_null($from) ? $d->diff(new Datetime()) : $d->diff(new Datetime($from));

      //list($years, $months, $days, $hours, $minutes, $seconds) = explode(",", $diff->format("%Y,%M,%d,%H,%i,%s"));
      $times = explode(",", $diff->format("%Y,%M,%d,%H,%i,%s"));

      // get first non-zero item
      for ($i = 0; $i < count($times); $i++ ) {
        if ( (int) $times[$i] > 0 ) {
          $ret = (int) $times[$i] > 1 ? (int) $times[$i] . " " . $intervals[$i] . "s" :  (int) $times[$i] . " " . $intervals[$i];
          break;
        }
      }
    }

    return ($ret);
  }

  public function date_diff ($to = null, $from = null) {
    // date difference between 'From' (defaults to now) and $to (required)
    // returns object of form: years => x, months => x, days => x, hours => 24 x days, minutes -> etc
    $ret = array();

    if ( !is_null($to) ) {

      $d = new DateTime($to);
      $diff = is_null($from) ? $d->diff(new Datetime()) : $d->diff(new Datetime($from));

      $ret = array (
        "years" => $diff->y,
        "months" => $diff->m,
        "days" => $diff->days,
        "hours" => 24 * $diff->days,
        "minutes" => 60 * 24 * $diff->days,
        "seconds" => 60 * 60 * 24 * $diff->days,
      );
    }

    return ($ret);
  }





  private function eval_get_file($file) {
    // evaluate and get results of this file into a string
    ob_clean();
    ob_start(); // start buffering
    is_readable($file) && include($file);
    $str = ob_get_contents();
    ob_end_clean();

    return $str;
  }

  private function extract_from ($array, $prefix) {
    // extract variables from the given array; DO NOT over-write existing vars
    foreach ($array as $key => $value) {      
      !isset($this->{$prefix . $key}) && $this->{$prefix . $key} = $value;
    }
  }

  private function extract_from_overwrite ($array, $prefix) {
    // extract variables from the given array; over-write existing vars
    foreach ($array as $key => $value) {      
      $this->{$prefix . $key} = $value;
    }
  }

  private function add_headers() {
    // send headers in $http_headers to browser, if any

    foreach ( $this->configs["http_headers"] as $header => $value ) {
      header($header . ': ' . $value);
    }

    return true;
  }

  private function substitude ( $hash = null, $str = null) {
    // hash is an assoc array whose keys are the tokens to be replaced in str
    is_null($hash) && $hash = array();
    is_null($str) && $str = "";

    $return = $str;

    if ( count($hash) > 0 && strlen($str) > 0 ) {
      // make substitutions
      foreach ( $hash as $key => $val ) {
        $pattern = "/" . $key . "/i";

        $return = preg_replace($pattern, $val, $return);
      }
    }

    return ($return);
  }







}


?>
