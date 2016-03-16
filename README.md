# trackeros
Simple issue tracker

An application demonstrating a robust MVC pattern for rapid development of websites small or large.

Some highlights

1. Simple REST-full URLs with automatic routing
For example in the URL http://www.tracker.com/user/show/50 the route is autmatically mapped to an action 'show' in the controller 'user'. Static routes are available and take precedence over automatic routes.

2. Simple uniform database access pattern with multiple resulsets assumed
All DB access takes the form: $result = $this->query(<some query>)
$result now contains resultsets ordered from 0 to n.

3. Simple but robust form processing pattern with validation
For example:

      var fields = [
          "name__\\w+__Please enter your name",
          "headline__\\w+__Please enter a headline or job title",
          ..
        ];
        ..
      var data = _get_form_values("global-form", fields);
      ..
      _ajax_post ("/users/add",
              data["data"],
              function (response) {
              ..
              });
            
