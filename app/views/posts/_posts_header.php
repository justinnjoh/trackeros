<?php
  // btn_label, category and protected info is in the calling script

  if ( count($category) > 0 ) {
?>

  <div class="row bg-success m-b reduce-bs-margins">
  	<div class="col-sm-12 col-md-8">
      <strong>
  	  <?php echo $category["category"]; ?>
      </strong>
    </div>

  	<div class="col-sm-12 col-md-4">

      <a href="/posts/index/<?php echo $category['id']; ?>" class="btn btn-success btn-sm pull-right">
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

      <form id="global-form" class="add-post-form">
        <input type="hidden" name="_p1_" value="<?php echo $protected['_p1_']; ?>">
        <input type="hidden" name="_p2_" value="<?php echo $protected['_p2_']; ?>">
      </form>

    </div>
  </div>

<?php
  }
?>
