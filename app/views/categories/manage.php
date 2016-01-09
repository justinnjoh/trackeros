<?php

  //print_r($this->params["categories"]);

  $errors = $this->get_errors($this->params["manage_categories"]);
  $data = isset($this->params["manage_categories"]["data"]) ? $this->params["manage_categories"]["data"] : array();

  $organisation = "";
  isset($this->params["manage_categories"]["data"]["organisation"]) && $organisation = $this->params["manage_categories"]["data"]["organisation"];
  $organisation = strlen($organisation) > 0 ? "Manage categories for " . $organisation : "";

  $categories = array();
  isset($this->params["manage_categories"]["data"]["categories"]) && $categories = $this->params["manage_categories"]["data"]["categories"];
?>

<div class="row">
  <div id="global-item" class="col-md-12 global-item">
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <h5>
      <?php 
        echo $organisation;
      ?>
    </h5>
  </div>

  <div class="col-md-6">
    <button type="button" data-id="0" data-target="" data-action="form" class="add-category add-category-form pull-right btn btn-sm btn-primary">
      <span class="fa fa-plus"></span> 
      Add new category
    </button>
  </div>
</div>

<?php
if ( strlen($errors) > 0 ) {
  echo $errors;
}

else {
  // add form
?>

<div class="table-responsive">

  <table class="table table-sm table-bordered">
    <thead class="bg-info">
      <tr>
        <th>Position</th>
        <th>Category</th>
        <th>Type</th>
        <th>Posts</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody>
      <?php
        foreach ( $categories as $item ) {
      	  $id = $item["id"];
      	  $status = $item["status"];

          $posts_status = $item["posts_status"];
          $posts = $posts_status == 10 ? 'Published immediately' : 'Validated first';
          $type = $item["type"] > 0 ? "Public" : "Private";

          $links = "<a href='#' class='add-category' data-id='" . $id . "' data-target='' " .
            "data-action='edit'>edit</a>";

      	  echo " <tr>" . PHP_EOL .
            "<td>" . $item["position"] . "</td>" .
            "<td>" . $item["category"] . "</td>" .
            "<td>" . $type . "</td>" .
            "<td>" . $posts . "</td>" .
            "<td>" . $this->app_helper->get_status($status, $status)[0] . "</td>" .
            "<td>" . $links . "</td> </tr>" . PHP_EOL;
        }
      ?>

    </tbody>

  </table>

</div>

<?php	
}
?>

