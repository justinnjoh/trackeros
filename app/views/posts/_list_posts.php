<?php
// list posts partial
// posts data is  presumed in variable $posts

$posts = isset($posts) ? $posts : array();
$header = isset($header) ? $header : null;

if ( !is_null($header) ) {
?>

<div class="row bg-success">
  <div class="col-sm-12 col-md-8">
    <strong>
      <?php echo $header; ?>
    </strong>
  </div>
</div>

<?php 
}
?>

<ul class="list-unstyled">

  <?php
    foreach ( $posts as $item ) {
      $name = $item["creator_name"];
      $image = $item["creator_image"];
      $user_id = $item["created_by"];
      $caption = "Created by";

      $title = $this->escape_string($item["title"]) . " - " . $item["id"];

      if ( $item["assigned_to"] > 0 ) {
        $name = $item["assigned_to_name"];
        $image = $item["assigned_to_image"];
        $user_id = $item["assigned_to"];
        $caption = "Assigned to";
      }

      $category_type = $item["category_type"]; // enable deep (crawlable) links only for type 10
  ?>

    <div class="media">
      <a class="media-left" href="/users/show/<?php echo $user_id; ?>">
        <img class="media-object thumb" src="<?php echo $image; ?>" alt="<?php echo $this->escape_string($caption) . ' ' . $this->escape_string($name); ?>" title="<?php echo $this->escape_string($caption) . ' ' . $this->escape_string($name); ?>">
        <span title="<?php echo $caption; ?>">
          <?php echo $name; ?>
        </span>
      </a>
      <div class="media-body">
        <h4 class="media-heading">
          <?php
            if ( $category_type == 10 ) {
          ?>
              <a href="/posts/show/<?php echo $item['id']; ?>" class="show-post">
                <?php echo $title; ?>
              </a>
          <?php 
            }
            else {
          ?>
             <a href="#" data-id="<?php echo $item['id']; ?>" data-action="show" class="add-post">
                <?php echo $title; ?>
              </a>
 
          <?php
            }
 
            echo $this->configs["mode"] == 'tracker' ? $this->app_helper->get_priority_html($item["priority"]) : "";
          ?>
        </h4>

        <ul class="list-inline bg-info pad4">
          <li title='This item was posted ...'>
            <span class="fa fa-file"></span> 
            <?php
              echo $this->time_diff($item['created_at']) .
                " ago at " .
                $this->get_datetime_string($item['created_at']); 
            ?>
          </li>

          <li class="pull-right">
            <?php
              echo $this->app_helper->get_status_html($item['status']);
            ?>
          </li>
        </ul>

        <?php echo nl2br($this->escape_string($item["post"])); ?>
      </div>
    </div>

  <?php
    }
  ?>

</ul>
