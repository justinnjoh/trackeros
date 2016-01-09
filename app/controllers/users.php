<?php
// controller for users

class Users_Controller extends Base_Controller {

  public function manage() {
    $u = new User_model($this->template, $this->router->query_string);

    $u->manage($this->router->id);

    $this->set_view("manage");
    $this->template->render(null, "html");
  }

  public function edit() {
    $u = new User_model($this->template, $this->router->query_string);

    $u->edit($this->router->id);

    $this->template->render("user_edit", "json");
  }

  public function adduserdetails() {
    $u = new User_model($this->template, $this->router->query_string);

    $u->add_user_details($this->router->id);

    $this->template->render("add_user_details_result", "json");
  }

  public function show() {
    $u = new User_model($this->template, $this->router->query_string);

    $u->show($this->router->id);

    $this->set_view("show");
    $this->template->render(null, "html");
  }





}
