<?php
// comment model

class Comment_Model extends Base_model {

  public function add () {
    // add a comment

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;
    $post_status = 0;
    $commenting = -1;

    $notify_subject = "Tracker Notification - new comment added for post";

    $id = 0;
    $comment = "";

    $docs_path = SITE_ROOT . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR .
      "docs" . DIRECTORY_SEPARATOR . "posts";

    $user_id = $this->template->get_session_value('user_id', 0);

    isset($_POST["comment"]) && $comment = $_POST["comment"];

    //isset($_POST["id"]) && $id = $_POST["id"]; // for now, no comment edits

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
      list ($post_id, $category_id, $org_id, $posts_status, $commenting) = explode(",", $p1, 5);

      is_null($commenting) && $commenting = -1;
      !is_numeric($commenting) && $commenting = -1;

      $post_id < 1 && $error = "Sorry a post for this comment was not specified";
    }

    strlen($error) < 1 &&  $commenting < 0 && $error = "Sorry, the authenticity of this request is in doubt";
    strlen($error) < 1 &&  !array_key_exists($commenting, array("0" => 1, "10" => 1, "11" => 0)) && $error = "Sorry invalid comments status specified";
    strlen($error) < 1 &&  $commenting == 0 && $error = "Sorry, comments are not allowed for this post";

    if ( strlen($error) < 1 ) {

      $data = array (
        "post_id" => $post_id,
        "comment" => substr($comment, 0, 799),
      );

      if ( $id > 0 ) {

      	// this is not used now; comments may not be edited

        $notify_subject = "Tracker Notification - comment updated for post";

        $res = $this->update('post_comments', $data, $id, true);
      }
      else {
        $data["files"] = 0;
        $data["status"] = $commenting;
        $res = $this->insert('post_comments', $data, true, true);
      }

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      strlen($error) < 1 && isset($res[0][0]) && $id = $res[0][0]["id"];

      $files_added = 0;

      if ( $id > 0 ) {
        // notify
        $u = new Post_model($this->template, $this->query_string);
        $x = $u->notify_post($post_id, $notify_subject); 

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
                "comment_id" => $id,
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

                  $files_added += 1;

                  // move file to raw images directory - by renaming
                  $raw_file_name = $docs_path . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR .
                    $post_id . "-" . $id . "-" . $image_id . "." . $file_ext;

                  if ( rename($preup_file_name, $raw_file_name) ) {

                    // resize image files only
                    if ( $is_image > 0 ) {
                      $thumb_name = $thumbs_path . DIRECTORY_SEPARATOR . $post_id . "-" . $id . "-" . $image_id . "-th." . $file_ext;
                      $image_name = $thumbs_path . DIRECTORY_SEPARATOR . $post_id . "-" . $id . "-" . $image_id . "-im." . $file_ext;

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

          // if files added, set count in comment record
          if ( $files_added > 0 ) {

            $data = array (
              "files" => $files_added
            );

            $res = $this->update('post_comments', $data, $id, true);

            strlen($this->db_error) > 0 && $error .= "; " . $this->db_error;
            DEBUG > 0 && $debug .= "; " . $this->db_debug; 

          }

        } // count(new files) > 0

      } // id > 0 

    } // len(error) - comment entries validation

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          "id" => $id,
          "post_id" => $post_id,
        ),
      'info' => $info,
    );

    $this->template->assign("add_comment_result", $result);
    
    return ($result);

  }

  public function publish () {
    // change status of a comment

    $debug = "";
    $error = "";
    $info = "";

    $post_id = 0;
    $category_id = 0;
    $org_id = 0;

    $id = 0;
    $status = 254;

    $user_id = $this->template->get_session_value('user_id', 0);
    $rights = $this->template->get_session_value('rights', 0);

    isset($_POST["id"]) && $id = $_POST["id"];
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

      $post_id < 1 && $error = "Sorry a post for this comment was not specified";
    }

    strlen($error) < 1 &&  $id < 0 && $error = "Sorry, invalid request";
    strlen($error) < 1 &&  !array_key_exists($status, array("0" => 1, "1" => 1, "10" => 0)) && $error = "Sorry invalid comment publish status specified";

    if ( strlen($error) < 1 ) {

      $data = array (
        "status" => $status,
      );

      $checks = " and post_id = " . $post_id . " and " . $rights . " >= 30";

      $res = $this->update('post_comments', $data, $id, true, 'id', $checks);

      $error = $this->db_error;
      DEBUG > 0 && $debug = $this->db_debug;

      strlen($error) < 1 && isset($res[0][0]) && $id = $res[0][0]["id"];

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array (
          "id" => $id,
          "sts" => $status,
          "post_id" => $post_id,
        ),
      'info' => $info,
    );

    $this->template->assign("publish_comment_result", $result);
    
    return ($result);

  }






}
?>


