<?php
// list posts partial
// posts data is  presumed in variable $posts

$posts = isset($posts) ? $posts : array();
?>

<ul class="list-unstyled">

  <?php
    foreach ( $posts as $item ) {
      $post = strlen($item["post"]) > 50 ? substr($item["post"], 0, 49) . "..." : $item["post"]; 
      $post = $this->escape_string($post); 

      $date = $item["created_at"];
      $caption = "Created at";

      if ( !is_null($item["end_date_proposed"]) ) {
        $date = $item["end_date_proposed"];
        $caption = "Proposed due date";
      }

      $category_type = $item["category_type"]; // enable deep (crawlable) links only for type 10
  ?>

    <div class="media">
      <div class="media-body">
        <h5 class="media-heading">

          <?php
            if ( $category_type == 10 ) {
          ?>

            <a href="/posts/show/<?php echo $item['id']; ?>" class="show-post">
              <?php echo $this->escape_string($item["title"]); ?>
            </a>
          <?php 
            }
            else {
          ?>

             <a href="#" data-id="<?php echo $item['id']; ?>" data-action="show" class="add-post">
                <?php echo $this->escape_string($item["title"]); ?>
              </a>

          <?php
            }
            echo $this->configs["mode"] == 'tracker' ? $this->app_helper->get_priority_html($item["priority"]) : "";
          ?>
        </h5>

        <em class="block">
        <?php
          echo $caption . ": " . $this->get_datetime_string($date); 
        ?>
        </em>

        <?php
          echo nl2br($post); 
        ?>
      </div>
    </div>

  <?php
    }
  ?>

</ul>
