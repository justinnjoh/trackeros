<?php 
// partial for displaying file thumbs
// data is assumed to be in params["get_files_result"] OR in $files

$errors = "";

if ( !isset($files) || count($files) < 1 ) {
  $errors = $this->get_errors($this->params["get_post_files"]);
  strlen($errors) < 1 && isset($this->params["get_post_files"]["data"]["files"]) && $files = $this->params["get_post_files"]["data"]["files"];
}

if ( strlen($errors) > 0 ) {
  echo $errors;
}
else {

  if ( !is_null($files) && count($files) > 0 ) {
?>

    <ul class="list-inline thumbs">

<?php
      foreach ($files as $item) {
        $id = $item["id"];
        $post_id = $item["post_id"];
        $comment_id = $item["comment_id"];
        $caption = $this->escape_string($item['caption']);

        $show_caption = $caption;
        strlen($show_caption) > 20 && $show_caption = substr($show_caption, 0, 19) . "...";

        $file_uri = $post_id . "*" . $comment_id . "*" . $id ;

        echo "<li>" . PHP_EOL;

        if ( $item["is_image"] ) {
          $img_src = "/assets/images/posts/" . $post_id . "-" . $comment_id . "-" . $id . "-im." . $item["file_ext"];
          $thumb_src = "/assets/images/posts/" . $post_id . "-" . $comment_id . "-" . $id . "-th." . $item["file_ext"];

          echo "<a href='#' data-img='" . $file_uri . "' title='" . $show_caption . "' class='show-image'>" .
            "<img src='" . $thumb_src . "' class='thumb thumb-sm' />" . $show_caption .
            "</a>" . PHP_EOL;
        }
        else {
          echo "<a href='#' data-img='" . $file_uri . "' title='" . $show_caption . "'>" .
            "<img src='/assets/images/posts/0.png' class='thumb thumb-sm' />" . $show_caption .
            "</a>" . PHP_EOL;
        }

        echo "<a href='#' class='block add-post' data-action='get-file' data-info='" . $file_uri . "' title='Click to download file'>" .
          "<span class='fa fa-download'></span>" .
          "</a>" .
          "</li>";
      }

?>
    </ul>

<?php
  } // files

} // no error
?>
