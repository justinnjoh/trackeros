// custom javascripts
var tracker = (

  // ajax json returns are in the form
  // { errors : [{ message: ..., debug: ...}], data: {}, info: }

  function () {

    // globs - starts
    var version = "0.9.0";

    var siteURL = window.location.host;
    var max_file_size = 2000000; // 2G

    var speech = null; // global speech handler; used for starting / stopping speech
    var popup_window = null; // global handle for javascript pop-up windows

    // globs - ends



    // utilities - start

    function log (item) {
      // print in console
      console.log(item);
    }

    function log_info (info, elem_id, cls) {
      // print in console, and on screen if elem (id) is passed
      var elem = typeof(elem_id) != 'undefined' ? elem_id : "global-form-error",
        clas = typeof(cls) != 'undefined' ? cls : ""; 

      $("#" + elem).html(info);

      if (clas.length > 0 ) {
        $("#" + elem).addClass(clas);
      }

      console.log(info);
    }

    function count_chars (elem, max) {
      // count number of chars in elem, limiting to 'max' chars
      // returns count

      max = max || 1;

      if ( typeof(elem) === "string" && elem.length > 0 ) {
        var val = $('#' + elem).val();
 
        if ( val ) {
          var chrs = val.length,
            msg_elem = $('#' + elem + '-msg');

          if ( chrs >= max ) {
            // stop more characters getting entered
            $(msg_elem).attr('class', 'text-danger');
            $('#' + elem).val(val.substring(0, max - 1));
          }
          else {
            //$(msg_elem).attr('class', 'text-success');
          }

          // message ?
          var msg = (max - chrs) + ' characters remaining ...';
          if ( chrs >= max ) {
            msg += ' You cannot enter any more.';
          }

          $(msg_elem).html(msg);
        }

      } // elem != null

      return (chrs);
    }

    function escape_string (str) {
      // to mitogate potential script injection;
      // escape < and > charcters
      var res = typeof(str) === 'string' ? str : '';

      res = res.replace(/</g, "&lt;"); 
      res = res.replace(/>/g, "&gt;");
      res = res.replace(/'/g, "''");
      res = res.replace(/"/g, '""');
      res = res.replace(/\&/g, "&amp;");

      return (res);
    }





    function _reload_page () {
      window.location.reload();
    }

    function _goto (url) {
      window.location.href = url;
    }

    function _get_ajax_error (response) {
      // attempt to extract the error from the ajax response object
      // response struct: { errors : [{ message: ..., debug: ..., }], data: {}, info: }
      // currently just getting the first error is enough to signal error in response

      var error = null;
      
      try {
        error = response.errors[0].message;
      }

      catch (e) {
        error = 'Error parsing response : ' + e.message;
      }

      error = error || "";

      return (error);
    }

    function _get_ajax_info (response) {
      // attempt to extract any info from the ajax response object
      // response struct: { errors : [{ message: ..., debug: ..., }], data: {}, info: }

      var info = null;
      
      try {
        info = response["errors"]["info"];
      }

      catch (e) {
        info = 'Error parsing response : ' + e.message;
      }

      info = info || "";

      return (info);
    }


    function _ajax_post (url, data, success_cb, error_cb) {
      // use jquery to make an ajax POST
      $.ajax({
        type : "POST",
        cache : false,
        url : url,
        data : data,
        success : success_cb,
        error : error_cb
      });
    }

    function _post_to (url, data) {
      // creates form and posts data
      var form = document.createElement('form'),
        fields = typeof(data) === 'object' ? data : null,
        html = "";

      if ( fields ) {
        var keys = window.Object.keys(fields);

        for (var i = 0, len = keys.length; i < len; i++) {
          html += "<input type='hidden' name='" + keys[i] + "' value='" + fields[keys[i]] + "' />";
        }

        form.action = url;
        form.method = "POST";
        form.innerHTML = html;
        document.lastChild.appendChild(form);
        form.submit();
      }

    }

    function _ajax_get_page_result (data, elem) {
      // generic function to inject results of an ajax call into the dome

      if ( typeof(elem) !== 'undefined' && elem ) {
        $(elem).hide().html(data).show(800);
      }
      else {
        console.log('ajax get page ', data);
      }
    }

    function _get_form_input (elem, validate_regex, err_msg) {
      // get input from form elememt (elem) - it must have a type
      // returns {value, err_msg, type}

      var error = elem ? null : err_msg || 'Programmer error - no valid form field specified',
        value = null,
        type = error ? 'unknown' : $(elem).attr("type"),
        display_class = "",
        regex = validate_regex && validate_regex.length > 0 ? new RegExp(validate_regex) : null;

      if ( !error ) {

        switch (type) {
          case 'text':
          case 'date':
          case 'url':
          case 'email':
          case 'number':
          case 'hidden':
          case 'select':
          case 'password':
            value = $(elem).val();
            break;

          case 'radio':
            value = $(elem).filter(':checked').val();
            break;

          case 'checkbox':
            value = [];
            $(elem).filter(':checked').each ( function () {
              value.push(this.value);
            });


          default:

        }

        error = !value ? err_msg : null;

        if ( !error && regex ) {
          error = value.toString().match(regex) ? null : err_msg;
        }

        display_class = error && error.length > 0 ? "has-error" : "has-success";

        $(elem)
          .parent()
          .addClass(display_class);

      } // if !error

      var result = {
            "value": value,
            "error": error,
            "type": type
          };

      return (result);
    }

    function _get_form_values (form_id, fields) {
      // get input values from form whose DOM Id and fields are passed in array fields;
      // each field has the following form <field name>__<validation rule>__<err msg>
      // returns { data: {name : value, ...}, error: <error for all fields in <span> elements }
      var result = {
          "data" : {},
          "error" : null
        },
        field = null,
        err_msg = null,
        regexp = null,
        name = null,
        value = "";

      result.error = form_id && form_id.length > 0 ? null : "<span>Programmer error - a form Id is expected";
      result.error = !result.error && fields && Array.isArray(fields) ? null : "<span>Programmer error - form fields are required";

      if ( !result.error ) {
        // get protected values automatically
        fields.push("_p1_");
        fields.push("_p2_");

        fields.forEach (function (item, index) {

          item = item.split(/__/);

          regexp = null;
          err_msg = null;

          name = item[0];
          regexp = item.length > 1 && item[1].length > 0 ? item[1] : null;
          err_msg = item.length > 2 && item[2].length > 0 ? item[2] : null;

          field = $("#" + form_id + " [name='" + name + "']");
          value = _get_form_input(field, regexp, err_msg);

          result.data[name] = value["value"];
          if ( value.error && value.error.length > 0 ) {
            result.error = result.error ? result.error : ""; // git rid of initial null value
            result.error += "<span>" + value.error + "</span>";
          }

        });

      }

      return (result);
    }



    function _login_result(data) {
      // process login response
      console.log('Login result: ', data);

      var error = _get_ajax_error(data);
      if ( !error || error.length < 1 ) {

        // success - any info ?
        var info = _get_ajax_info(data);
        if ( info && info.length > 0 ) {
          // TO DO: define generic function to show info messages
          _log(info);

        }
        else {
          // no info, so reload page
          _goto('/');
        }

      }
      else {
        //
        log_info(error, "login-signup-msg", "text-danger");
        console.log('Error: ', error);
      }
    }

    function _fb_login() {

      FB.getLoginStatus(function (response) {

        //console.log('_fb_login', response);

        if ( response.status === 'connected') {
          _fb_do_login();
        }
        else {
          FB.login(function (response) {
            if (response.authResponse) {
              _fb_do_login();
            }
            else {
              console.log('User cancelled login dialogue');
            }
          });
        }
      });
    }


    function _fb_do_login() {

      //console.log('_fb_do_login');

      FB.api('/me?fields=id,name,email', function (response) {
        console.log('/me', response);

        // login - if not registered, reg first, then log in
        var data = {
          provider: 'facebook',
          user_name: response["id"],
          name: response["name"],
          email: response["email"]
        };

        _ajax_post(
            '/login',
            data,
            _login_result,
            log
          );
      });
    }

    function _self_login(user_name, password, name, create) {

      console.log('_self_login');

      var data = {
        provider: 'self',
        user_name: user_name,
        password: password,
        name: name,
        create: create
      };

      _ajax_post(
          '/login',
          data,
          _login_result,
          log
        );
    }






    // utilities - ends



    function login(provider, user_name, password, name, create) {

      //log('Login using ' + provider);
      switch (provider) {

        case 'facebook':
          _fb_login();
          break;

        case 'self':
          _self_login(user_name, password, name, create);
          break;


      } // switch

    }

    function ajax_get_page (url, params, elem) {
      // generic function to get page and inject the results into DOM elem

      //console.log("ajax_get_page", url, params, elem);

      _ajax_post(
          url,
          params,
          function (data) { _ajax_get_page_result(data, elem) },
          log
        );

    }

    function add_category() {
      // category form has been submitted

      var fields = [
          "category__\\w+__Please enter a category",
          "description",
          "position__\\d+",
          "posts_status____Please select a status for new posts in this category",
          "type",
          "status"
        ];
      
      var data = _get_form_values("global-form", fields);

      console.log(data);

      if ( !data.error ) {

        _ajax_post ("/categories/add",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  _reload_page();
                }
              },
              log('Add category error')
            );

      }
      else {
        // input / validation error
        log_info(data.error, "global-form-error", "text-danger");
        console.log(data);
      }

    }

    function list_posts(params) {
      // list posts with possible status filters - POST submit the global form
      var id = typeof(params) === 'object' && params.hasOwnProperty('id') ? params["id"] : 0,
        form = document.getElementById('global-form'),
        err = form ? null : "Unable to complete request";
      
      if ( !err ) {
        form.action = "/posts/index/" + id;
        form.method = "POST";
        form.submit();
      }
      else {
        log_info(err, "global-form-error", "text-danger");        
      }
    }

    function add_post() {
      // post form has been submitted - submit normally

      var fields = [
          "title__\\w+__Please enter a title",
          "post__\\w+__Please enter your post",
          "url",
          "commenting",
          "priority",
          "start_date_proposed",
          "end_date_proposed",
          "days_proposed"
        ];

      var data = _get_form_values("global-form", fields);

      // get pre-uploads files info separately
      var old_files = _get_preupload_info("old", "global-form");
      var new_files = _get_preupload_info("new", "global-form");

      data["data"]["new_files"] = new_files.ids.join(",");
      data["data"]["new_files_info"] = new_files.info;

      data["data"]["old_files"] = old_files.ids.join(",");
      data["data"]["old_files_info"] = old_files.info;

      console.log(data);

      var form = null,
        error = data.error || "";

      if ( error.length < 1 ) {
         form = document.getElementById("global-form");
         error = typeof(form) == 'object' ? "" : "Programmer error - form not found";
      }

      if ( error.length < 1 ) {

        _ajax_post ("/posts/add",
              data["data"],
              function (response) {

                console.log('Response: ', response);

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  // show the post just added / updated
                  var post_id = response.data.post_id;

                  if ( post_id && post_id > 0 ) {
                    _goto ('/posts/show/' + post_id)
                  }
                  else {
                    log('Post not found', post_id);
                  }
                }
              },
              log('Add post error')
            ); // ajax post

      }
      else {
        log_info(error, "global-form-error", "text-danger");
      }

    }

    function add_user_details() {
      // user form has been submitted - submit normally

      var fields = [
          "name__\\w+__Please enter your name",
          "headline__\\w+__Please enter a headline or job title",
          "about",
          "status",
          "rights",
          "use_current"
        ];
      
      var data = _get_form_values("global-form", fields);

      // get pre-upload file info separately; only 1 file will be used
      var new_files = _get_preupload_info("new", "global-form");

      data["data"]["new_files"] = new_files.ids.join(",");
      data["data"]["new_files_info"] = new_files.info;

      console.log(data);

      var form = null,
        error = data.error || "";

      if ( error.length < 1 ) {
         form = document.getElementById("global-form");
         error = typeof(form) == 'object' ? "" : "Programmer error - form not found";
      }

      if ( error.length < 1 ) {

        _ajax_post ("/users/adduserdetails",
              data["data"],
              function (response) {

                console.log('Response: ', response);

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  // show the post just added / updated
                  var user_id = response.data.user_id;

                  if ( user_id && user_id > 0 ) {
                    _goto ('/users/show/' + user_id)
                  }
                  else {
                    log('User not found', user_id);
                  }
                }
              },
              log('Add user details error')
            ); // ajax post

      }
      else {
        log_info(error, "global-form-error", "text-danger");
      }

    }

    function add_comment() {
      // comment submit button has been clicked

      var fields = [
          "comment__\\w+__Please enter a comment",
        ];
      
      var data = _get_form_values("comment-form", fields);

      // get pre-upload file info separately; only 1 file will be used
      var new_files = _get_preupload_info("new", "comment-form");

      data["data"]["new_files"] = new_files.ids.join(",");
      data["data"]["new_files_info"] = new_files.info;

      console.log(data);

      if ( !data.error ) {

        _ajax_post ("/comments/add",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "comment-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  // reload post where comment has just been added
                  var post_id = response.data.post_id;

                  if ( post_id && post_id > 0 ) {
                    _goto ('/posts/show/' + post_id)
                  }
                  else {
                    log('Programmer error - post where comment was added was not found', response);
                  }                }

              },
              log('Add comment error')
            );

      }
      else {
        // input / validation error
        log_info(data.error, "comment-form-error", "text-danger");
        console.log(data);
      }

    }

    function publish_comment (params) {
      // set comment status

      var fields = [],
        data = _get_form_values("global-form", fields);

      if ( !data.error && (typeof(params) !== 'object' || !params.hasOwnProperty('id') || !params.hasOwnProperty('sts')) ) {
        data.error = "Publish comment - invalid request";
      }

      console.log(data, params);

      if ( !data.error ) {

         data["data"]["sts"] = params.sts;
         data["data"]["id"] = params.id;

        _ajax_post ("/comments/publish",
              data["data"],
              function (response) {

                console.log('Response: ', response);

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log(err);
                }
                else {
                  // change buttons
                  var id = response.data.id,
                    sts = response.data.sts == 10 ? 1 : 10,
                    titl = sts == 10 ? "Publish this comment" : "Remove comment from public view",
                    btn = sts == 10 ? "Publish" : "Unpublish",
                    html = "<a class='btn btn-info btn-sm add-comment p-a-0' href='#' data-sts='" +
                      sts + "'data-id='" + id + "' data-action='publish'>" + btn + "</a>";

                  $('#c' + id).html(html);
                  $('#c' + id).attr("title", titl);
                }
              },
              log('Publish comment error')
            );

      }
      else {
        // input / validation error
        console.log(data, params);
      }
    }

    function publish_post (params) {
      // set post status

      var fields = [],
        data = _get_form_values("global-form", fields);

      if ( !data.error && (typeof(params) !== 'object' || !params.hasOwnProperty('sts')) ) {
        data.error = "Publish post - invalid request";
      }

      console.log(data, params);

      if ( !data.error ) {

         data["data"]["sts"] = params.sts;

        _ajax_post ("/posts/publish",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log(err);

                  console.log('Response: ', response);
                }
                else {
                  // change buttons
                  var post_id = response.data.post_id;

                  if ( post_id ) {
                    _goto('/posts/show/' + post_id);
                  }
                  else {
                    log('Error - no post to show');
                  }
                }
              },
              log('Publish post error')
            );

      }
      else {
        // input / validation error
        console.log(data, params);
      }

    }

    function watch_post (params) {
      // watch or unwatch post

      var fields = [],
        data = _get_form_values("global-form", fields);

      if ( !data.error && (typeof(params) !== 'object' || !params.hasOwnProperty('sts')) ) {
        data.error = "Watch post - invalid request";
      }

      console.log(data, params);

      if ( !data.error ) {

         data["data"]["sts"] = params.sts;

        _ajax_post ("/posts/watch",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log(err);

                  console.log('Response: ', response);
                }
                else {
                  // change buttons
                  var id = response.data.id,
                    sts = response.data.sts == 1 ? 0 : 1,
                    titl = sts == 1 ? "Watch this post" : "Stop watching this post",
                    btn = sts == 1 ? "Watch" : "Unwatch",
                    html = "<a class='btn btn-info btn-sm add-post p-a-0' href='#' data-sts='" +
                      sts + "' data-action='watch'>" + btn + "</a>";

                  $('#watch').html(html);
                  $('#watch').attr("title", titl);
                }
              },
              log('Watch post error')
            );

      }
      else {
        // input / validation error
        console.log(data, params);
      }

    }

    function feature_post (params) {
      // feature or unfeature post

      var fields = [],
        data = _get_form_values("global-form", fields);

      if ( !data.error && (typeof(params) !== 'object' || !params.hasOwnProperty('sts')) ) {
        data.error = "Feature post - invalid request";
      }

      console.log(data, params);

      if ( !data.error ) {

         data["data"]["sts"] = params.sts;

        _ajax_post ("/posts/feature",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log(err);

                  console.log('Response: ', response);
                }
                else {
                  // change buttons
                  var id = response.data.id,
                    sts = response.data.sts == 1 ? 0 : 1,
                    titl = sts == 1 ? "Feature this post" : "Remove post from the featured list",
                    btn = sts == 1 ? "Feature" : "UnFeature",
                    html = "<a class='btn btn-info btn-sm add-post p-a-0' href='#' data-sts='" +
                      sts + "' data-action='feature'>" + btn + "</a>";

                  $('#feature').html(html);
                  $('#feature').attr("title", titl);
                }
              },
              log('Feature post error')
            );

      }
      else {
        // input / validation error
        console.log(data, params);
      }

    }

    function show_post (params) {
      // show a post whose id is passed params

      var id = typeof(params) !== 'object' || !params.hasOwnProperty('id') ? 0 : parseInt(params.id, 10),
        error = id === NaN || id < 1 ? "No post to show" : null;

      if ( !error ) {
        _goto("/posts/show/" + id);
      }
      else {
        log_info (error);
      }

    }


    function assign_user() {
      // assign submit button has been clicked

      var fields = [
          "usr",
        ];
      
      var data = _get_form_values("global-form", fields);

      console.log(data);

      if ( !data.error ) {

        _ajax_post ("/posts/assign",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  // reload post where comment has just been added
                  var post_id = response.data.post_id;

                  if ( post_id && post_id > 0 ) {
                    _goto ('/posts/show/' + post_id)
                  }
                  else {
                    log('Programmer error - post to assign was not found', response);
                  }
                }
              },
              log('Assign post error')
            );

      }
      else {
        // input / validation error
        log_info(data.error, "global-form-error", "text-danger");
        console.log(data);
      }

    }

    function get_post_files (params, target) {
      // get and display files associated with a post and/or comment
      
      var error = typeof(params) == 'object' ? null : "Programmer error - post files info not specific";
      error = !error && ( typeof(target) != 'string' || target.length < 1 ) ? "Programmer error - ajax results target not specified" : null;

      if ( !error ) {

        var elem = $('#' + target),
          data = _get_form_values("global-form", []);

        data.data["info"] = params.info;

        ajax_get_page("/posts/getfiles/", data.data, elem);
      }
      else {
        log_info(error, "global-form-error", "text-danger");

        console.log('Get post files error: ', error);
      }

    }

    function get_file (params) {
      // download a file
      
      var error = typeof(params) == 'object' ? null : "Programmer error - post file info not specific";

      if ( !error ) {

        var data = _get_form_values("global-form", []);

        data.data["info"] = params.info;

        _post_to("/posts/getfile/", data.data);

      }
      else {
        log_info(error, "global-form-error", "text-danger");

        console.log('Get file error: ', error);
      }

    }

    function actual() {
      // set actual dates

      var fields = [
          "start_date_actual",
          "end_date_actual",
          "days_actual",
        ];
      
      var data = _get_form_values("global-form", fields);

      console.log(data);

      if ( !data.error ) {

        _ajax_post ("/posts/actual",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  // reload post where comment has just been added
                  var post_id = response.data.post_id;

                  if ( post_id && post_id > 0 ) {
                    _goto ('/posts/show/' + post_id)
                  }
                  else {
                    log('Programmer error - post to set actual dates for was not found', response);
                  }
                }
              },
              log('Set actual dates error')
            );

      }
      else {
        // input / validation error
        log_info(data.error, "global-form-error", "text-danger");
        console.log(data);
      }

    }

    function forgot() {
      // attempt to reset password - email has been entered

      var fields = [
          "email__\\w+__Please enter your email address",
        ];
      
      var data = _get_form_values("global-form", fields);

      if ( !data.error ) {

        _ajax_post ("/sessions/reset",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  _goto('/');
                }

              },
              log('Forgot password error')
            );

      }
      else {
        // input / validation error
        log_info(data.error, "global-form-error", "text-danger");
        console.log(data);
      }

    }


    function update_password () {
      // new password has been entered - update it

      var fields = [
          "password1__\\w+__Please enter new password",
          "password2__\\w+__Please confirm your new password",
        ];
      
      var data = _get_form_values("global-form", fields);

      var error = data.error;
      if ( !error && data["data"]["password1"] != data["data"]["password2"] ) {
        error = "Sorry your password entries do not match";
      }

//console.log('Data: ', data, error);

      if ( !error ) {

        _ajax_post ("/sessions/updatepassword",
              data["data"],
              function (response) {

                var err = _get_ajax_error(response);
                if ( err.length > 0 ) {
                  log_info(err, "global-form-error", "text-danger");

                  console.log('Response: ', response);
                }
                else {
                  _goto('/');
                }

              },
              log('Forgot password error')
            );

      }
      else {
        // input / validation error
        log_info(error, "global-form-error", "text-danger");
        console.log(data);
      }

    }










  // file upload functions START

  function preupload_file (file, info_inputs) {
    // send a single file to the server
    // if info_inputs = 1, then add caption and position inputs along with thumbnail
    var data = new FormData();

    data.append("file", file);

    $.ajax({
      url: "/posts/preupload",
      type: "post",
      cache: false,
      //async: false,
      //contentType: "multipart/form-data",
      contentType: false,
      data: data,
      processData: false,
      success: function (result) {
        //alert(result);
        var id = 0,
          error = _get_ajax_error(result),
          info = _get_ajax_info(result);

        if ( error.length < 1 ) {

          id = parseInt(result["data"]["id"], 10);

          if (id > 0) {
            if (info.length > 0) {
              alert(info);
            }

            _show_file_thumb('file_list', file, id, info_inputs);

            // if in context of a 'current image' uncheck it
            $("#global-form [name = 'use_current']").attr("checked", false);
          }
          else { // an error occurred
            error = "System error - file information was not returned by the server";
          }
        }

        if ( error.length > 0 ) {
          log(error);
        }

      } // success:
    });

  }

  function _show_file_thumb (elem, file, id, info_inputs) {
    // show this file in 'files' inside the 'container' (ul) element
    var container = document.getElementById(elem);
    var html = "", image_type = /image.*/,
      fset = document.createElement('fieldset'),
      labl = document.createElement('label');
      labl.classList.add("clearfix", "bg-info");
      labl.innerHTML = "<input type='checkbox' class='new_files' name='new_files[]' value='" + id + "' checked>";

    if ( container != null && file != null ) {

      if ( file.type.match(image_type) ) {
        img = document.createElement("img");
        img.src = window.URL.createObjectURL(file);
        img.classList.add("thumb"); // add this class to all images
        labl.appendChild(img);

      } // image_type match

      labl.innerHTML += " " + file.name + ", " + file.size + " bytes";
      fset.appendChild(labl);

      if ( info_inputs > 0 ) {

        html += "<label>" +
          "<input class='form-control form-control-sm' type='text' maxlength='200' title='Enter a caption for this file' placeholder='caption' name='new_caption" + id + "' value=''>" +
          "</label>";

        html += "<label>" +
          "<input type='number' title='Enter position - 0 means next position available' name='new_position" + id + "' value='0'>" +
          "</label>";

        html += "<label title='Use as main image when post is displayed ?'>" +
          "<input type='checkbox' name='new_main" + id + "' value='1'> Use as main image</label>";
      }

      html += "<input type='hidden' name='new_file_name" + id + "' value='" + file.name + "'>";
      html += "<input type='hidden' name='new_file_type" + id + "' value='" + file.type + "'>";
      html += "<input type='hidden' name='new_file_size" + id + "' value='" + file.size + "'>";

      fset.innerHTML += html;
      fset.classList.add("form-group", "uploads");

      container.appendChild(fset);
    } // end for
  }

  function _get_preupload_info (prefix, target) {
    // asuming pre-uploaded files, get info from pre-defined form vars
    // prefix should be 'new' or 'old' (for edit)
    // info is in the form { ids: [id1,id2,...], info: <id,,,name,,,caption,,,type,,,size,,,pos##id,,,name,,,....>}

    var result = {
        "ids" : [],
        "info" : ""
      },
      id = null,
      info = "",
      form_id = target || "global-form";

    if ( prefix == 'new' || prefix == 'old' ) {

      $("." + prefix + "_files:checked").each ( function () {
        id = this.value;
        result.ids.push(id);

        info += ",,," + $("#" + form_id + " input[name=" + prefix + "_main" + id + "]").val();

        // get and concatenate each file info
        info = id;
        info += ",,," + $("#" + form_id + " input[name=" + prefix + "_file_name" + id + "]").val();
        info += ",,," + $("#" + form_id + " input[name=" + prefix + "_caption" + id + "]").val();
        info += ",,," + $("#" + form_id + " input[name=" + prefix + "_file_type" + id + "]").val();
        info += ",,," + $("#" + form_id + " input[name=" + prefix + "_file_size" + id + "]").val();
        info += ",,," + $("#" + form_id + " input[name=" + prefix + "_position" + id + "]").val();
        info += $("#" + form_id + " input[name=" + prefix + "_main" + id + "]").prop("checked") ? ",,,1" : ",,,0";

        result.info += result.info.length > 0 ? "##" + info : info;
      });
    }

    return (result);
  }

  // file upload functions END






  // public functions - starts





    // public functions - ends


    var public_functions = {
      version : version,
      siteURL : siteURL,
      max_file_size : max_file_size,
      speech : speech,
      popup_window : popup_window,

      log : log,
      count_chars : count_chars,
      log_info : log_info,
      login : login,
      ajax_get_page : ajax_get_page,
      add_category : add_category,
      preupload_file : preupload_file,
      add_post : add_post,
      add_comment : add_comment,
      publish_comment : publish_comment,
      publish_post : publish_post,
      feature_post : feature_post,
      show_post : show_post,
      add_user_details : add_user_details,
      assign_user : assign_user,
      get_post_files : get_post_files,
      get_file : get_file,
      actual : actual,
      forgot : forgot,
      update_password : update_password,
      watch_post : watch_post,
      list_posts : list_posts,

    };

    return (public_functions);

  } // function tracker
)
()


