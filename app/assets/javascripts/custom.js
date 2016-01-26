
// custom : jquery

$(document).ready( function() {

  $(document).on("click", ".login-signup", function (e) {

  	var provider = this.dataset.provider,
  	  name = null,
  	  password = null,
  	  user_name = null,
  	  create = 0;

  	//console.log(provider);

  	if ( provider && provider.length > 0 ) {
  	  provider = provider.toLowerCase();

  	  if ( provider == 'self' ) {
        create = this.dataset.create;

        name = $('#login-signup input[name=name]').val();
        password = $('#login-signup input[name=password]').val();
        user_name = $('#login-signup input[name=user_name]').val();

        //console.log('Form: ', name, password, user_name);
  	  }

      $("#login-signup-msg").html();
  	  tracker.login(provider, user_name, password, name, create);
  	}
    else {
      tracker.log('Invalid login/signup call - unknown provider');
    }

  });


  $(document).on("click", ".add-category", function (e) {

    var id = this.dataset.id,
      target = this.dataset.target,
      action = this.dataset.action,
      params = {
        "id" : id,
      };

    target = !target || target.length < 1 ? "global-item" : target;
    target = $("#" + target);

    //$(this).hide(800);
    switch (action) {

      case 'form':
        tracker.ajax_get_page("/categories/edit/" + id, params, target);
        $(this).hide(800);
        break;

      case 'edit':
        tracker.ajax_get_page("/categories/edit/" + id, params, target);
        $(".add-category-form").hide(800);
        break;

      case 'close':
        $(".add-category-form").toggle(800);
        break;

      case 'submit':
        tracker.add_category();
        break;

      default:
        console.log('Ha ha');
    }

  });


  $(document).on("click", ".add-user", function (e) {

    var id = this.dataset.id,
      target = this.dataset.target,
      action = this.dataset.action,
      params = {
        "id" : id,
      };

    target = !target || target.length < 1 ? "global-item" : target;
    target = $("#" + target);

    switch (action) {

      case 'form':
        tracker.ajax_get_page("/users/edit/" + id, params, target);
        $(this).hide(800);
        break;

      case 'edit':
        tracker.ajax_get_page("/users/edit/" + id, params, target);
        $(".add-user-form").hide(800);
        break;

      case 'close':
        $(".add-user-form").toggle(800);
        break;

      case 'submit':
        tracker.add_user_details();
        break;

      default:
        console.log('Ha ha');
    }

  });



  $(document).on("click", ".add-post", function (e) {
    e.preventDefault();

    var id = this.dataset.id,
      target = this.dataset.target,
      action = this.dataset.action,
      sts = this.dataset.sts,
      params = {
        "id" : id,
        "_p1_" : $("#global-form [name='_p1_']").val(),
        "_p2_" : $("#global-form [name='_p2_']").val()
      };

    params["sts"] = sts;

    console.log(params, action);

    target = !target || target.length < 1 ? "global-item" : target;
    target = $("#" + target);

    //$(this).hide(800);
    switch (action) {

      case 'form':
        tracker.ajax_get_page("/posts/edit/" + id, params, target);
        $(this).hide(800);
        break;

      case 'edit':
        tracker.ajax_get_page("/posts/edit/" + id, params, target);
        $(".add-post-form").hide(800);
        break;

      case 'close':
        $(".add-post-form").toggle(800);
        break;

      case 'submit':
        tracker.add_post();
        break;

      case 'feature':
        tracker.feature_post(params);
        break;

      case 'publish':
        tracker.publish_post(params);
        break;

      case 'assign-form':
        tracker.ajax_get_page("/posts/assignwiz1/" + id, params, target);
        break;

      case 'assign-user':
        tracker.assign_user(params);
        break;

      case 'get-file':
        params["info"] = this.dataset.info;
        tracker.get_file(params);
        break;

      case 'actual-form':
        tracker.ajax_get_page("/posts/actualwiz1/" + id, params, target);
        break;

      case 'actual':
        tracker.actual(params);
        break;

      case 'show':
        tracker.show_post(params);
        break;

      case 'watch':
        tracker.watch_post(params);
        break;

      case 'list':
        tracker.list_posts(params);
        break;

      default:
        console.log('Ha ha');
    }

  });

  $(document).on("click", ".add-comment", function (e) {
    e.preventDefault();

    var action = this.dataset.action,
      data = {
        "id" : this.dataset.id,
        "sts" : this.dataset.sts
      };

    switch (action) {

      case 'publish':
        tracker.publish_comment(data);
        break;

      case 'submit':
        tracker.add_comment();
        break;

      default:
        console.log('Ha ha');
    }

  });

  $(document).on("click", ".comment", function (e) {

    var action = this.dataset.action;

    switch (action) {

      case 'submit':
        tracker.add_comment();
        break;

      default:
        console.log('Ha ha');
    }

  });

  $(document).on("click", ".forgot", function (e) {

    var action = this.dataset.action;

    switch (action) {

      case 'submit':
        tracker.forgot();
        break;

      case 'update':
        $('#global-form-error').html();
        tracker.update_password();
        break;

      default:
        console.log('Ha ha');
    }

  });




  $(document).on("change", '#images', function (e) {
    var upload = document.getElementById('images'),
      info_inputs = this.dataset.info;

    info_inputs = info_inputs || 1; // default to showing info inputs with thumbnail

    if ( upload.files.length > 0 && upload.files[0] != null ) {
      if ( upload.files[0].size < tracker.max_file_size ) {
        tracker.preupload_file(upload.files[0], info_inputs); // send file and show thumb
      }
      else {
        alert ('This file (' + upload.files[0].size + ') exceeds the maximum allowed size, so cannot be uploaded');
      }
    }
  });


  $(document).on('keyup', '.count-chars', function (e) {
    var max = parseInt(this.dataset["max"], 10);

    if ( max > 0 ) {
      tracker.count_chars(this.id, max);
    }
  });

  $(document).on('focusin', '.datetime', function (e) {
    $(this).datepicker({
      dateFormat:'yy-mm-dd',
    });

  });



  // jQuery pluggins -- starts

  (
    function ($) {

      $.fn.show_post_files = function ( options ) {

        var params = {},
          target = null;

        this.each (function (idx, obj) {
          //alert(idx + ', ' + obj.id + ', ' + obj.dataset.info );
          //console.log(obj);
          target = obj.id;
          params["info"] = obj.dataset.info;

          if ( target && params["info"] ) {
            tracker.get_post_files(params, target);
          }

        });

        //example_villages('', 0, 1, 0);

      }
    } ( jQuery )

  );

  

  // jQuery pluggins -- ends


  $('.c-files').show_post_files();


});
