<?php
// home model

class Home_Model extends Base_Model {

  public function index () {
    // home page - get top 10 posts
    $error = "";
    $debug = "";
    $info = "";

    $user_id = $this->template->get_session_value('user_id', 0);

    //$user_id < 1 && $info = "Please log in to view content";

    $posts = array();

    if ( strlen($error) < 1 && strlen($info) < 1 ) {

      $query = "select posts.*, " .
        " assigned_to.name as assigned_to_name, assigned_to.image_url as assigned_to_image, " .
        " creator.name as creator_name, creator.image_url as creator_image, " .
        " categories.type as category_type " .
        " from posts left outer join user_details as assigned_to on posts.assigned_to = assigned_to.user_id " .
        " left outer join user_details as creator on posts.created_by = creator.user_id " .
        " inner join categories on posts.category_id = categories.id " .
        " where posts.status = 10 and (categories.type = 10 or " . $user_id . " > 0) " .
        " order by posts.priority desc, posts.position, posts.id desc";

      $query = $this->add_limit_clause($query, 10);

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0]) && $posts = $res[0];
      }

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "posts" => $posts,
        ),
      'info' => $info,
      'protected' => ""
    );

    strlen($info) > 0 && $this->template->flash($info, "alert alert-info");

    $this->get_menu();
    $this->get_helper_objects();
    $this->template->assign("posts_top10", $result);

    return ($result);
  }

  public function about () {
    $this->get_menu();
  }




}


?>
