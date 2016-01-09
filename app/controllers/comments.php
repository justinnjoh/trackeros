<?php
// controller for comments

class Comments_Controller extends Base_Controller {

  public function add() {
    $u = new Comment_model($this->template, $this->router->query_string);
    $u->add();
    $this->template->render("add_comment_result", "json");
  }

  public function publish() {
    $u = new Comment_model($this->template, $this->router->query_string);
    $u->publish();
    $this->template->render("publish_comment_result", "json");
  }


}
