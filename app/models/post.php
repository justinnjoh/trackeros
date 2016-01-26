<?php
// post model

class Post_Model extends Base_model {

  public function index ($category_id = null) { 
    // get posts in a category

    $error = "";
    $debug = "";
    $info = "";
    $protected = array();

    $posts = array();
    $category = array();
    $status_codes = array();

    $sts_codes = "";
    isset($_POST["sts"]) && $sts_codes = join(",", $_POST["sts"]);

    $page_meta_tags = array();

    is_null($category_id) && $category_id = 0;

    $user_id = $this->template->get_session_value('user_id', 0);
    $rights = $this->template->get_session_value('rights', 0);
    $org_id = $rights >= 80 || $user_id < 1 ? $this->template->get_session_value('org_id', 0) : $this->template->get_session_value('user_org_id', 0);

    //$user_id < 1 && $info = "Please log in to view content";

    if ( strlen($error) < 1 && strlen($info) < 1 ) {

      $query = "select id, category, type, description, posts_status from categories where id = " . $category_id . 
        " and organisation_id = " . $org_id . "; " .
        " select posts.*, " .
        " assigned_to.name as assigned_to_name, assigned_to.image_url as assigned_to_image, " .
        " creator.name as creator_name, creator.image_url as creator_image, " .
        " status_codes.code as status_code, status_codes.description as status_description, " .
        " status_codes.icon as status_icon, status_codes.class as status_class, " .
        " categories.type as category_type " .
        " from posts left outer join user_details as assigned_to on posts.assigned_to = assigned_to.user_id " .
        " left outer join user_details as creator on posts.created_by = creator.user_id " .
        " inner join categories on posts.category_id = categories.id " .
        " inner join status_codes on posts.status = status_codes.id " .
        " where posts.category_id = " . $category_id .
        " and categories.organisation_id = " . $org_id .
        " and (categories.type = 10 or " . $user_id . " > 0) " .
        " and ( (" . $rights . " >= 80) or (" . $rights . " >= 30 and posts.status in (10, 11, 1)) " .
        " or (posts.created_by = " . $user_id . " and posts.status in (10, 11, 1))" .
        " or (posts.status in (10, 2)) )";

      strlen($sts_codes) > 0 && $query .= " and posts.status in (" . $sts_codes . ")";

      $query .= " order by posts.position, posts.status desc, posts.priority desc, posts.id desc; ";

      $status = " select id, code, description, icon, class";
      if ( strlen($sts_codes) > 0 ) {
        $status .= ", case when id in (" . $sts_codes . ") then 'checked' else '' end as checked ";
      }
      else {
        $status .= ", '' as checked ";
      }

      $status .= " from status_codes where id in (1, 2, 10, 11) order by id;";
      $query .= $status;

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      $this_cat_id = 0;
      $posts_status = 11;
      $type = 0;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $category = $res[0][0];
        isset($res[1]) && $posts = $res[1];
        isset($res[2]) && $status_codes = $res[2];
      }

      if ( count($category) > 0 ) {
        $this_cat_id = $category["id"];
        $posts_status = $category["posts_status"];
        $type = $category["type"];

        $page_title = $category["category"];
        $page_meta_tags["description"] = $category["description"];
      }

      count($posts) < 1 && $type != 10 && $user_id < 1 && $info = "Please log in to view content in this category";

      // protected data
      $protected_data = "0," . $this_cat_id . "," . $org_id . "," . $posts_status . ",0";
      $protected = array(
        "_p1_" => $protected_data,
        "_p2_" => $this->codify($protected_data)
      );

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
        'btn_label' => $user_id > 0 ? "Add New Post" : "",
        'posts' => $posts,
        'category' => $category,
        'status_codes' => $status_codes
      ),
      'info' => $info,
      'protected' => $protected
    );

    strlen($info) > 0 && $this->template->flash($info, "alert alert-info");

    $this->template->assign("posts_index", $result);

    // get menu and reset this cat in session
    $this->get_menu();
    $this->get_helper_objects();

    $this->template->assign("page_title", $page_title);
    $this->template->assign("page_meta_tags", $page_meta_tags);

    $_SESSION['cat_id'] = $this_cat_id;

    return ($result);
  }	

  public function edit () {
    // add / edit post - get values (if id is passed) for form
    // AJAX; security values _p1_ and _p2_ are expected

    $debug = "";
    $error = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;
    $post_status = 0;

    $created_by = 0;
    $post = array();
    $category = array();
    $images = array();

    $user_id = $this->template->get_session_value('user_id', 0);
    $rights = $this->template->get_session_value('rights', 0);
    $this_org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $this_org_id = $this->template->get_session_value('org_id', 0);

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    if ( strlen($p1) < 1 || strlen($p2) < 1 ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    strlen($error) < 1 && $user_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $org_id, $posts_status, $x) = explode(",", $p1, 5);

      $category_id < 1 && $error = "Sorry a category for the post was not specified";
    }

    strlen($error) < 1 &&  $org_id < 1 && $error = "Sorry an organisation was not specified";
    strlen($error) < 1 &&  !array_key_exists($posts_status, array("10" => 1, "11" => 0)) && $error = "Sorry a post status could not be deduced or was not specified";

    if ( strlen($error) < 1 ) {

      $protected_data = $post_id . "," . $category_id . "," . $org_id . "," . $posts_status . ",0";
      $protected = array(
        "_p1_" => $protected_data,
        "_p2_" => $this->codify($protected_data)
      );

      $query = "select id, organisation_id, category, posts_status from categories " .
        "where id > 0 and id = " . $category_id . " and organisation_id = " . $org_id . "; " .
        "select * from posts where id > 0 and category_id = " . $category_id .
        " and id = " . $post_id . "; " .
        "select * from post_files where post_id > 0 and post_id = " . $post_id . ";";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $category = $res[0][0];
        isset($res[1][0]) && $post = $res[1][0];
        isset($res[2]) && $images = $res[2];

        if ( $post_id > 0 ) {
          count($post) > 0 && $created_by = $post["created_by"];
          $rights < 30 && $created_by != $_SESSION['user_id'] && $error = "You do not have sufficient authority to modify this resource";
        }
      }

    }

    // protected data
    $protected = array(
      "_p1_" => $protected_data,
      "_p2_" => $this->codify($protected_data)
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "btn_label" => "Add New Post",
          "category" => $category,
          "post" => $post,
          "images" => $images
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->get_helper_objects();

    $this->template->assign("edit_post_result", $result);

    $this->template->assign("edit_post", $result["data"]["post"]);
    $this->template->extract_fields("edit_post", "post_");

    // provide view for display
    $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "edit.php";
    $this->template->provide($view, 'post_edit');
    
    return ($result);

  }

  public function show ($post_id = null) {
    // show post - get values (if id is passed) for form

    $debug = "";
    $error = "";

    is_null($post_id) && $post_id = 0;

    $category_id = 0;
    $category = array();
    $org_id = 0;
    $posts_status = 11;
    $commenting = 11;

    $watching = 0;
    $can_edit = 0;
    $can_action = 0;
    $can_feature = 0;
    $show_guestimates = 0;

    $mode = $this->template->configs["mode"];

    $post = array();
    $comments = array();
    $images = array();
    $main_image = array();
    $featured = array();

    $page_title = "";
    $page_meta_tags = array();

    $rights = $this->template->get_session_value('rights', 0);
    $user_id = $this->template->get_session_value('user_id', 0);
    $this_org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $this_org_id = $this->template->get_session_value('org_id', 0);

    $post_id < 1 && $error = "There is no post to show";

    if ( strlen($error) < 1 ) {

      $query = "select posts.*, categories.category, categories.organisation_id, categories.posts_status, " .
        " assigned_to.name as assigned_to_name, assigned_to.image_url as assigned_to_image, " .
        " creator.name as creator_name, creator.image_url as creator_image, " .
        " status_codes.code as status_code, status_codes.description as status_description, " .
        " status_codes.icon as status_icon, status_codes.class as status_class " .
        " from posts left outer join user_details as assigned_to on posts.assigned_to = assigned_to.user_id " .
        " left outer join user_details as creator on posts.created_by = creator.user_id " .
        " inner join categories on posts.category_id = categories.id " .
        " inner join status_codes on posts.status = status_codes.id " .
        " where posts.id > 0 and posts.id = " . $post_id . " and ( categories.type = 10 or " .
          $user_id . "> 0) " .
        " and ( (" . $rights . " >= 80) or " .
        " (posts.created_by = " . $user_id . " and posts.status in (1, 2, 10, 11)) or " .
        " (" . $rights . " >= 30 and posts.status in (1, 2, 10, 11) and " .
        " categories.organisation_id = " . $this_org_id . ") or " .
        " (posts.status in (2, 10)) ); " .
        " select * from post_files where post_id > 0 and comment_id < 1 and main < 1 and post_id = " . $post_id .
        " order by position, id desc; " .
        " select * from post_files where post_id > 0 and comment_id < 1 and post_id = " . $post_id .
        " and is_image = 1 and main > 0 order by position, id desc; " .
        " select post_comments.*, user_details.name as creator_name, " .
        " user_details.image_url as creator_image " .
        " from post_comments inner join user_details on post_comments.created_by = " .
        " user_details.user_id where post_comments.post_id = " . $post_id .
        " and ( (" . $rights . " >= 80) or " .
        " (post_comments.created_by = " . $user_id . " and post_comments.status in (1, 10, 11)) or " .
        " (" . $rights . " >= 30 and post_comments.status in (1, 10, 11)) or " .
        " (post_comments.status = 10) ) order by post_comments.id; " .
        " select * from featured_posts where post_id = " . $post_id . "; " .
        " select * from post_watchers where post_id = " . $post_id .
        " and user_id = " . $user_id . "; ";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $post = $res[0][0];
        isset($res[1]) && $images = $res[1];
        isset($res[2][0]) && $main_image = $res[2][0];
        isset($res[3]) && $comments = $res[3];
        isset($res[4][0]) && $featured = $res[4][0];
        isset($res[5][0]) && count($res[5][0]) > 0 && $watching = 1;

        if ( count($post) > 0 ) {
          $page_title = $post["title"] . " - " . $post["id"];
          $page_meta_tags["description"] = $post["post"];

          if ( $post['text_in_file'] > 0 ) {
            $file_name = SITE_ROOT . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR .
              "docs" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . $post_id .
              ".txt";
            $post["post"] = file_get_contents($file_name);
          }

          $category_id = $post["category_id"];
          $org_id = $post["organisation_id"];
          $posts_status = $post["posts_status"];
          $commenting = $post["commenting"];

          $can_edit = $rights >= 80 || ($rights >= 30 && $org_id == $this_org_id) || ($user_id == $post["created_by"]) ? 1 : 0;
          $can_action = $rights >= 80 || ($rights >= 30 && $org_id == $this_org_id) || ($user_id == $post["created_by"]) || ($user_id > 0 && $user_id == $post["assigned_to"]) ? 1 : 0;
          $can_feature = $rights >= 80 ? 1 : 0;

          $post["watching"] = $watching;
          $post["can_edit"] = $can_edit;
          $post["can_action"] = $can_action;
          $post["can_feature"] = $can_feature;


          $post["proposed_lag_class"] = "text-muted";
          $post["proposed_lag"] = is_null($post["end_date_proposed"]) ? "n/a" : $this->template->date_diff($post["end_date_proposed"])["days"];

          $post["actual_lag_class"] = "text-muted";
          $post["actual_lag"] = "n/a";
          if ( $post["days_actual"] >= 0 && $post["days_proposed"] >= 0 ) {
            $post["actual_lag"] = $post["days_proposed"] - $post["days_actual"];
          }

          switch (true) {
            case $post["proposed_lag"] == "n/a":
              break;

            case $post["proposed_lag"] > 5:
              $post["proposed_lag_class"] = "bg-success";
              break;

            case $post["proposed_lag"] >= 0:
              $post["proposed_lag_class"] = "bg-waning";
              break;

            default:
              $post["proposed_lag_class"] = "bg-danger";
              break;
          }

          switch (true) {
            case $post["actual_lag"] == "n/a":
              break;

            case $post["actual_lag"] > 5:
              $post["actual_lag_class"] = "bg-danger";
              break;

            case $post["actual_lag"] >= 0:
              $post["actual_lag_class"] = "bg-warning";
              break;

            default:
              $post["actual_lag_class"] = "bg-success";
              break;
          }
          if ( !is_null($post["start_date_proposed"]) || !is_null($post["end_date_proposed"]) || $post["days_proposed"] >= 0 || 
               !is_null($post["start_date_actual"]) || !is_null($post["end_date_actual"]) || $post["days_actual"] >= 0  ) {
            $show_guestimates = 1;
          }

          $post["show_guestimates"] = $show_guestimates;

          $category = array(
            "id" => $post["category_id"],
            "category" => $post["category"],
          );

          // publish actions
          $publish_actions = [];
          if ( $can_action > 0 ) {

            $unpublish = array (
                "code" => "1",
                "title" => "Remove this post from public view",
                "action" => "Unpublish"
            );

            $publish = array (
                "code" => "10",
                "title" => "Publish this post",
                "action" => "Publish"
            );

            $reopen = array (
                "code" => "10",
                "title" => "Re-open this post - issue not resolved",
                "action" => "Re-open"
            );

            $close = array (
                "code" => "2",
                "title" => "Close - issue is resolved",
                "action" => "Close"
            );

            switch ( $post["status"] ) {
              case 11: // new => publish, unpublish
                $publish_actions[0] = $publish;
                $publish_actions[1] = $unpublish;
                break;

              case 10: // active => close, unpublish
                $publish_actions[0] = $unpublish;
                if ( $mode == 'tracker' ) {
                  $publish_actions[1] = $close;
                }
                break;

              case 2: // closed => re-open
                $publish_actions[0] = $reopen;
                break;

              case 1: // inactive => publish
                $publish_actions[0] = $publish;
                break;

            }

          }

          $post["publish_actions"] = $publish_actions;

        } // count $post > 0

      }
    }

    // protected data
    $protected_data = $post_id . "," . $category_id . "," . $org_id . "," . $posts_status . "," . $commenting;
    $protected = array(
      "_p1_" => $protected_data,
      "_p2_" => $this->codify($protected_data)
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "btn_label" => $can_edit > 0 ? "Edit Post" : "",
          "post" => $post,
          "comments" => $comments,
          "images" => $images,
          "main_image" => $main_image,
          "category" => $category,
          "featured" => $featured,
        ),
      'info' => "",
      'protected' => $protected
    );

    // get menu and reset this cat in session
    $this->get_menu();
    $this->get_helper_objects();
    
    $this->template->assign("show_post_result", $result);

    strlen($page_title) > 0 && $this->template->assign("page_title", $page_title);
    $this->template->assign("page_meta_tags", $page_meta_tags);
    
    return ($result);

  }

  public function add() {
    // add / update post
    // security values _p1_ and _p2_ are expected

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;
    $post_status = 0;
    $commenting = 0;
    $priority = 0;

    $start_date_proposed = null;
    $end_date_proposed = null;
    $days_proposed = -1;

    $url = "";
    $title = "";
    $post = "";

    $visit_id = $this->template->get_session_value('visit_id', 0);
    $user_id = $this->template->get_session_value('user_id', 0);

    $docs_path = SITE_ROOT . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR .
      "docs" . DIRECTORY_SEPARATOR . "posts";

    isset($_POST["title"]) && $title = $_POST["title"];
    isset($_POST["post"]) && $post = $_POST["post"];
    isset($_POST["url"]) && $url = $_POST["url"];
    isset($_POST["commenting"]) && $commenting = $_POST["commenting"];
    isset($_POST["priority"]) && $priority = $_POST["priority"];

    isset($_POST["start_date_proposed"]) && strlen($_POST["start_date_proposed"]) > 0 && $start_date_proposed = $_POST["start_date_proposed"];

    isset($_POST["end_date_proposed"]) && strlen($_POST["end_date_proposed"]) > 0 && $end_date_proposed = $_POST["end_date_proposed"];

    isset($_POST["days_proposed"]) && $days_proposed = $_POST["days_proposed"];
    !is_numeric($days_proposed) && $days_proposed = -1;

    $text_in_file = strlen($post) > 800 ? 1 : 0;

    $rights = $this->template->get_session_value('rights', 0);
    $this_org_id = $this->template->get_session_value('user_org_id', 0);
    isset($_SESSION["rights"]) && $_SESSION["rights"] >= 80 && $this_org_id = $this->template->get_session_value('org_id', 0);

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    if ( strlen($p1) < 1 || strlen($p2) < 1 ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $org_id, $posts_status, $x) = explode(",", $p1, 5);

      $category_id < 1 && $error = "Sorry a category for the post was not specified";
    }

    strlen($error) < 1 &&  $org_id < 1 && $error = "Sorry an organisation was not specified";
    strlen($error) < 1 &&  !array_key_exists($posts_status, array("10" => 1, "11" => 0)) && $error = "Sorry a post status could not be deduced or was not specified";

    if ( strlen($error) < 1 ) {
      // validate guestimates
      if ( !is_null($end_date_proposed) && !is_null($start_date_proposed) ) {

        try {
          $interval = $this->template->date_diff ($end_date_proposed, $start_date_proposed);

          $interval["days"] < 0 && $error = "The end date proposed must be after the start date proposed";
          strlen($error) < 1 && $days_proposed > 0 && $days_proposed > $interval["days"] && $error = "Proposed days, " .
            $days_proposed . ", inconsistent with dates " . $start_date_proposed . " to " . $end_date_proposed .
            " which spans only " . $interval["days"] . " days";
        }

        catch (Exception $e) {
          $error = "There was a problem with the dates entered : " .
            $start_date_proposed . ", " . $end_date_proposed . "; " . $e->getMessage();
        }
      }
    }

    if ( strlen($error) < 1 ) {

      $data = array (
        "title" => substr($title, 0, 199),
        "post" => substr($post, 0, 799),
        "category_id" => $category_id,
        "position" => 0,
        "url" => substr($url, 0, 99),
        "commenting" => $commenting,
        "priority" => $priority,
        "text_in_file" => $text_in_file,
      );

      !is_null($end_date_proposed) && $data["end_date_proposed"] = $end_date_proposed;
      !is_null($start_date_proposed) && $data["start_date_proposed"] = $start_date_proposed;
      $days_proposed != -1 && $data["days_proposed"] = $days_proposed;

      if ( $post_id > 0 ) {
        $res = $this->update('posts', $data, $post_id, true);
      }
      else {
        $data["assigned_to"] = 0;
        $data["status"] = $posts_status;

        $res = $this->insert('posts', $data, true, true);
      }

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $post_id = $res[0][0]["id"];

        $post_id < 1 && $error = "There was an error saving post information";

        if ( strlen($error) < 1 && $text_in_file > 0 ) {
          $text = $this->clean_string($post);
          $file_name = $docs_path . DIRECTORY_SEPARATOR . $post_id . ".txt";
          file_put_contents($file_name, $text);
        } 
      }
    }

    if ( strlen($error) < 1 ) {

      // gallery : old images - delete ones that are not currently checked
      $old_files = array();
      if ( isset($_POST['old_files']) ) {
        $old_files = explode(",", $_POST['old_files']);
      }

      // <id>,,,<file name>,,,<caption>,,,<file type>,,,<size>,<pos>##<id>,,,<file name>,,, ...
      $old_files_info = "";
      isset($_POST['old_files_info']) && $old_files_info = $_POST['old_files_info'];
      $old_files_info = strlen($old_files_info) > 0 ? explode('##', $old_files_info) : array();

      if ( count($old_files) > 0 ) {
        $sql = "update post_files set status = 0, update_visit_id = " . $visit_id .
          ", updated_by = " . $user_id . ", updated_at = '" . $this->iso_datetime_now .
          "' where post_id = " .
          $post_id . " and not id in (" . implode(',', $old_files) . ")";

        DEBUG > 0 && $debug .= "; " . $sql;

        $this->query($sql);

        // for each old file, update position, caption
        foreach ( $old_files_info as $f ) {

          // <id>,,,<file name>,,,<caption>,,,<file type>,,,<size>,<pos>##<id>,,,<file name>,,,<main> ...
          $one_file = explode(',,,', $f);

          $file_id = count($one_file) > 0 ? $one_file[0] : 0;
          !is_numeric($file_id) && $file_id = 0;

          if ( $file_id > 0 ) {

            $caption = count($one_file) > 2 ? $one_file[2] : "";
            $position = count($one_file) > 5 ? $one_file[5] : 0;
            !is_numeric($position) && $position = 0;

            $main = count($one_file) > 6 && !is_null($one_file[6]) && strlen($one_file[6]) > 0 ? $one_file[6] : 0;

            $data = array (
              "caption" => $caption,
              "position" => $position,
              "main" => $main
            );

            $this->update('post_files', $data, $file_id, true);

            DEBUG > 0 && $debug .= "; " . $this->db_debug;
          }
          else {
            DEBUG > 0 && $debug .= "; " . "Invalid old file info";
          }

        } // foreach

      } // count old files

      // add new files - those that have been pre-uploaded
      $new_files = array();
      if ( isset($_POST['new_files']) ) {
        $new_files = explode(",", $_POST['new_files']);
      }

      // in case of AJAX call, new files info will be passed via 'new_files_info'
      // <id>,,,<file name>,,,<caption>,,,<file type>,,,<size>,<pos>##<id>,,,<file name>,,, ...
      $new_files_info = "";
      isset($_POST['new_files_info']) && $new_files_info = $_POST['new_files_info'];
      $new_files_info = strlen($new_files_info) > 0 ? explode('##', $new_files_info) : array();

      if ( count($new_files) > 0 ) {

        $thumbs_path = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "assets" .
          DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "posts"; 

        foreach ( $new_files_info as $f ) {

          // <id>,,,<file name>,,,<caption>,,,<file type>,,,<size>,<pos>##<id>,,,<file name>,,,<main> ...
          $one_file = explode(',,,', $f);
          $fields = count($one_file);

          $preup_file_id = $fields > 0 ? $one_file[0] : 0;
          !is_numeric($preup_file_id) && $preup_file_id = 0;

          $file_name = $fields > 1 ? $one_file[1] : "";
          $caption = $fields > 2 ? $one_file[2] : "";
          $file_type = $fields > 3 ? $one_file[3] : "";

          $file_size = $fields > 4 ? $one_file[4] : 0;
          !is_numeric($file_size) && $file_size = 0;

          $position = $fields > 5 ? $one_file[5] : 0;
          !is_numeric($position) && $position = 0;

          $main = count($one_file) > 6 && !is_null($one_file[6]) && strlen($one_file[6]) > 0 ? $one_file[6] : 0;

          if ( $preup_file_id > 0 && strlen($file_name) > 0 ) {

            $file_ext = strtolower(substr(strrchr($file_name, '.'), 1, 10));

            // is it an image file ?
            $is_image = 0;
            $this->valid_upload_extension($file_ext, array("jpeg", "jpg", "png", "gif")) && $is_image = 1;

            $preup_file_name = SITE_ROOT  . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR .
              "temp" . DIRECTORY_SEPARATOR . "preups" . DIRECTORY_SEPARATOR . "raw" .
              DIRECTORY_SEPARATOR . $preup_file_id . "." . $file_ext;

            // add the image in DB & get id
            $image_id = 0;
            $data = array (
              "post_id" => $post_id,
              "comment_id" => 0,
              "position" => $position,
              "main" => $main,
              "caption" => substr($caption, 0, 199),
              "file_ext" => $file_ext,
              "file_type" => substr($file_type, 0, 99),
              "file_name" => substr($file_name, 0, 99),
              "file_size" => $file_size,
              "is_image" => $is_image,
              "status" => 10
            );

            $res = $this->insert('post_files', $data, true, true);

            DEBUG > 0 && $debug .= "; " . $this->db_debug;

            if ( strlen($this->db_error) < 1 ) {

              isset($res[0][0]['id']) && $image_id = $res[0][0]['id'];

              if ( $image_id > 0 ) {
                // move file to raw images directory - by renaming
                $raw_file_name = $docs_path . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR .
                  $post_id . "-0-" . $image_id . "." . $file_ext;

                if ( rename($preup_file_name, $raw_file_name) ) {

                  // resize image files only
                  if ( $is_image > 0 ) {
                    $thumb_name = $thumbs_path . DIRECTORY_SEPARATOR . $post_id . "-0-" . $image_id . "-th." . $file_ext;
                    $image_name = $thumbs_path . DIRECTORY_SEPARATOR . $post_id . "-0-" . $image_id . "-im." . $file_ext;

                    $img = $this->resize_image($raw_file_name, 'image', 100, $image_name);
                    DEBUG > 0 && strlen($img["error"]["message"]) > 0 && $debug .= ", " . $img["error"]["message"];

                    $img = $this->resize_image($raw_file_name, 'thumb', 100, $thumb_name);
                    DEBUG > 0 && strlen($img["error"]["message"]) > 0 && $debug .= ", " . $img["error"]["message"];
                  }

                  // remove from pre_upload
                  $this->delete('pre_uploads', $preup_file_id, 'id');

                } // move (rename) file
                else {
                  DEBUG > 0 && $debug .= "; Could not move file to correct application folder";
                }
 
              } // image_id > 0
              else {
                DEBUG > 0 && $debug .= "; Error recoding file named [" . $file_name . "]";
              }

            } // len db_error > 0
            else {
              DEBUG > 0 && $debug .= "; " . $this->db_error;
            }

          } // $preup_file_id > 0 and strlen($file_name)
          else {
            DEBUG > 0 && $debug .= "; There was a problem trying to find uploaded file [" . $file_name . "]";
          }

        } // foreach

      } // count(new files) > 0

    } // len(error) < 1

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "post_id" => $post_id,
          "category_id" => $category_id
        ),
      'info' => $info,
    );

    $this->template->assign("add_post_result", $result);

    strlen($info) > 0 && $this->template->flash($info, "alert alert-success");
    strlen($error) > 0 && $this->template->flash($error, "alert alert-warning");
    
    return ($result);

  }

  public function preupload() {
    // a file is being PRE-added from a variable called 'file'
    // add it to pre-uploads and store it in /images/raw for now
    // return the pre-upload ID (or 0 for error), and an error msg in an array 

    $id = 0; // pre_upload ID

    $file_name = "";
    $file_ext = "";
    $file_type = "";
    $file_size = 0;

    $error = "";
    $debug = "";
    $info = "";
    $file = array();

    $user_id = $this->template->get_session_value('user_id', 0);
    $user_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 && $_FILES['file']['error'] == UPLOAD_ERR_OK) {

      $file_name = strtolower(substr($_FILES['file']['name'], 0, 49));
      $file_type = strtolower(substr($_FILES['file']['type'], 0, 99));
      $file_ext = strtolower(substr(strrchr($file_name, '.'), 1, 10));
      $file_size = $_FILES['file']['size'];

      // limited to max upload file size ?
      $this->max_upload_file_size > 0 && $this->max_upload_file_size < $file_size && $error = "This file has exceeded (" . $file_size . ") the maximum allowed size limit"; 
      strlen($error) < 1 && !$this->valid_upload_extension($file_ext, null) && $error = "This file type is not allowed";

      if ( strlen($error) < 1 ) {

        $data = array(
          "position" => 0,
          "caption" => "",
          "file_ext" => $file_ext,
          "file_name" => $file_name,
          "file_type" => $file_type,
          "file_size" => $file_size,
          "status" => 11
        );

        $res = $this->insert('pre_uploads', $data, true, true, 'id');

        $error = $this->db_error;
        $debug = $this->db_debug;

        if ( strlen($error) < 1 ) {
          
          isset($res[0][0]) && $file = $res[0][0];
          $id = count($file) > 0 ? $file['id'] : 0;
          $id < 1 && $error = "System error : the pre-upload file could not be successfully recorded";
        }

        if ( strlen($error) < 1 ) {

          $file_name = SITE_ROOT . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . "preups" . DIRECTORY_SEPARATOR . 
              "raw" . DIRECTORY_SEPARATOR . $id . '.' . $file_ext;
          
          if ( move_uploaded_file($_FILES['file']['tmp_name'], $file_name) ) {
            $info = "File was sucessfully saved";
          }
          else {
            $error = "Could not save file in temporal storage.";

            // remove record from DB
            $this->delete('pre_uploads', $id, 'id');
          }

        }

      } // strlen($error) < 1 - max-file-size
    }
    else {
      strlen($error) < 1 && $error = "An error occurred while uploading the file (" . $_FILES['file']['error'] . ")";
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $file,
      'info' => $info,
    );

    $this->template->assign("preupload_result", $result);

    return ($result);
  }

  public function publish () {
    // change status of a post

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;

    $status = 254;

    $comment_info = array();

    $user_id = $this->template->get_session_value('user_id', 0);
    $rights = $this->template->get_session_value('rights', 0);

    isset($_POST["sts"]) && $status = $_POST["sts"];

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $user_id < 1 && $error = "Please, log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $org_id, $x, $y) = explode(",", $p1, 5);

      $category_id < 1 && $error = "Sorry a category was not specified";
    }

    strlen($error) < 1 && $post_id < 1 && $error = "Sorry invalid request";
    strlen($error) < 1 &&  !array_key_exists($status, array("0" => 1, "1" => 1, "2" => 1, "10" => 0)) && $error = "Sorry invalid post publish status specified";

    if ( strlen($error) < 1 ) {
      // get current and new status info for comment
      $query = "select status_codes.code as current_status, status_codes.description " .
        " as current_description, new.code as new_status, new.description as new_description " . 
        " from status_codes as new, posts inner join status_codes on posts.status = status_codes.id " .
        " where posts.id = " . $post_id . " and new.id = " . $status;

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $comment_info = $res[0][0];
      }

    } 

    if ( strlen($error) < 1 ) {

      $data = array (
        "status" => $status,
      );

      $checks = " and category_id = " . $category_id . " and (" . $rights . " >= 30 or " .
        "assigned_to = " . $user_id . " or created_by = " . $user_id . ")";

      $res = $this->update('posts', $data, $post_id, true, 'id', $checks);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 && count($comment_info) > 0 ) {
        $comment = $this->template->get_session_value('name', 'Someone') .
          " changed status from " . $comment_info["current_status"] . " (" .
          $comment_info["current_description"] . ") to " . $comment_info["new_status"] .
          " (" . $comment_info["new_description"] . ")" . PHP_EOL; 

        $data = array (
          "post_id" => $post_id,
          "comment" => substr($comment, 0, 799),
          "status" => 1,
          "files" => 0
        );

        $res = $this->insert('post_comments', $data, true, true);

        //$error = $this->db_error;
        //DEBUG > 0 && $debug = $this->db_debug;
      }

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "category_id" => $category_id,
          "sts" => $status,
          "post_id" => $post_id,
        ),
      'info' => $info,
    );

    $this->template->assign("publish_post_result", $result);
    
    return ($result);

  }

  public function feature () {
    // feature / unfeature a post

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;

    $status = 254;

    $user_id = $this->template->get_session_value('user_id', 0);
    $rights = $this->template->get_session_value('rights', 0);

    isset($_POST["sts"]) && $status = $_POST["sts"];

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $user_id < 1 && $error = "Please, log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $org_id, $x, $y) = explode(",", $p1, 5);

      $category_id < 1 && $error = "Sorry a category was not specified";
    }

    strlen($error) < 1 && $post_id < 1 && $error = "Sorry invalid request";
    strlen($error) < 1 &&  !array_key_exists($status, array("0" => 1, "1" => 1, "10" => 0)) && $error = "Sorry invalid post feature status specified";

    if ( strlen($error) < 1 ) {

      if ( $status == 0 ) {
        // unfeature

        $checks = " and " . $rights . " >= 30";
        $this->delete('featured_posts', $post_id, 'post_id', $checks);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;
      }
      else {
        // feature - update if record already exists

        $res = $this->get('featured_posts', $post_id, 'post_id', $checks);

        $exists = 0;
        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;

        strlen($error) < 1 && isset($res[0][0]) && $exists = $res[0][0]['post_id'];

        if ( strlen($error) <  1 ) {

          $checks = " and " . $rights . " >= 30";

          if ( $exists > 0 ) {

            $data = array (
              "date" => $this->iso_datetime_now()
            );
        
            $res = $this->update('featured_posts', $data, $post_id, false, 'post_id', $checks);
          }
          else {

            $data = array (
              "post_id" => $post_id,
              "date" => $this->iso_datetime_now()
            );

            $res = $this->insert('featured_posts', $data, true, true, 'post_id');
          }

          $error = $this->db_error;
          DEBUG > 0 && $debug = $this->db_debug;

        } // len(error)
      } // else

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "category_id" => $category_id,
          "sts" => $status,
          "post_id" => $post_id,
        ),
      'info' => $info,
    );

    $this->template->assign("feature_post_result", $result);
    
    return ($result);

  }

  public function assign_wiz1() {
    // get a list of users in an organisation - to assign a post to one of thme

    $error = "";
    $debug = "";

    $post_id = 0;
    $this_org_id = 0;

    $creator_id = $this->template->get_session_value("user_id", 0);
    $rights = $this->template->get_session_value("rights", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);


    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $creator_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $x, $this_org_id, $y) = explode(",", $p1, 4);

      $post_id < 1 && $error = "Sorry invalid request - a post was not specified";
    }

    strlen($error) < 1 && $rights < 30 && $org_id != $this_org_id && $error = "Sorry you do not have sufficient rights to take this action";

    $users = array();
    $post = array();

    if ( strlen($error) < 1 ) {

      $query = "select id, title, assigned_to from posts where id > 0 and id = " .
        $post_id . "; " .
        "select users.id, users.user_name, users.provider, user_details.* from users " .
        "inner join user_details on users.id = user_details.user_id " .
        "where user_details.organisation_id = " . $org_id .
        " and user_details.status in (10) and not user_details.user_id in (" .
        " select assigned_to from posts where posts.id = " . $post_id . ") " .
        " and users.id >= 100 order by user_details.name; ";


      $res = $this->query ($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $post = $res[0][0];
        isset($res[1]) && $users = $res[1];
      }

    }

    $protected = array(
      "_p1_" => $p1,
      "_p2_" => $p2
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "post" => $post,
          "users" => $users
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->template->assign("assign_wiz1", $result);

    // provide view via ajax
    $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "assign_wiz1.php";
    $this->template->provide($view, 'assign_wiz1_result');

    return ($result);
  }

  public function assign () {
    // assign a post to a user

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;

    $assigned_to = 0;

    $rights = $this->template->get_session_value('rights', 0);
    $creator_id = $this->template->get_session_value("user_id", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    isset($_POST["usr"]) && $assigned_to = $_POST["usr"];

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $creator_id < 1 && $error = "Please, log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $this_org_id, $x) = explode(",", $p1, 4);

      $category_id < 1 && $error = "Sorry a category was not specified";
    }

    strlen($error) < 1 && $post_id < 1 && $error = "Sorry invalid request - post could not deduced";
    strlen($error) < 1 && $assigned_to < 1 && $error = "Sorry incorrect request - who to assign to ?";
    strlen($error) < 1 && $rights < 30 && $org_id != $this_org_id && $error = "Sorry you do not have sufficient rights to take this action";

    if ( strlen($error) < 1 ) {

      $data = array (
        "assigned_to" => $assigned_to
      );

      $checks = " and category_id = " . $category_id . " and (" . $rights . " >= 30 or " .
        "assigned_to = " . $creator_id . " or created_by = " . $creator_id . ")";
      $append = " select user_id, name from user_details where user_id = " . $assigned_to;

      $res = $this->update('posts', $data, $post_id, true, 'id', $checks, $append);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        // log comment
        $log_msg = "Assigned by " .
          $this->template->get_session_value("name", "Someone");

        isset($res[1][0]['name']) && $log_msg .= " to " . $res[1][0]['name'];

        $data = array (
          "post_id" => $post_id,
          "comment" => substr($log_msg, 0, 799),
          "status" => 10,
          "files" => 0
        );

        $res = $this->insert('post_comments', $data, true, true);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;

        if ( strlen($error) < 1 ) {
          // notify post assignment, IGNORE errors
          $subject = "Notofication of post assignment";

          $res = $this->notify_post($post_id, $subject);
        }

      } // update error ?

    } // len(error)

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "category_id" => $category_id,
          "post_id" => $post_id,
        ),
      'info' => $info,
      "res" => $res,
    );

    $this->template->assign("assign_result", $result);
    
    return ($result);

  }

  public function get_post_files() {
    // get files for a post (where comment_id = 0) OR comment (comment_id > 0)
    // these will be displayed as thumbs using the _file_thumbs partial

    $error = "";
    $debug = "";

    $this_post_id = 0;
    $this_org_id = 0;

    $post_id = 0;
    $comment_id = 0;
    $info = "";

    $rights = $this->template->get_session_value("rights", 0);

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    // info should be in POST as comment_id*post_id
    isset($_POST["info"]) && $info = $_POST["info"];
    if ( strlen($info) > 0 ) {
      list($comment_id, $post_id) = explode("*", $info, 2);

      $comment_id = strlen($comment_id) > 0 && is_numeric($comment_id) ? $comment_id : 0;
      $post_id = strlen($post_id) > 0 && is_numeric($post_id) ? $post_id : 0;
    }

    if ( strlen($p1) < 1 || strlen($p2) < 1 ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($this_post_id, $x, $this_org_id, $y) = explode(",", $p1, 4);

      $this_post_id < 1 && $error = "Sorry invalid request - invalid post data specified";
    }

    strlen($error) < 1 && $post_id < 1 && $error = "Sorry invalid request - the post was not specified";
    strlen($error) < 1 && $post_id != $this_post_id && $error = "Sorry invalid request - suspect post data specified";

    $files = array();

    if ( strlen($error) < 1 ) {

      $query = "select * from post_files where post_id = " . $post_id . " and " .
        " comment_id = " . $comment_id . " and (" .
        $rights . " >= 80 or status in (10) ) and main < 1 " .
        " order by position, id desc; ";

      $res = $this->query ($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0]) && $files = $res[0];
      }

    }

    $protected = array(
      "_p1_" => $p1,
      "_p2_" => $p2
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "files" => $files,
          "comment_id" => $comment_id,
          "post_id" => $post_id
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->template->assign("get_post_files", $result);

    // provide view via ajax
    $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "_file_thumbs.php";
    $this->template->provide($view, 'get_post_files_result');

    return ($result);
  }

  public function get_file() {
    // get details of a file for download

    $error = "";
    $debug = "";

    $this_post_id = 0;
    $this_org_id = 0;

    $post_id = 0;
    $comment_id = 0;
    $file_id = 0;
    $info = "";

    $rights = $this->template->get_session_value("rights", 0);

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    // info should be in POST as comment_id*post_id
    isset($_POST["info"]) && $info = $_POST["info"];
    if ( strlen($info) > 0 ) {
      list($post_id, $comment_id, $file_id) = explode("*", $info, 3);

      $comment_id = strlen($comment_id) > 0 && is_numeric($comment_id) ? $comment_id : 0;
      $post_id = strlen($post_id) > 0 && is_numeric($post_id) ? $post_id : 0;
      $file_id = strlen($file_id) > 0 && is_numeric($file_id) ? $file_id : 0;
    }

    if ( strlen($p1) < 1 || strlen($p2) < 1 ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($this_post_id, $x, $this_org_id, $y) = explode(",", $p1, 4);

      $this_post_id < 1 && $error = "Sorry invalid request - invalid post data specified";
    }

    strlen($error) < 1 && $file_id < 1 && $error = "Sorry invalid request - the file was not specified";
    strlen($error) < 1 && $post_id < 1 && $error = "Sorry invalid request - the post was not specified";
    strlen($error) < 1 && $post_id != $this_post_id && $error = "Sorry invalid request - suspect post data specified";

    $file = array();

    if ( strlen($error) < 1 ) {

      $query = "select * from post_files where id = " . $file_id .
        " and post_id = " . $post_id . " and " .
        " comment_id = " . $comment_id . " and (" .
        $rights . " >= 80 or status in (10) );";

      $res = $this->query ($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $file = $res[0][0];

        count($file) < 1 && $error = "Requested file not found";
      }

    }

    if ( strlen($error) < 1 ) {
      $file["file_path"] = SITE_ROOT . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . 
        "docs" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR .
        $file["post_id"] . "-" . $file["comment_id"] . "-" . $file["id"] . "." . $file["file_ext"];
    }

    $protected = array(
      "_p1_" => $p1,
      "_p2_" => $p2
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "file" => $file,
          "comment_id" => $comment_id,
          "post_id" => $post_id
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->template->assign("get_file", $result);

    return ($result);
  }

  public function posts_assigned () {
    // get top 10 posts assigned to logged in user
    // static route => automatically called

    $result = $this->get_posts(null, 10, 0);
    $result["data"]["header"] = "Assigned to me";

    $this->template->assign("posts_assigned_result", $result);
  }

  public function assigned_to_me ($user_id = null, $count = null) {
    // get posts assigned to this user - eg from 'Assigned to me' click
    // if count is passed, get lastest $count posts

    $result = $this->get_posts($user_id, $count, 0);

    $result["data"]["header"] = "Posts assigned to me";

    $this->get_menu();
    $this->get_helper_objects();
    $this->template->assign("my_posts_result", $result);
  }

  public function posts_watched () {
    // get all posts watched by logged in user
    // static route => automatically called

    $result = $this->get_posts(null, null, 5);
    $result["data"]["header"] = "Watched by me";

    $this->template->assign("posts_watched_result", $result);
  }

  public function my_posts ($user_id = null, $count = null) {
    // get posts created by this user - eg from 'Created by me' click
    // if count is passed, get lastest $count posts

    $result = $this->get_posts($user_id, $count, 3);
    $result["data"]["header"] = "Posts created by me";

    $this->get_menu();
    $this->get_helper_objects();
    $this->template->assign("my_posts_result", $result);
  }

  public function featured_posts ($count = null) {
    // get featured posts - posts are featured for 7 days from the date featured
    // if count is passed, get $count featured posts

    $result = $this->get_posts(null, $count, 7);
    $result["data"]["header"] = "Featured";

    $this->template->assign("featured_posts_result", $result);
  }

  public function actual_wiz1() {
    // get current actual dates for editing

    $error = "";
    $debug = "";

    $post_id = 0;
    $this_org_id = 0;

    $creator_id = $this->template->get_session_value("user_id", 0);
    $rights = $this->template->get_session_value("rights", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);


    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $creator_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $x, $this_org_id, $y) = explode(",", $p1, 4);

      $post_id < 1 && $error = "Sorry invalid request - a post was not specified";
    }

    strlen($error) < 1 && $rights < 30 && $org_id != $this_org_id && $error = "Sorry you do not have sufficient rights to take this action";

    $post = array();

    if ( strlen($error) < 1 ) {

      $res = $this->get('posts', $post_id);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $post = $res[0][0];
      }

    }

    $protected = array(
      "_p1_" => $p1,
      "_p2_" => $p2
    );

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "post" => $post,
        ),
      'info' => "",
      'protected' => $protected
    );

    $this->template->assign("actual_wiz1", $result);

    // provide view via ajax
    $view = SITE_ROOT . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR . "actual_wiz1.php";
    $this->template->provide($view, 'actual_wiz1_result');

    return ($result);
  }

  public function actual () {
    // set actual dates

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;

    $start_date_actual = null;
    $end_date_actual = null;
    $days_actual = -1;

    $rights = $this->template->get_session_value('rights', 0);
    $creator_id = $this->template->get_session_value("user_id", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    isset($_POST["start_date_actual"]) && strlen($_POST["start_date_actual"]) > 0 && $start_date_actual = $_POST["start_date_actual"];
    isset($_POST["end_date_actual"]) && strlen($_POST["end_date_actual"]) > 0 && $end_date_actual = $_POST["end_date_actual"];
    isset($_POST["days_actual"]) && $days_actual = $_POST["days_actual"];
    !is_numeric($days_actual) && $days_actual = -1;

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $creator_id < 1 && $error = "Please, log in first";
    strlen($error) < 1 && $rights < 30 && $error = "You do not have sufficient rights to take this action";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $this_org_id, $x) = explode(",", $p1, 4);

      $category_id < 1 && $error = "Sorry a category was not specified";
    }

    strlen($error) < 1 && $post_id < 1 && $error = "Sorry invalid request - post could not deduced";
    strlen($error) < 1 && $rights < 30 && $org_id != $this_org_id && $error = "Sorry you do not have sufficient rights to take this action";

    if ( strlen($error) < 1 && (!is_null($start_date_actual) || !is_null($end_date_actual)) ) {
      // if any of start or end dates is not passed, then validate against existing date
      $start_date_actual_validate = $start_date_actual;
      $end_date_actual_validate = $end_date_actual;

      if ( !is_null($start_date_actual) || is_null($end_date_actual) ) {
        $res = $this->get('posts', $post_id, 'id');
        $post = isset($res[0][0]) ? $res[0][0] : array();

        if ( count($post) > 0 ) {
          is_null($start_date_actual_validate) && $start_date_actual_validate = $post["start_date_actual"];
          is_null($end_date_actual_validate) && $end_date_actual_validate = $post["end_date_actual"];
        }
      }

      // can only validate if both validation dates are non-null
      if ( !is_null($start_date_actual_validate) && !is_null($end_date_actual_validate) ) {

        try {
          $interval = $this->template->date_diff ($end_date_actual_validate, $start_date_actual_validate);

          $interval["days"] < 0 && $error = "The end date must be after the start date";
          strlen($error) < 1 && $days_actual > 0 && $days_actual > $interval["days"] && $error = "Actual days, " .
            $days_actual . ", inconsistent with dates " . $start_date_actual_validate . " to " . $end_date_actual_validate .
            " which spans only " . $interval["days"] . " days";
        }

        catch (Exception $e) {
          $error = "There was a problem with the dates : " .
            $start_date_actual_validate . ", " . $end_date_actual_validate . "; " . $e->getMessage();
        }
      }

    }

    if ( strlen($error) < 1 ) {

      $data = array ();
      !is_null($start_date_actual) && $data["start_date_actual"] = $start_date_actual;
      !is_null($end_date_actual) && $data["end_date_actual"] = $end_date_actual;
      $days_actual >= 0 && $data["days_actual"] = $days_actual;

      $checks = " and category_id = " . $category_id . " and " . $rights . " > 30";

      if ( count(array_keys($data)) > 0 ) {

        $res = $this->update('posts', $data, $post_id, true, 'id', $checks, null);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;
      }

    } // len(error)

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "category_id" => $category_id,
          "post_id" => $post_id,
        ),
      'info' => $info,
    );

    $this->template->assign("actual_result", $result);
    
    return ($result);

  }

  public function watch () {
    // watch / unwatch posts for currently logged in user

    $error = "";

    $post_id = 0;

    $status = isset($_POST["sts"]) ? $_POST["sts"] : -1;
    $user_id = $this->template->get_session_value("user_id", 0);

    $p1 = isset($_POST["_p1_"]) ? $_POST["_p1_"] : "";
    $p2 = isset($_POST["_p2_"]) ? $_POST["_p2_"] : "";

    $user_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 && (strlen($p1) < 1 || strlen($p2) < 1) ) {
      $error = "Action prohibited - authenticity of request is in doubt";
    }

    if ( strlen($error) < 1 && !$this->is_equal($p1, $p2) ) {
      $error = "Action prohibited - validity of request could not be confirmed";
    }

    if ( strlen($error) < 1 ) {
      !array_key_exists($status, array(0 => 0, 1 => 1)) && $error = "Invalid request";
    }

    if ( strlen($error) < 1 ) {
      list ($post_id, $category_id, $this_org_id, $x) = explode(",", $p1, 4);

      $category_id < 1 && $error = "Sorry a category was not specified";
    }
    
    if ( strlen($error) < 1 ) {
      $result = $status == 1 ? $this->add_post_watcher ($post_id, $user_id) : $this->remove_post_watcher ($post_id, $user_id);
    }
    else {
      $result = array (
        'errors' => array (
            array('message' => $error, 'debug' => "")
          ),
        'data' => null,
        'info' => "",
      );

    }

    $this->template->assign("watch_post_result", $result);
    
    return ($result);
  }

  public function notify_post ($post_id = null, $subject = null) {
    // send a notification email to the creator, assigned_to and watchers

    $debug = "";
    $error = "";
    $info = "";

    $user_id = $this->template->get_session_value("User_id", 0);

    $post = array();
    $watchers = array();

    is_null($post_id) && $post_id = 0;
    is_null($subject) && $subject = "";

    $post_id < 1 && $error = "Sorry a post was not specified";

    if ( strlen($error) < 1 ) {
      $query = "select posts.id, posts.title, posts.post, posts.priority, posts.category_id, posts.status, " .
          " posts.assigned_to, posts.created_by, priorities.priority, priorities.description, " .
          " categories.category, " .
          " creator.email as creator_email, creator.name as creator_name, " .
          " assigned.email as assigned_email, assigned.name as assigned_name from posts " .
          " left outer join priorities on posts.priority = priorities.id " .
          " inner join categories on posts.category_id = categories.id " .
          " inner join user_details as creator on posts.created_by = creator.user_id " .
          " left outer join user_details as assigned on posts.assigned_to = assigned.user_id " .
          " where posts.id = " . $post_id . "; " .
          " select user_details.name, user_details.email from post_watchers " .
          " inner join posts on post_watchers.post_id = posts.id " .
          " inner join user_details on post_watchers.user_id = user_details.user_id " .
          " where post_watchers.post_id = " . $post_id . " and not post_watchers.user_id in " .
          " (posts.created_by, posts.assigned_to); ";

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      if ( strlen($error) < 1 ) {
        isset($res[0][0]) && $post = $res[0][0];
        count($post) < 1 && $error = "Sorry the post specified was not found";

        isset($res[1]) && $watchers = $res[1];
      }

    }

    if ( strlen($error) < 1 ) {
      strlen($subject) < 1 && $subject = $post["title"];

      // only cc if there is an assigned to and/or watchers
      // ASSUME valid emails
      $cc = array();
      $mail_to = null;

      if ( $post["assigned_to"] < 1 ) {
        $mail_to = $post["creator_email"];
      }
      else {
        // assigned to exists
        $mail_to = $post["assigned_email"];
        $cc[] = array($post["creator_email"] => $post["creator_name"]);
      }

      if ( !is_null($mail_to) ) {

        foreach ( $watchers as $w ) {
          #$cc[] = "'" . $w["email"] . "' => '" . $w["name"] . "'";
          $cc[] = $w["email"];
        }

        $mailer = new Mailer_Lib();

        $url = $this->get_site_url();
        $post_url = $url . "/posts/show/" . $post["id"];

        $message = $post["post"];
        strlen($message) > 100 && $message = substr($message, 0, 99) . "..";

        $message = "Post: <a href='" . $post_url . "'>" . $post["title"] . "</a>\r\n" .
          "Category: " . $post["category"] . "\r\n" .
          "Priority: " . $post["priority"] . "\r\n\r\n" .
          $message . "\r\n\r\n" .
          "<a href='" . $post_url . "'>" . $post_url . "</a>\r\n\r\n" .
          "Support\r\nSimple Tracker\r\n" . $url . "\r\n";

        $ret = $mailer->swiftmail($this->mail['mandrill'], $mail_to, $subject, $message, $this->mail['support'], $this->mail['support_name'], $this->admin_email, $cc);

        if ( !$ret ) {
          $info = "Unable to send notification email";
        }
      } // !is_null
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "post_id" => $post_id,
        ),
      'info' => $info,
    );
    
    return ($result);

  }






  public function stats ($category_id = null) {
    // stats
    // if category is passed, confine to that category
    // only relevant for logged in users

    $error = "";
    $debug = "";

    is_null($category_id) && $category_id = 0;

    $user_id = $this->template->get_session_value('user_id', 0);
    $rights = $this->template->get_session_value('rights', 0);
    $org_id = $rights >= 80 || $user_id < 1 ? $this->template->get_session_value('org_id', 0) : $this->template->get_session_value('user_org_id', 0);

    $user_id < 1 && $error = "Please log in first";
    strlen($error) < 1 && $org_id < 1 && $error = "Sorry an organisation could not be deduced.";

    $stats = array();

    if ( strlen($error) < 1 ) {

      $main = $this->stats_main($category_id, $org_id);

      if ( isset($main["errors"][0]) ) {

        $error = $main["errors"][0]["message"];
        DEBUG > 0 && $debug = $main["errors"][0]["debug"];

        strlen($error) < 1 && $stats["main"] = $main["data"]["stats_main"];
      }
      else {
        $error = "There was an error getting main stats";
      }

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => $stats,
      'info' => "",
      'protected' => ""
    );

    $this->template->assign("stats", $result);

    return ($result);
  }





  private function stats_main ($category_id = null, $org_id = null) {
    // stats main - just retrieve the results

    $error = "";
    $debug = "";

    is_null($category_id) && $category_id = 0;
    is_null($org_id) && $org_id = 0;

    $stats_main = array();

    $query = "select m.*, status_codes.id, status_codes.code, status_codes.icon, " .
        " status_codes.description from (select posts.status, count(posts.status) as count " .
        " from posts inner join categories on posts.category_id = categories.id where " .
        " posts.status in (2, 10, 11) and categories.organisation_id = " . $org_id . 
        " and (" . $category_id . " < 1 or category_id = " . $category_id . ") group by posts.status ) as m " .
        " right outer join status_codes on m.status = status_codes.id " .
        " where status_codes.id in (2, 10, 11) order by m.status; ";

    $res = $this->query($query);

    $error = $this->db_error;
    DEBUG > 0 && $debug = $this->db_debug;

    if ( strlen($error) < 1 ) {

      isset($res[0]) && $stats_main = $res[0];
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "stats_main" => $stats_main,
        ),
      'info' => "",
      'protected' => ""
    );

    return ($result);
  }

  private function get_posts ($user_id = null, $count = null, $type = null) {
    // get posts assigned to a user (type 0 - default), posted created (type 3), posts watched (type 5)
    // posts featured (type 7)
    // if count is passed, get lastest $count posts

    $error = "";
    $debug = "";

    is_null($type) && $type = 0;

    $posts = array();

    $rights = $this->template->get_session_value('rights', 0);
    $creator_id = $this->template->get_session_value("user_id", 0);

    $org_id = $this->template->get_session_value('user_org_id', 0);
    $rights >= 80 && $org_id = $this->template->get_session_value('org_id', 0);

    is_null($user_id) && $user_id = 0;
    is_null($count) && $count = 0;

    $user_id < 1 && $user_id = $creator_id;

    $creator_id < 1 && $error = "Please log in first";

    if ( strlen($error) < 1 ) {

      // pressume type 0 (assigned)
      switch ( $type ) {
        case 3: // created
          $where = " posts.status in (1, 2, 10, 11) and categories.organisation_id = " . $org_id .
            " and posts.created_by = " . $user_id;
          break;

        case 5: // watched
          $where = " posts.status in (2, 10, 11) and categories.organisation_id = " . $org_id .
            " and posts.id in ( select post_id from watcher_posts where user_id = " . $user_id . ")";
          break;

        case 7: // featured
          $date = new DateTime();
          $date = $date->sub(new DateInterval('P7D'));
          
          $where = " posts.status in (2, 10) and categories.organisation_id = " . $org_id .
            " and posts.id in ( select post_id from featured_posts where date >= '" .
            $date->format('Y-m-d H:i') . "')";
          break;

        default: // assigned to
          $where = " posts.status in (10, 11) and categories.organisation_id = " . $org_id .
            " and posts.assigned_to = " . $user_id;
      }

      $query = "select posts.*, categories.category, " .
        " assigned_to.name as assigned_to_name, assigned_to.image_url as assigned_to_image, " .
        " creator.name as creator_name, creator.image_url as creator_image, " .
        " categories.type as category_type " .
        " from posts left outer join user_details as assigned_to on posts.assigned_to = assigned_to.user_id " .
        " left outer join user_details as creator on posts.created_by = creator.user_id " .
        " inner join categories on posts.category_id = categories.id where " .
        $where .
        " order by posts.position, posts.status desc, posts.priority desc, posts.id desc";

      $count > 0 && $query = $this->add_limit_clause($query, $count);

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
          "user_id" => $user_id,
          "count" => $count
        ),
      'info' => "",
      'protected' => ""
    );

    return ($result);
  }

  private function add_post_watcher ($post_id = null, $user_id = null) {
    // add a watcher to a post
    $debug = "";
    $error = "";
    $info = "";

    $row = array();

    is_null($post_id) && $post_id = 0;
    !is_numeric($post_id) && $post_id = 0;

    is_null($user_id) && $user_id = 0;
    !is_numeric($user_id) && $user_id = 0;

    $post_id < 1 && $error = "A post to watch is required";
    strlen($error) < 1 && $user_id < 1 && $error = "A user watching this post is required";

    if ( strlen($error) < 1 ) {
      $sql = "select * from post_watchers where post_id = " . $post_id .
        " and user_id = " . $user_id;

      $res = $this->query($sql);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      !is_null($res) && isset($res[0][0]) && $row = $res[0][0];
    }

    if ( strlen($error) < 1 && count($row) < 1 ) {

      $sql = "insert into post_watchers (post_id, user_id) values (" . $post_id .
        ", " . $user_id . "); insert into watcher_posts (user_id, post_id) " .
        "values (" . $user_id . ", " . $post_id . "); " .
        "select * from post_watchers where post_id = " . $post_id .
        " and user_id = " . $user_id;

      $sql = $this->set_nocount($sql);

      $res = $this->query($sql);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      !is_null($res) && isset($res[0][0]) && $row = $res[0][0];
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "id" => $post_id,
          "sts" => 1,
          "row" => $row,
        ),
      'info' => $info,
    );
    
    return ($result);
  }

  private function remove_post_watcher ($post_id = null, $user_id = null) {
    // remove a watcher from a post
    $debug = "";
    $error = "";
    $info = "";

    is_null($post_id) && $post_id = 0;
    !is_numeric($post_id) && $post_id = 0;

    is_null($user_id) && $user_id = 0;
    !is_numeric($user_id) && $user_id = 0;

    $post_id < 1 && $error = "A post being watched is required";
    strlen($error) < 1 && $user_id < 1 && $error = "A user watching this post is required";

    if ( strlen($error) < 1 ) {
      $sql = "delete from post_watchers where post_id = " . $post_id .
        " and user_id = " . $user_id . "; delete from watcher_posts where " .
        " user_id = " . $user_id . " and post_id = " . $post_id . "; " .
        " select * from users where id = -1; ";

      $sql = $this->set_nocount($sql);

      $res = $this->query($sql);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "id" => $post_id,
          "sts" => 0
        ),
      'info' => $info,
    );
    
    return ($result);
  }





}
