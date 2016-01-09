<?php
// controller for categories

class Categories_Controller extends Base_Controller {

  public function manage() {
    $u = new Category_model($this->template, $this->router->query_string);

    $u->manage($this->router->id);

    $this->set_view("manage");
    $this->template->render(null, "html");
  }

  public function edit() {
    $u = new Category_model($this->template, $this->router->query_string);

    $u->edit($this->router->id);

    $this->template->render("category_edit", "json");
  }

  public function add() {
    $u = new Category_model($this->template, $this->router->query_string);

    $u->add($this->router->id);

    $this->template->render("add_category_result", "json");
  }



}
