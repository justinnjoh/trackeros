<?php

  $errors = $this->get_errors($this->params["manage_users"]);

  $organisation = "";
  isset($this->params["manage_users"]["data"]["organisation"]) && $organisation = $this->params["manage_users"]["data"]["organisation"];
  $organisation = strlen($organisation) > 0 ? "Manage users for " . $organisation : "";

  $users = array();
  isset($this->params["manage_users"]["data"]["users"]) && $users = $this->params["manage_users"]["data"]["users"];
?>

<div class="row">
  <div id="global-item" class="col-md-12 global-item">
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <h5>
      <?php
        echo $organisation;
      ?>
    </h5>
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
        <th>&nbsp;</th>
        <th>Name</th>
        <th>About</th>
        <th>Status</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody>
      <?php
        foreach ( $users as $item ) {
      	  $id = $item["id"];
          $status = $item["status"];
          $rights = $item["rights"];

          $name = $this->escape_string($item['name']);
          $display_name = $item['provider'] != 'self' ? "<span class='block'>" . $name . "</span> <span class='block'>" . $item['provider'] . "</span>" : $name;

          $about = strlen($item['about']) > 0 ? $item['about'] : "";
          $about = strlen($item['about']) > 100 ? substr($about, 0, 99) . "..." : $about;
          $about = strlen($about) > 0 ? "<span class='block'>" . $about . "</span>" : "";
          $about = strlen($item['headline']) > 0 ? "<span class='block'>" . $this->escape_string($item['headline']) . "</span>" . $about : $about;

          $created = $this->time_diff($item['created_at']) . " ago";
          !is_null($item['last_logged_in_at']) && $created = "<span class='block'>" . $created . 
            "</span> <span class='block'>Last active: " . $this->time_diff($item['last_logged_in_at']) .
            "</span> ago";

          $links = "<a href='#' class='add-user' data-id='" . $id . "' data-target='' " .
            "data-action='edit'>edit</a>";

      	  echo " <tr>" . PHP_EOL .
            "<td><a href='/users/show/" . $id . "'><img class='thumb' alt='" . $name . "' title='" . $name . "' src='" .
              $item["image_url"] . "' /></a></td>" .
            "<td>" . $display_name . "</td>" .
            "<td>" . $about . "</td>" .
            "<td><span class='block' title='" . $this->app_helper->get_status($status, $status)[1] . "'>" .
              $this->app_helper->get_status($status, $status)[0] . "</span>" .
              "<span class='block' title='" . $this->app_helper->get_right($rights, $rights)[1] . "'>" .
              $this->app_helper->get_right($rights, $rights)[0] . "</span></td>" .
            "<td>" . $created . "</td>" .
            "<td>" . $links . "</td> </tr>" . PHP_EOL;
        }
      ?>

    </tbody>

  </table>

</div>

<?php	
}
?>

