<?php
// controller for home pages

class Home_Controller extends Base_Controller {

  public function index() {
    $u = new Home_model($this->template, $this->router->query_string);

    $u->index();

    $this->set_view("index");
    $this->template->render(null, "html");
  }

  public function about() {
    $u = new Home_model($this->template, $this->router->query_string);

    $u->about();

    $this->set_view("about");
    $this->template->render(null, "html");
  }



}

?>
