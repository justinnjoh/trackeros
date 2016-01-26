<?php
  // btn_label, category and protected info is in the calling script

  //print_r($status_codes);

  if ( count($category) > 0 ) {
?>

  <form id="global-form" class="add-post-form">
    <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
    <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">

    <?php
    if ( isset($status_codes) && count($status_codes) > 0 ) {
    ?>

      <div class="row reduce-bs-margins m-r-0 p-r-0">
        <div class="col-md-12">

          <?php
          foreach ( $status_codes as $item ) {
            echo "<span class='pull-right status-codes " . $item['class'] . "' title='" . $item['description'] . "'>" .
              "<input type='checkbox' name='sts[]'" . $item['checked'] . " value='" . $item['id'] . "'> " . 
              $item['code'] . "</span>";
          }
          ?>

        </div>
      </div>

    <?php
    }
    ?>

    <div class="row bg-success reduce-bs-margins">
  	  <div class="col-sm-12 col-md-8">
        <strong>
  	    <?php echo $category["category"]; ?>
        </strong>
      </div>

  	  <div class="col-sm-12 col-md-4">

        <a href="/posts/index/<?php echo $category['id']; ?>" class="btn btn-success btn-sm pull-right add-post" data-id="<?php echo $category['id']; ?>" data-action="list">
          <span class="fa fa-refresh"></span>
          List Posts
        </a>

  	    <button type="button" class="btn btn-success btn-sm add-post add-post-form pull-right" data-id="0" data-target="" data-action="form">
          <?php
          if ( strlen($btn_label) > 0 ) {
          ?>
  	        <span class="fa fa-plus">
  	          <?php echo $btn_label; ?>
  	        </span>
          <?php
          }
          ?>
  	    </button>

      </div>
    </div>

    <div class="row">
      <div id="global-item" class="col-md-12 global-item">
      </div>
    </div>

  </form>
<?php
  }
?>
