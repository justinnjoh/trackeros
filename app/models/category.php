<?php

class Category_Model extends Base_model {

  public function index() {
  	// for normal use, when page is refreshed - to get menus (categories) for current organisation

    // whenever a page is refreshed, reset the current cat_id and post_id in session
    // these IDs are NOT past via POST or GET when items are added to them
    $_SESSION['cat_id'] = 0;
    $_SESSION['post_id'] = 0;

    // current organisation is always in session
    $org_id = $this->template->get_session_value("org_id", 0);

    $data = array();

    $query = "select * from categories where organisation_id = " . $org_id .
        " and status = 10 and id >= 500 order by position;";

    $res = $this->query($query);

    $error = $this->db_error;
    DEBUG > 0 && $debug = $this->db_debug;

    strlen($error) < 1 && isset($res[0]) && $data = $res[0];

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $data,
      'info' => ""
    );
    
    $this->template->assign("menu", $result);
    $this->template->extract_params(array("menu"), "");

    return ($result);
  }

  public function manage() {
  	$error = "";
  	$debug = "";

    $right = $this->template->get_session_value("rights", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    $res = $this->check_right(30);
    $error = $res["errors"][0]["message"];

    $data = array();

    if ( strlen($error) < 1 ) {
      $query = "select id, organisation from organisations where id = " . $org_id . "; " .
        "select * from categories where organisation_id = " . $org_id .
        " and status in (10, 11, 1) order by position; ";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        $data["org_id"] = $org_id; 
      	$data["organisation"] = isset($res[0][0]["id"]) ? $res[0][0]["organisation"] : "";
      	$data["categories"] = isset($res[1]) ? $res[1] : array();
      }
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $data,
      'info' => ""
    );

    $this->get_helper_objects();
    $this->template->assign("manage_categories", $result);
    
    // menu
    $this->get_menu();

    return ($result);
  }

  public function edit ($category_id = null) {
    // add / edit category - get values (if id is passed) for form
    // AJAX

    $debug = "";
    $error = "";

    is_null($category_id) && $category_id = 0;

    $org_id = $this->template->get_session_value('user_org_id', 0);
    isset($_SESSION["rights"]) && $_SESSION["rights"] >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    $org_id < 1 && $error = "An organisation is required";

    if ( strlen($error) < 1 ) {
      $res = $this->check_right(30);
      $error = $res["errors"][0]["message"];
    }

    $category = array();
    
    if ( strlen($error) < 1 && $category_id > 0 ) {
      $query = "select * from categories where id > 0 and id = " . $category_id .
          " and organisation_id = " . $org_id;

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $category = $res[0][0];
      }
    }

    // protected data
    $protected_data = $category_id . "," . $org_id;
    $protected = array(
      "_p1_" => $protected_data,
      "_p2_" => $this->codify($protected_data)
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "category" => $category
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->template->assign("edit_category_result", $result);

    $this->template->assign("edit_category", $result["data"]["category"]);
    $this->template->extract_fields("edit_category", "cat_");

    // provide view for display
    $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories" . DIRECTORY_SEPARATOR . "edit.php";
    $this->template->provide($view, 'category_edit');
    
    return ($result);

  }

  public function add () {
    // add / update category
    // AJAX

    $debug = "";
    $error = "";

    $org_id = 0;
    $category_id = 0;

    $protected_p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $protected_p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $category = isset($_POST["category"]) ? $_POST["category"] : "";
    $description = isset($_POST["description"]) ? $_POST["description"] : "";
    $position = isset($_POST["position"]) ? $_POST["position"] : 0;
    $posts_status = isset($_POST["posts_status"]) ? $_POST["posts_status"] : 11;
    $status = isset($_POST["status"]) ? $_POST["status"] : 11;
    $type = isset($_POST["type"]) ? $_POST["type"] : 0;

    $category = strlen($category) > 100 ? substr($category, 0, 99) : $category;
    $description = strlen($description) > 100 ? substr($description, 0, 99) : $description;

    $res = $this->check_right(30);
    $error = $res["errors"][0]["message"];

    if ( strlen($error) < 1 && (strlen($protected_p1) < 1 || strlen($protected_p2) < 1) ) {
      $error = "Invalid request or programmer error - security information not found";
    }

    if ( strlen($error) < 1 && !$this->is_equal($protected_p1, $protected_p2) ) {
      $error = "Request denied - authenticity of this request could not be verified";
    }

    if ( strlen($error) < 1 ) {
      list( $category_id, $org_id ) = explode(",", $protected_p1, 2);

      $data = array (
          "category" => $category,
          "description" => $description,
          "position" => $position,
          "organisation_id" => $org_id,
          "posts_status" => $posts_status,
          'type' => $type,
          'status' => $status
        );

      $this->ensure_visit();
      if ( $category_id > 0 ) {
        $res = $this->update('categories', $data, $category_id, true);
      }
      else {
        $res = $this->insert('categories', $data, true, true);
      }

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $res,
      'info' => "",
      'protected' => array("_p1_" => $protected_p1,
          "_p2_" => $protected_p2, 
          ),
    );

    $this->template->assign("add_category_result", $result);

    return ($result);

  }




}
