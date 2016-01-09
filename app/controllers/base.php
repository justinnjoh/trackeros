<?php

// base controller - implements functionality common to all controllers
// a controller class is initialised with a Template and Router class object

abstract class Base_Controller {

  protected $router = null;
  protected $template = null;
  protected $file_name = null; // name of file containing the parent controller class - this SHOULD be in the router already, BUT with "_Controller" at the end

  public function __construct(Template_Lib $templ = null, Router_Lib $routr = null) {

  	$routr && $this->router = $routr;

    // set controller class name
    $this->set_file_name();

    if ($templ) { // a template object was passed
      $this->template = $templ;

      // transfer default format from router to template
      $this->router && $this->template->format = $this->router->format;

      // if an application help exists (app), load it and place it in the template
      if ( class_exists("App_Helper") ) {
        $app_helper = new App_Helper();
        $this->template->app_helper = $app_helper;
      } 

      // if this controller has a helper, load it into the template
      if ( ($this->file_name)) {
        $name = $this->file_name . "_Helper";
        if (class_exists($name)) {
          $obj = new $name;
          $obj && $this->template->helper = $obj;
        } 
      }
    }    

    // $this->set_menu(null);

  }

  public function run_action () {
  	//$this->{$this->router->action}();

    if ( method_exists($this, $this->router->action) ) {
      $this->{$this->router->action}();
    }
    else {
      // show page not found error
      $error = "Sorry the resource you requested was not found";
      $this->template->show_error($error, 404);
    }
  }

  public function set_view ($partial = null) {
    $return = false;

    // $partial is usually the name of the action - add path to it and place it in the template; if not passed, get action from the router object
    // the 'partial' file is rendered within an application layout file
    $this->router && $partial && strlen($partial) > 0 || $partial = $this->router->action;

    // the controller class file name is also the name of the view folder
    $file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . strtolower($this->file_name) . DIRECTORY_SEPARATOR . strtolower($partial) . ".php";
    if ( is_readable($file) ) {
      // if template exists set the partial in $yield attribute
      //$this->template && $this->template->assign("yield", $file);
      $this->template && $this->template->yield = $file;
    }

    return ($return);
  }

  private function set_file_name () {
    // set the name of this controller class file - this should be in the router object already
    // but with "_Controller" at the end 
    $this->router && $this->file_name = $this->router->controller;
    $this->file_name && strlen($this->file_name) > 0 || $this->file_name =  get_class($this);
    strpos($this->file_name, "_") && $this->file_name = substr($this->file_name, 0, strpos($this->file_name, "_"));
  }


}


?>
