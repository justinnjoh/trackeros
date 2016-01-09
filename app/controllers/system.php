<?php
// controller for system

class System_Controller extends Base_Controller {

  public function initialise() {
    $u = new System_model($this->template, $this->router->query_string);

    $u->initialise($this->router->id);

    $this->set_view("initialise");
    $this->template->render(null, "html");
  }

  public function install() {
    $u = new System_model($this->template, $this->router->query_string);

    $u->install($this->router->id);

    $this->set_view("install");
    $this->template->render(null, "html");
  }

  public function howto() {

    $this->set_view("howto");
    $this->template->render(null, "html");
  }



}


?>
