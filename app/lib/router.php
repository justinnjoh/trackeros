<?php
// router - matches routes from URL info and returns a Controller, Action and an Array of other parameters

class Router_Lib {

  public $controller = null; // the controller class name, including _Controller
  public $action = "index";
  public $id = 0;
  public $query_string = array();
  public $format = "html"; // this is in query string _format_ or http_accept, in that order

  public $layout = "application"; // the layout - defaults to application.php

  private $routes = array();
  private $template = null;
  private $sections = array(); // these are sections that make up the main template;
  // they are normally included files, and the router gets data for the file if needed

  public function __construct(Template_Lib $templat = null) {
    $templat && $this->template = $templat;

    // process query_string FIRST as mapped routes may need it
    $this->process_query_string();

    // on landing, set organisation and save visit info
    if ( !isset($_SESSION['org_id']) ) {
      $ses = new Session_Model($this->template, $this->query_string);
      $ses->save_session_info();
      $ses->get_organisation();
    }

    $this->load_routes();
    //$this->process_uri();
    $this->format = $this->get_format();

    // set template's referer_url if one is available
    isset($_SERVER['HTTP_REFERER']) && $this->template && $this->template->referer_url = $_SERVER['HTTP_REFERER'];

    // set current domain / host (ie from the url that someone typed in)
    isset($_SERVER['SERVER_NAME']) && $this->template && $this->template->host = $_SERVER['SERVER_NAME'];


  /*
    $debug = new Debug_lib();
    $debug->print_hash($_SERVER);

    echo "<p>PHP INPUT<br />";
    print_r (file_get_contents('php://input'));

    echo "<p>POST info<br />";
    $debug->print_hash($_POST);

    echo "<p>Check boxes ?<br />";
    //echo $_POST['check'];
    $debug->print_hash($_POST['check']);

    echo "<p>Print from $ variables ?<br />";
    echo "check: " . $check;
  */

  }

  public function run() {
    // this is the only publicly exposed function of the router, and it does this:
    // 1) run any routes specified in the 'layouts' section of the config file
    // 2) run any controller/action pairs specified in 'Sections'
    // 3) process and run the main URI

    foreach ($this->sections as $item) {
      $this->clear_route();
      $this->process_uri($item);
      $this->get_route();
    }

    // process main route
    // the main route URI is in this format: /[layout]/controller/function/id
    // layouts are: 
    // 1) blank (default => application) or 
    // 2) embed (full screen for embeded content) or
    // 3) api (for API data calls) 
    $this->clear_route();
    $this->process_uri();
    $this->get_route();

  }

  private function get_route() {

    // attempt to map static route first;
    if ($this->controller) {
      if (array_key_exists($this->controller, $this->routes)) {
        list($this->controller, $act) = explode("/", $this->routes[$this->controller]);
        if ( !is_null($act) && strlen($act) > 0 ) {
          $this->action = strtolower($act);
        }
      }
    }

    //echo "<p>Get route: controller: $this->controller; Action: $this->action; ID: $this->id</p>";

    if ( $this->controller && strlen($this->controller) > 1 ) {
      // if the class exists, then take it
      $rout = ucfirst($this->controller) . "_Controller"; 
      if ( class_exists ($rout) ) {
        $this->controller = $rout;

        // run the route
        $controller = new $this->controller($this->template, $this);
        $controller->run_action();
      }
      else {
        //throw new Exception ("Could not find route '" . $this->controller . " -> " . $this->action . "'");
        $error = "Could not find route to '" . $this->controller . "/" . $this->action . "'";
        $this->template->show_error($error, 404);
      }
    }
    else {
      // no where to go, so throw error
      //throw new Exception ("Could not find the resource requested");
      $error = "Could not find the resource requested";
      $this->template->show_error($error, 404);
    }
  }

  private function load_routes() {
    $data = array();

    //$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "routes.php";
    $file = SITE_ROOT . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "routes.php";
    $data = include($file); 

    // get mapped routes
    array_key_exists('routes', $data) && $this->routes = $data['routes'];

    // get layout and related sections (controller/actions to get data for include files)
    array_key_exists('layouts', $data) && $data = $data['layouts'];

    if (isset($_SESSION['user_id'])) {
      array_key_exists('member', $data) && $data = $data['member']; 
    }
    else {
      array_key_exists('default', $data) && $data = $data['default']; 
    }

    array_key_exists('layout', $data) && $this->layout = $data['layout']; 
    array_key_exists('sections', $data) && $this->sections = $data['sections']; 
  }

  private function process_query_string() {
    // process query string and place results in the router field $query_string
    // query string is processed initially as mapped routes (ie not the main route in the URI) may require them
    $query_string = urldecode($_SERVER['QUERY_STRING']);
    if (strlen($query_string) > 0 ) {
      $qs = explode("&", $query_string);
      foreach ($qs as $item) {
        list($var, $value) = explode("=", $item);
        $this->query_string[strtolower($var)] = $value;
      }
    }
  }

  private function process_uri ($u = null) {
    // process url and place 'components' into class variables - ready for a controller call

    $uri = $u;
    !is_null($uri) || $uri = $_SERVER['REQUEST_URI'];
    if ( is_null($uri) || strlen($uri) < 1) {
      $uri = "/";
    } 

    // if some one typed in /index.php, set uri to /
    strtolower($uri) == "/index.php" && $uri = "/";

    // split into controller and query string
    $resource = $uri;
    $query_string = "";
    strpos($uri, "?") && list ($resource, $query_string) = array_pad( explode ("?", $uri ,2), 2, "");

    // process resource part
    if (!$resource || strlen($resource) < 1 || $resource == "/") {
      $this->controller = "/";
    }
    else {
      substr($resource, 0, 1) == "/" && $resource = substr($resource, 1);

      // uri format is /[layout]/controller/action/id -- see run function above
      // where layout is: 
      // 1) blank (default => application) or 
      // 2) embed (full screen for embeded content) or
      // 3) api (for API data calls) 
      list($layout, $controller, $action, $id) = array_pad( explode ('/', $resource, 4), 4, null);
      is_null($layout) && $layout = "";
      $layout = strtolower($layout);

      if ( $layout == 'embed' || $layout == 'api' ) {
        // none default, so set non-standard layout
        $this->template->layout = $layout;

        // set in session
        $_SESSION[$layout] = 1;
      }
      else {
        // a standard request, so swift variables to left by 1 place
        $id = $action;
        $action = $controller;
        $controller = $layout;
      }

      $controller && strlen($controller) > 0 && $this->controller = $controller;
      $action && strlen($action) > 0 && $this->action = $action;
      $id && is_numeric($id) && $this->id = $id;
    }

    //echo "<p>URI: controller: $this->controller; Action: $this->action; ID: $this->id </p>";

  }


  private function get_format() {
    // only 2 formats currently supported - html (default) and JSON
    $format = "";
    array_key_exists("_format_", $this->query_string) && $format = $this->query_string['_format_'];
    $format && strlen($format) > 0 || $format = $_SERVER['HTTP_ACCEPT'];

    return stripos($format, "JSON") ? "JSON" : "html";
  }

  private function clear_route() {
    // clears route prior to processing a new one
    $this->controller = null;
    $this->action = "index";
    $this->id = 0;
  }



}


?>
