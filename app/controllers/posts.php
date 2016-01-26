<?php
// controller for posts

class Posts_Controller extends Base_Controller {

  public function index () {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->index($this->router->id);

    $this->set_view("index");
    $this->template->render(null, "html");
  }

  public function edit() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->edit($this->router->id);

    $this->template->render("post_edit", "json");
  }

  public function add() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->add($this->router->id);

    $this->template->render("add_post_result", "json");
  }

  public function show() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->show($this->router->id);

    $this->set_view("show");
    $this->template->render(null, "html");
  }

  public function feature() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->feature($this->router->id);

    $this->template->render("feature_post_result", "json");
  }

  public function watch() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->watch($this->router->id);

    $this->template->render("watch_post_result", "json");
  }

  public function publish() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->publish();

    $this->template->render("publish_post_result", "json");
  }

  public function assignwiz1() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->assign_wiz1();

    $this->template->render("assign_wiz1_result", "json");
  }

  public function assign() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->assign($this->router->id);

    $this->template->render("assign_result", "json");
  }

  public function getfiles() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->get_post_files($this->router->id);

    $this->template->render("get_post_files_result", "json");
  }

  public function getfile() {
    // download a fil
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->get_file();

    $this->template->getfile();
  }

  public function featured() {
    // get featured posts - usually for display on the right column
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->featured_posts();
  }

  public function postsassigned() {
    // get posts assigned to logged in user - usually for display on the right column
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->posts_assigned();
  }

  public function watched() {
    // get watched posts - usually for display on the right column
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->posts_watched();
  }

  public function assignedtome() {
    // get posts assigned to a user
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->assigned_to_me($this->router->id, null);

    $this->set_view("my_posts");
    $this->template->render(null, "html");
  }

  public function myposts() {
    // get posts created by a user
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->my_posts($this->router->id, null, 1);

    $this->set_view("my_posts");
    $this->template->render(null, "html");
  }

  public function actualwiz1() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->actual_wiz1();

    $this->template->render("actual_wiz1_result", "json");
  }

  public function actual() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->actual($this->router->id);

    $this->template->render("actual_result", "json");
  }

  public function stats() {
    // get stats
    $u = new Post_Model($this->template, $this->router->query_string);
    $u->stats($this->router->id);
  }




  public function preupload() {
    $u = new Post_model($this->template, $this->router->query_string);

    $u->preupload($this->router->id);

    $this->template->render("preupload_result", "json");
  }



}
