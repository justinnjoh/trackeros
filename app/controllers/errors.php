<?php
// error model

class Errors_Controller extends Base_Controller {

  public function index () {
    $u = new Error_model($this->template, $this->router->query_string);
    $u->index();
    
    if ( isset($this->router->id) && !is_null($this->router->id) ) {
      $this->set_view($this->router->id);
      $this->template->render(null, "html");
    }
  }


}
