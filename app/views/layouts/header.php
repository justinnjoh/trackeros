
<nav class="navbar navbar-default">

  <!-- Brand and toggle -->
  <button class="navbar-toggler hidden-sm-up pull-right bg-success" type="button" data-toggle="collapse" data-target="#exCollapsingNavbar2">
    &#9776;
  </button>

  <a class="navbar-brand" href="/">
    Simple Tracker
    <!-- img src="/assets/images/logo.png" / -->
  </a>

  <ul class="nav navbar-nav">

    <li class="pull-right m-l">

    <?php 
      if ( isset($_SESSION['user_id']) ) {
    ?>
        <div class="dropdown">
          <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-image: url(<?php echo $_SESSION['image']; ?>); background-size: cover; background-repeat: no-repeat; ">
            &nbsp;
          </button>

          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">

            <h6 class="dropdown-header">Posts</h6>

            <a class="dropdown-item" href="/posts/assignedtome" title="Get open and new posts assigned to you">Assigned to me</a>
            <a class="dropdown-item" href="/posts/myposts" title="Get all posts created by you">Created by me</a>

            <?php
              if ( $_SESSION["rights"] >= 80 ) {
            ?>
              <div class="dropdown-divider"></div>
              <h6 class="dropdown-header">Admin</h6>
              <a class="dropdown-item" href="/categories/manage" title="Manage categories (menus) for all organisations">Manage Categories</a>
              <a class="dropdown-item" href="/users/manage" title="Manage users for all organisations">Manage Users</a>

            <?php
              }
            ?>

            <div class="dropdown-divider"></div>
            <h6 class="dropdown-header">Account</h6>
            <a class="dropdown-item" href="/users/show" title="View and edit your details">My Profile</a>
            <a class="dropdown-item" href="/logout" title="Log out">Logout</a>

          </div>
        </div>

    <?php
      }
      else {
    ?>

    <?php
      }
    ?>

    </li>

    <li class="pull-right menu p-r-0 m-r-0">

      <ul>
        <li>
          <a class="btn btn-info" href="/posts/index/101">
            <span class="fa fa-book"></span> 
            Tutorials
          </a>
        </li>

        <li>
          <a class="btn btn-info" href="/posts/index/111">
            <span class="fa fa-link"></span> 
            Resources
          </a>
        </li>

        <li>
          <a class="btn btn-info" href="/about">
            <span class="fa fa-info"></span> 
            About
          </a>
        </li>

        <li>
          <a class="btn btn-info" href="/">
            <span class="fa fa-home"></span> 
            Home
          </a>
        </li>

      </ul>

    </li>

  </ul>

</nav>

<nav class="navbar navbar-light bg-info">

  <div class="collapse navbar-toggleable-xs" id="exCollapsingNavbar2">

    <ul class="nav navbar-nav">

    <?php
      $menu = array();
      isset($this->menu["data"]) && $menu = $this->menu["data"];

      if ( count($menu) > 0 ) {

        foreach ( $menu as $item ) {
          $active = $_SESSION['cat_id'] == $item['id'] ? 'active' : '';

    ?>
          <li class="nav-item <?php echo $active; ?>">
            <a class="btn btn-info" href="/posts/index/<?php echo $item['id']; ?>" title="<?php echo $this->escape_string($item['description']); ?>">
              <?php echo $this->escape_string($item['category']); ?>
            </a>
          </li>

    <?php
        }
      }
      else {
        echo " <li>&nbsp;</li>";
      }
    ?>

    </ul>
  </div>

</nav>

