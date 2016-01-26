
<?php
//print_r($this->params["show_post_result"]);

$errors = $this->get_errors($this->params["show_post_result"]);
$protected = $this->get_protected_info($this->params["show_post_result"]);
$category = isset($this->params["show_post_result"]["data"]["category"]) ? $this->params["show_post_result"]["data"]["category"] : array();
$status_codes = isset($this->params["show_post_result"]["data"]["status_codes"]) ? $this->params["show_post_result"]["data"]["status_codes"] : array();

$btn_label = isset($this->params["show_post_result"]["data"]["btn_label"]) ? $this->params["show_post_result"]["data"]["btn_label"] : "";

include("_posts_header.php");

//print_r($protected);

if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {

  $post = array();
  isset($this->params["show_post_result"]["data"]["post"]) && $post = $this->params["show_post_result"]["data"]["post"];

  $files = array();
  isset($this->params["show_post_result"]["data"]["images"]) && $files = $this->params["show_post_result"]["data"]["images"];

  $main_image = array();
  isset($this->params["show_post_result"]["data"]["main_image"]) && $main_image = $this->params["show_post_result"]["data"]["main_image"];

  $comments = array();
  isset($this->params["show_post_result"]["data"]["comments"]) && $comments = $this->params["show_post_result"]["data"]["comments"];

  $featured = array();
  isset($this->params["show_post_result"]["data"]["featured"]) && $featured = $this->params["show_post_result"]["data"]["featured"];

  if ( count($post) > 0 ) {

    $name = $post["creator_name"];
    $image = $post["creator_image"];
    $user_id = $post["created_by"];
    $caption = "Created by";

    if ( $post["assigned_to"] > 0 ) {
      $name = $post["assigned_to_name"];
      $image = $post["assigned_to_image"];
      $user_id = $post["assigned_to"];
      $caption = "Assigned to";
    }

    $title = $this->escape_string($post["title"]) . " - " . $post["id"];

    $commenting = $post["commenting"];

    $can_action = $post["can_action"];
    $publish_actions = $post["publish_actions"];
    $can_feature = $post["can_feature"];
    $watching = $post["watching"];

    $status = $post["status"];

    $url = $this->escape_string($post["url"]);

    $post_id = $post["id"];

    $show_guestimates = $post["show_guestimates"];
?>

<div class="row">
  <div class="col-md-12">

    <div class="media">
      <a class="media-left" href="/users/show/<?php echo $user_id; ?>">
        <img class="media-object thumb" src="<?php echo $image; ?>" alt="<?php echo $this->escape_string($caption) . ' ' . $this->escape_string($name); ?>" title="<?php echo $this->escape_string($caption) . ' ' . $this->escape_string($name); ?>">
        <?php echo $name; ?>
      </a>
      <div class="media-body">
        <h4 class="media-heading">
          <?php
            echo $title;
            echo $this->configs["mode"] == 'tracker' ? $this->app_helper->get_priority_html($post["priority"]) : "";
          ?> 
        </h4>

          <ul class="list-inline bg-info pad4">
            <li title='This item was posted ...'>
              <span class='fa fa-file'></span>  
              <?php
                echo $this->time_diff($post['created_at']) .
                  " ago at " .
                  $this->get_datetime_string($post['created_at']); 
              ?>
            </li>

            <li class="pull-right">
              <?php
                echo $this->app_helper->get_status_html($status);
              ?>
            </li>

            <?php
            if ( $can_action > 0 ) {

              if ( $this->configs["mode"] == 'tracker' ) {

                echo "<li class='pull-right' title='Set actual dates'>" .
                  "<a class='btn btn-info btn-sm add-post p-a-0' data-action='actual-form' href='#'>" .
                    "Actual</a>" .
                  "</li>";

                echo "<li class='pull-right' title='Assign post to someone'>" .
                  "<a class='btn btn-info btn-sm add-post p-a-0' data-action='assign-form' href='#'>" .
                    "Assign</a>" .
                  "</li>";
              }

              // publish actions ?
              foreach ( $publish_actions as $act ) {

                echo "<li id='publish" . $act["code"] . "' title='" . $act["title"] . "' class='pull-right'>" .
                  "<a class='btn btn-info btn-sm add-post p-a-0' data-action='publish' href='#' data-sts='" . $act["code"] . "'>" .
                    $act["action"] . "</a>" .
                  "</li>";
              }

            }

            if ( $can_feature > 0 ) {
              $sts = 1;
              $btn = 'Feature';
              $titl = 'Feature this post';

              if ( count($featured) > 0 ) {
                $duration = $this->date_diff ($featured['date'], null);

                if ( $duration["days"] < 7 ) {
                  // after 7 days a featured item expires
                  $sts = 0;
                  $btn = 'Unfeature';
                  $titl = 'Remove this post from the featured list';
                }
              }

              echo "<li id='feature' title='" . $titl . "' class='pull-right'>" .
                "<a class='btn btn-info btn-sm add-post p-a-0' data-action='feature' href='#' data-sts='" . $sts . "'>" .
                  $btn . "</a>";
                "</li>";
            }

            // watching
            $sts = 1;
            $btn = 'Watch';
            $titl = 'Watch this post';

            if ( $watching > 0 ) {
              $sts = 0;
              $btn = 'Unwatch';
              $titl = 'Stop watching this post';
            }

            echo "<li id='watch' title='" . $titl . "' class='pull-right'>" .
              "<a class='btn btn-info btn-sm add-post p-a-0' data-action='watch' href='#' data-sts='" . $sts . "'>" .
                $btn . "</a>";
              "</li>";

            ?>

          </ul>

          <?php
          if ( $show_guestimates > 0 ) {
          ?>

            <ul class="list-inline pad4 m-l-0 post-guestimates">
              <li>
                <span class="text-muted">
                  Proposed
                </span>
                <span class="block">
                  <?php
                    echo $post["days_proposed"] < 0 ? 'Not set' : $post["days_proposed"] . " days";
                  ?>
                </span>
              </li>

              <li>
                <span class="text-muted">
                  Start
                </span>
                <span class="block">
                  <?php
                    echo $this->get_datetime_string($post["start_date_proposed"], 'Not set', 'Y-m-d');
                  ?>
                </span>
              </li>
              <li>
                <span class="text-muted">
                  End
                </span>
                <span class="block">
                  <?php
                    echo $this->get_datetime_string($post["end_date_proposed"], 'Not set', 'Y-m-d');
                  ?>
                </span>
              </li>

              <li class="<?php echo $post['proposed_lag_class']; ?> m-r">
                Exp
                <span class="block">
                  <?php
                    echo $post["proposed_lag"];
                  ?>
                </span>
              </li>

              <li>
                <span class="text-muted">
                  Actual
                </span>
                <span class="block">
                  <?php
                    echo $post["days_actual"] < 0 ? 'Not set' : $post["days_actual"] . " days";
                  ?>
                </span>
              </li>
              <li>
                <span class="text-muted">
                  Start
                </span>
                <span class="block">
                  <?php
                    echo $this->get_datetime_string($post["start_date_actual"], 'Not set', 'Y-m-d');
                  ?>
                </span>
              </li>
              <li>
                <span class="text-muted">
                  End
                </span>
                <span class="block">
                  <?php
                    echo $this->get_datetime_string($post["end_date_actual"], 'Not set', 'Y-m-d');
                  ?>
                </span>
              </li>
              <li class="<?php echo $post['actual_lag_class']; ?>">
                +/-
                <span class="block">
                  <?php
                    echo $post["actual_lag"];
                  ?>
                </span>
              </li>


            </ul>

          <?php
          }
          ?>


      </div>
    </div>
  </div>
</div>


<div class="row p-b">
  <div class="col-md-12">

    <?php
      if ( count($main_image) > 0 ) {        
        $img_src = "/assets/images/posts/" . $post_id . "-" . $main_image["comment_id"] . "-" . $main_image["id"] . "-im." . $main_image["file_ext"];

        echo "<img src='" . $img_src . "' class='main-image' title='" . $title . "' alt='" . $title . "' />";
      }

      echo nl2br($this->escape_string($post["post"]));
    ?>

  </div>
</div>

  <?php
    if ( strlen($url) > 0 ) {
  ?>
    <div class="row p-b">
      <div class="col-md-12">
        <span class="fa fa-link"></span>
        <a target="_new" href="<?php echo $url; ?>">
          <?php echo $url; ?>
        </a>
      </div>
    </div>

  <?php
    }
  ?>

  <?php
    if ( count($files) > 0 ) {
  ?>

    <div class="row p-t p-b">
      <div class="col-md-12">

        <h5>
          Gallery
        </h5>

        <?php include("_file_thumbs.php"); ?>

      </div>
    </div> 


  <?php
    } // count images

    if ( count($comments) > 0 ) {
  ?>

    <div class="row p-b">
      <div class="col-md-12">

        <h5>
          Comments
        </h5>

        <ul class="list-unstyled comments">

        <?php
          foreach ($comments as $item) {
            $image = $item["creator_image"];
            $name = $item["creator_name"];
            $status = $item["status"];
            $files = $item["files"];

            $id = $item['id'];
        ?>
            <li class='p-b'>

              <div class="media">
                <a class="media-left" href="#">
                  <img class="media-object thumb thumb-sm" src="<?php echo $image; ?>" alt="<?php echo $this->escape_string($name); ?>" title="<?php echo $this->escape_string($name); ?>">
                  <?php echo $name; ?>
                </a>
                <div class="media-body">

                  <ul class="list-inline bg-info pad4">
                    <li>
                      <span class="fa fa-file"></span> 
                      <?php
                        echo $this->time_diff($item['created_at']) .
                          " ago at " .
                          $this->get_datetime_string($item['created_at']); 
                      ?>
                    </li>

                    <?php
                    if ( $can_action > 0 ) {
                      $sts = $status == 10 ? 1 : 10;
                      $btn = $sts == 10 ? 'Publish' : 'Unpublish';
                      $titl = $sts == 10 ? 'Publish this comment' : 'Remove comment from public view';

                      echo "<li title='" . $titl . "' class='pull-right' id='c" . $id . "'>" .
                        "<a class='btn btn-info btn-sm add-comment p-a-0' href='#' data-sts='" . $sts . "' data-id='" . $id . "' data-action='publish'>" .
                        $btn . "</a>";
                    }
                    ?>

                  </ul>

                  <?php
                    echo nl2br($this->escape_string($item["comment"])); 

                    if ( $files > 0 ) {
                  ?>

                      <div id="c-images<?php echo $id;?>" class="block c-files" data-info="<?php echo $id . "*" . $item["post_id"]; ?>">
                      </div>

                  <?php
                    }
                  ?>

                </div>
              </div>
            </li>

        <?php
          }
        ?>

        </ul>
      </div>
    </div> 

<?php
    } // count comments

    if ( $commenting > 0 ) {
?>

<form id="comment-form" class="add-comment-form">
  <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
  <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

  <div class="row">
    <div class="col-md-12">

      <fieldset class="form-group">
        <label class="bg-info pad4 block">
           Add new comment - 800 characters maximum, non-editable.
          <span class="" id="comment-msg">
          </span>
        </label>
        <textarea type="text" class="form-control count-chars" data-max="800" name="comment" id="comment" rows="4"></textarea>
      </fieldset>

    </div>
  </div>

  <div class="form-group row">

    <div class="col-md-12 col-sm-12">

      <label class="block">
        Upload new files (2G max)
      </label>

      <input type="file" multiple accept="image/*" name="file" id="images" data-info="1">
      <div id="file_list">
      </div>

    </div>
  </div>

  <div class="row">
    <div class="col-md-9 col-sm-12">
      <div id='comment-form-error' class='text-warning'>
      </div>
    </div>

    <div class="col-md-3 col-sm-12">
      <fieldset class="form-group pull-right">
        <button type="button" class="btn btn-primary btn-sm add-comment" data-action="submit">
          <span class="fa fa-play"></span>
          Add comment
        </button>
      </fieldset>
    </div>
  </div>

</form>

<?php
    } // commenting

  } // count post
}

?>

