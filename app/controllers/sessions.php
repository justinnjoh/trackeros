<?php
// controller for sessions

class Sessions_Controller extends Base_Controller {

  public function login() {
    $s = new Session_Model($this->template, $this->router->query_string);
    $s->login();
    $this->template->render('login', 'json');
    
  }

  public function logout() {

    $s = new Session_Model($this->template, $this->router->query_string);
    $s->logout();

    $this->template->redirect("/");
  }

  public function forgot() {
    // show form to reset password
    $this->set_view("forgot");
    $this->template->render(null, "html");
  }

  public function reset() {
    $s = new Session_Model($this->template, $this->router->query_string);
    $s->reset();

    $this->template->render("reset_result", "json");
    
  }

  public function verify() {
    // show password change form

    $s = new Session_Model($this->template, $this->router->query_string);
    $s->verify();

    $this->set_view("verify");
    $this->template->render(null, "html");
  }

  public function updatepassword() {
    $s = new Session_Model($this->template, $this->router->query_string);
    $s->update_password();

    $this->template->render("update_password_result", "json");
    
  }



}

?>