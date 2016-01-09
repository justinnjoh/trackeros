<?php
// system model

class System_Model extends Base_model {

  public function initialise() {
  	// initialise the system - assumes tables have been created

    $error = "";
    $debug = "";
    $info = "";

  	// 1. see if there is a table; it should exist and have no rows
    $query = "select * from users; select * from priorities; select * from status_codes; ";
    //$query = "select * from priorities; select * from status_codes; ";
  	$res = $this->query($query);

  	$error = $this->db_error;
    DEBUG > 1 && $debug = $this->db_debug;

  	if ( strlen($error) < 1 ) {
  	  !isset($res[0]) && $error = "An unspecified error occurred - could not deduce if tracker has been initialised or not";
  	  strlen($error) < 1 && count($res[0]) > 0 && $error = "Sorry, tracker has already been initialised";
    }

    if ( strlen($error) < 1 ) {
      // we can initialise tracker

      $res = null;
      switch ($this->dblibrary) {

        case 'pdo_sqlsrv':
        case 'sqlsrv':
          $res = $this->initialise_mssql();
          break;

        case 'pdo_mysql':
        case 'mysqli':
          $res = $this->initialise_mysql();
          break;
      }

      if ( isset($res["errors"]) ) {
  	    $error = $res["errors"][0]["message"];
        DEBUG > 1 && $debug = $res["errors"][0]["debug"];
      }

      if ( strlen($error) < 1 ) {
        // attempt to create admin
        $res = $this->setup_admin();

        $error = $res["errors"][0]["message"];
        $info = $res["info"];        
        DEBUG > 1 && $debug = $res["errors"][0]["debug"];
      }
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(),
      'protected' => "",
      'info' => $info
    );

    $this->get_menu();
    $this->template->assign("initialise_result", $result);

    return ($result);
  }

  public function install() {
  	// install tables - assumes DB has been set up

    $error = "";
    $debug = "";
    $info = "";

  	// 1. see if there is a table - we should have an error - else can't initialise
  	$query = "select * from users; ";
  	$res = $this->query($query);

  	$error = $this->db_error;
    DEBUG > 1 && $debug = $this->db_debug;

  	if ( strlen($error) < 1 && isset($res[0][0]) && count($res[0][0]) > 0 ) {
  	  $error = "Tracker appears to have been installed already";
    }
    else {
      $error = ""; 
    }

    if ( strlen($error) < 1 ) {
      // attempt to install tracker

      $res = null;
      switch ($this->dblibrary) {

        case 'pdo_sqlsrv':
        case 'sqlsrv':
          $res = $this->install_mssql();
          break;

        case 'pdo_mysql':
        case 'mysqli':
          $res = $this->install_mysql();
          break;

      }

      if ( isset($res["errors"]) ) {
  	    $error = $res["errors"][0]["message"];
        $info = $res["info"];

        DEBUG > 1 && $debug = $res["errors"][0]["debug"];
      }

    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(),
      'protected' => "",
      'info' => $info
    );

    $this->get_menu();
    $this->template->assign("install_result", $result);

    return ($result);
  }







  private function initialise_mssql() {
  	// initialise the system - MS SQL Server

    $error = "";
    $debug = "";

    try {
      // we can initialise tracker
      $query = <<<EOT

set nocount on;

truncate table categories;
truncate table errors;
truncate table featured_posts;
truncate table organisations;
truncate table posts;
truncate table post_comments;
truncate table post_files;
truncate table pre_uploads;
truncate table priorities;
truncate table user_details;
truncate table user_tokens;
truncate table users;
truncate table visits;

set identity_insert organisations on;
insert into organisations (id, organisation, description, domain, url, created_by, status) values (9, 'Simple Tracker', 'Default organisation for Simple Tracker', '', '', 10, 10);
set identity_insert organisations off;

set identity_insert users on;
insert into users (id, provider, user_name, password) values (9, 'self', 'anon@lisol.co.uk', '');
set identity_insert users off;

insert into user_details (user_id, organisation_id, name, email, headline, rights, about, created_by, status) values (9, 9, 'Guest User', 'anon@lisol.co.uk', 'Anonymous Tracker User', 0, '', 10, 1);

insert into priorities (id, priority, description, icon, class, status) values (0, 'None', 'None or not applicable', '<span class=''fa fa-star''></span>', 'text-muted', 1);
insert into priorities (id, priority, description, icon, class, status) values (100, 'Low', 'No user impact – correct as soon as possible', '<span class=''fa fa-star''></span>', 'text-muted', 10);
insert into priorities (id, priority, description, icon, class, status) values (200, 'Medium', 'May put off users – provide a better way', '<span class=''fa fa-star''></span>', 'text-info', 10);
insert into priorities (id, priority, description, icon, class, status) values (300, 'High', 'Unable to access a function or some resource – no work-around exists', '<span class=''fa fa-star''></span>', 'text-warning', 10);
insert into priorities (id, priority, description, icon, class, status) values (500, 'Critical', 'System down or data corruption is likely', '<span class=''fa fa-star''></span>', 'text-danger', 10);

insert into status_codes (id, code, description, icon, class, status) values (0, 'Deleted', 'Marked for deletion', '<span class=''fa fa-close''></span>', 'text-muted', 1);
insert into status_codes (id, code, description, icon, class, status) values (1, 'Inactive', 'Not available for public view', '<span class=''fa  fa-ban''></span>', 'text-muted', 10);
insert into status_codes (id, code, description, icon, class, status) values (2, 'Closed', 'Closed and archived - issue is resolved', '<span class=''fa  fa-check''></span>', 'text-info', 10);
insert into status_codes (id, code, description, icon, class, status) values (10, 'Open', 'Open and available - unresolved issue', '<span class=''fa  fa-folder-open''></span>', 'text-success', 10);
insert into status_codes (id, code, description, icon, class, status) values (11, 'New', 'New and awaiting validation', '<span class=''fa  fa-question''></span>', 'text-info', 10);

-- categories
set identity_insert categories on;
insert into categories (id, organisation_id, position, category, description, type, posts_status, create_visit_id, created_by, status) values (101, 9, 0, 'Tutorials', 'Tutorials for public consumption', 0, 10, 11, 10, 10)
insert into categories (id, organisation_id, position, category, description, type, posts_status, create_visit_id, created_by, status) values (111, 9, 0, 'Resources', 'Resources for public consumption', 0, 10, 11, 10, 10)
set identity_insert categories on;

select * from status_codes where id = -1;

EOT;

      $res = $this->query($query);

  	  $error = $this->db_error;
      DEBUG > 1 && $debug = $this->db_debug;

    }

    catch (Exception $e) {
      $error = "Error initialising Tracker : " . $e->getMessage();
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
      ),
      'protected' => "",
      'info' => ""
    );

    return ($result);
  }

  private function initialise_mysql() {
    // initialise the system - MySQL

    $error = "";
    $debug = "";


    try {
      // we can initialise tracker
      $query = <<<EOT

truncate table categories;
truncate table errors;
truncate table featured_posts;
truncate table organisations;
truncate table posts;
truncate table post_comments;
truncate table post_files;
truncate table pre_uploads;
truncate table priorities;
truncate table user_details;
truncate table user_tokens;
truncate table users;
truncate table visits;

insert into organisations (id, organisation, description, domain, url, created_by, status) values (9, 'Simple Tracker', 'Default organisation for Simple Tracker', '', '', 10, 10);

insert into organisations (id, organisation, description, domain, url, created_by, status) values (100, 'Simple Tracker', 'Default organisation for Simple Tracker', '', '', 10, 10);
delete from organisations where id = 100;

insert into users (id, provider, user_name, password) values (9, 'self', 'anon@lisol.co.uk', '');
insert into user_details (user_id, organisation_id, name, email, headline, rights, about, created_by, status) values (9, 9, 'Guest User', 'anon@lisol.co.uk', 'Anonymous Tracker User', 0, '', 10, 1);

insert into priorities (id, priority, description, icon, class, status) values (0, 'None', 'None or not applicable', '<span class=''fa fa-star''></span>', 'text-muted', 1);
insert into priorities (id, priority, description, icon, class, status) values (100, 'Low', 'No user impact – correct as soon as possible', '<span class=''fa fa-star''></span>', 'text-muted', 10);
insert into priorities (id, priority, description, icon, class, status) values (200, 'Medium', 'May put off users – provide a better way', '<span class=''fa fa-star''></span>', 'text-info', 10);
insert into priorities (id, priority, description, icon, class, status) values (300, 'High', 'Unable to access a function or some resource – no work-around exists', '<span class=''fa fa-star''></span>', 'text-warning', 10);
insert into priorities (id, priority, description, icon, class, status) values (500, 'Critical', 'System down or data corruption is likely', '<span class=''fa fa-star''></span>', 'text-danger', 10);

insert into status_codes (id, code, description, icon, class, status) values (0, 'Deleted', 'Marked for deletion', '<span class=''fa fa-close''></span>', 'text-muted', 1);
insert into status_codes (id, code, description, icon, class, status) values (1, 'Inactive', 'Not available for public view', '<span class=''fa  fa-ban''></span>', 'text-muted', 10);
insert into status_codes (id, code, description, icon, class, status) values (2, 'Closed', 'Closed and archived - issue is resolved', '<span class=''fa  fa-check''></span>', 'text-info', 10);
insert into status_codes (id, code, description, icon, class, status) values (10, 'Open', 'Open and available - unresolved issue', '<span class=''fa  fa-folder-open''></span>', 'text-success', 10);
insert into status_codes (id, code, description, icon, class, status) values (11, 'New', 'New and awaiting validation', '<span class=''fa  fa-question''></span>', 'text-info', 10);

insert into categories (id, organisation_id, position, category, description, type, posts_status, create_visit_id, created_by, status) values (101, 9, 0, 'Tutorials', 'Tutorials for public consumption', 0, 10, 11, 10, 10);
insert into categories (id, organisation_id, position, category, description, type, posts_status, create_visit_id, created_by, status) values (111, 9, 0, 'Resources', 'Resources for public consumption', 0, 10, 11, 10, 10);

ALTER TABLE visits AUTO_INCREMENT = 11;
ALTER TABLE users AUTO_INCREMENT = 101;
ALTER TABLE posts AUTO_INCREMENT = 501;
ALTER TABLE post_files AUTO_INCREMENT = 11;
ALTER TABLE post_comments AUTO_INCREMENT = 11;
ALTER TABLE organisations AUTO_INCREMENT = 101;
ALTER TABLE categories AUTO_INCREMENT = 501;
ALTER TABLE users AUTO_INCREMENT = 101;

select * from status_codes where id = -1;

EOT;

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 1 && $debug = $this->db_debug;

    }

    catch (Exception $e) {
      $error = "Error initialising Tracker : " . $e->getMessage();
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(),
      'protected' => "",
      'info' => ""
    );

    return ($result);
  }

  private function install_mssql() {
  	// install tables - MS SQL Server

    $error = "";
    $debug = "";
    $info = "";

    if ( strlen($error) < 1 ) {
      // attempt to install tracker
      $query = <<<EOT

CREATE TABLE [dbo].[categories](
  [id] [int] IDENTITY(500,4) NOT NULL,
  [organisation_id] [int] NOT NULL,
  [position] [int] NOT NULL CONSTRAINT [DF_categories_position]  DEFAULT ((0)),
  [category] [varchar](100) NOT NULL,
  [description] [varchar](300) NOT NULL CONSTRAINT [DF_categories_description]  DEFAULT (''),
  [type] [int] NOT NULL CONSTRAINT [DF_categories_type]  DEFAULT ((0)),
  [posts_status] [int] NOT NULL CONSTRAINT [DF_categories_posts_status]  DEFAULT ((11)),
  [create_visit_id] [bigint] NOT NULL,
  [created_by] [int] NOT NULL,
  [created_at] [datetime] NOT NULL CONSTRAINT [DF_categories_created_at]  DEFAULT (getdate()),
  [update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_categories_update_visit_id]  DEFAULT ((0)),
  [updated_by] [int] NOT NULL CONSTRAINT [DF_categories_updated_by]  DEFAULT ((0)),
  [updated_at] [datetime] NULL CONSTRAINT [DF_categories_updated_at]  DEFAULT (NULL),
  [status] [int] NOT NULL CONSTRAINT [DF_categories_status]  DEFAULT ((11)),
 CONSTRAINT [PK_categories] PRIMARY KEY CLUSTERED 
(
  [id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[errors](
	[id] [int] NOT NULL,
	[error] [varchar](200) NOT NULL,
	[count] [int] NOT NULL,
	[last_visit_id] [bigint] NOT NULL,
	[last_error_date] [datetime] NULL,
 CONSTRAINT [PK_errors] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[featured_posts](
	[post_id] [int] NOT NULL,
	[date] [datetime] NOT NULL CONSTRAINT [DF_featured_posts_date]  DEFAULT (getdate()),
	[create_visit_id] [bigint] NOT NULL CONSTRAINT [DF_featured_posts_create_visit_id]  DEFAULT ((0)),
	[created_by] [int] NOT NULL,
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_featured_posts_created_at]  DEFAULT (getdate()),
	[update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_featured_posts_update_visit_id]  DEFAULT ((0)),
	[updated_by] [int] NOT NULL CONSTRAINT [DF_featured_posts_updated_by]  DEFAULT ((0)),
	[updated_at] [datetime] NULL CONSTRAINT [DF_featured_posts_updated_at]  DEFAULT (NULL),
 CONSTRAINT [PK_featured_posts] PRIMARY KEY CLUSTERED 
(
	[post_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[organisations](
	[id] [int] IDENTITY(100,6) NOT NULL,
	[organisation] [varchar](200) NOT NULL,
	[description] [varchar](300) NOT NULL CONSTRAINT [DF_organisations_description]  DEFAULT (''),
	[domain] [varchar](100) NOT NULL CONSTRAINT [DF_organisations_domain]  DEFAULT (''),
	[url] [varchar](100) NOT NULL CONSTRAINT [DF_organisations_url]  DEFAULT (''),
	[create_visit_id] [bigint] NOT NULL CONSTRAINT [DF_organisations_create_visit_id]  DEFAULT ((0)),
	[created_by] [int] NOT NULL CONSTRAINT [DF_organisations_created_by]  DEFAULT ((0)),
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_organisations_created_at]  DEFAULT (getdate()),
	[update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_organisations_update_visit_id]  DEFAULT ((0)),
	[updated_by] [int] NOT NULL CONSTRAINT [DF_organisations_updated_by]  DEFAULT ((0)),
	[updated_at] [datetime] NULL CONSTRAINT [DF_organisations_updated_at]  DEFAULT (NULL),
	[status] [int] NOT NULL CONSTRAINT [DF_organisations_status]  DEFAULT ((10)),
 CONSTRAINT [PK_organisations] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[post_comments](
	[id] [int] IDENTITY(10,1) NOT NULL,
	[post_id] [int] NOT NULL,
	[comment] [varchar](800) NOT NULL,
	[files] [int] NOT NULL CONSTRAINT [DF_post_comments_files]  DEFAULT ((0)),
	[create_visit_id] [bigint] NOT NULL CONSTRAINT [DF_post_comments_create_visit_id]  DEFAULT ((0)),
	[created_by] [int] NOT NULL CONSTRAINT [DF_post_comments_created_by]  DEFAULT ((0)),
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_post_comments_created_at]  DEFAULT (getdate()),
	[update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_post_comments_update_visit_id]  DEFAULT ((0)),
	[updated_by] [int] NOT NULL CONSTRAINT [DF_post_comments_updated_by]  DEFAULT ((0)),
	[updated_at] [datetime] NULL CONSTRAINT [DF_post_comments_updated_at]  DEFAULT (NULL),
	[status] [int] NOT NULL CONSTRAINT [DF_post_comments_status]  DEFAULT ((11)),
 CONSTRAINT [PK_post_comments] PRIMARY KEY NONCLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[post_files](
	[id] [int] IDENTITY(10,1) NOT NULL,
	[post_id] [int] NOT NULL,
	[comment_id] [int] NOT NULL CONSTRAINT [DF_post_files_comment_id]  DEFAULT ((0)),
	[position] [int] NOT NULL CONSTRAINT [DF_post_files_position]  DEFAULT ((0)),
	[main] [int] NOT NULL CONSTRAINT [DF_post_files_main]  DEFAULT ((0)),
	[caption] [varchar](200) NOT NULL CONSTRAINT [DF_post_files_caption]  DEFAULT (''),
	[file_ext] [varchar](10) NOT NULL,
	[file_name] [varchar](100) NOT NULL CONSTRAINT [DF_post_files_file_name]  DEFAULT (''),
	[file_type] [varchar](100) NOT NULL CONSTRAINT [DF_post_files_file_type]  DEFAULT (''),
	[file_size] [int] NOT NULL CONSTRAINT [DF_post_files_file_size]  DEFAULT ((0)),
	[is_image] [int] NOT NULL CONSTRAINT [DF_post_files_is_image]  DEFAULT ((0)),
	[create_visit_id] [bigint] NOT NULL CONSTRAINT [DF_post_files_create_visit_id]  DEFAULT ((0)),
	[created_by] [int] NOT NULL CONSTRAINT [DF_post_files_created_by]  DEFAULT ((0)),
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_post_files_created_at]  DEFAULT (getdate()),
	[update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_post_files_update_visit_id]  DEFAULT ((0)),
	[updated_by] [int] NOT NULL CONSTRAINT [DF_post_files_updated_by]  DEFAULT ((0)),
	[updated_at] [datetime] NULL CONSTRAINT [DF_post_files_updated_at]  DEFAULT (NULL),
	[status] [int] NOT NULL CONSTRAINT [DF_post_files_status]  DEFAULT ((11)),
 CONSTRAINT [PK_post_files] PRIMARY KEY NONCLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[posts](
	[id] [int] IDENTITY(500,4) NOT NULL,
	[category_id] [int] NOT NULL,
	[assigned_to] [int] NOT NULL CONSTRAINT [DF_posts_assigned_to]  DEFAULT ((0)),
	[position] [int] NOT NULL CONSTRAINT [DF_posts_position]  DEFAULT ((0)),
	[title] [varchar](100) NOT NULL,
	[post] [varchar](800) NOT NULL,
	[url] [varchar](100) NOT NULL CONSTRAINT [DF_posts_url]  DEFAULT (''),
	[commenting] [int] NOT NULL CONSTRAINT [DF_posts_commenting]  DEFAULT ((5)),
	[priority] [int] NOT NULL CONSTRAINT [DF_posts_priority]  DEFAULT ((0)),
	[start_date_proposed] [datetime] NULL CONSTRAINT [DF_posts_start_date_proposed]  DEFAULT (NULL),
	[start_date_actual] [datetime] NULL CONSTRAINT [DF_posts_start_date_actual]  DEFAULT (NULL),
	[end_date_proposed] [datetime] NULL CONSTRAINT [DF_posts_end_date_proposed]  DEFAULT (NULL),
	[end_date_actual] [datetime] NULL CONSTRAINT [DF_posts_end_date_actual]  DEFAULT (NULL),
	[days_proposed] [int] NOT NULL CONSTRAINT [DF_posts_days_proposed]  DEFAULT ((-1)),
	[days_actual] [int] NOT NULL CONSTRAINT [DF_posts_days_actual]  DEFAULT ((-1)),
	[create_visit_id] [bigint] NOT NULL,
	[created_by] [int] NOT NULL,
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_posts_created_at]  DEFAULT (getdate()),
	[update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_posts_update_visit_id]  DEFAULT ((0)),
	[updated_by] [int] NOT NULL CONSTRAINT [DF_posts_updated_by]  DEFAULT ((0)),
	[updated_at] [datetime] NULL CONSTRAINT [DF_posts_updated_at]  DEFAULT (NULL),
	[text_in_file] [int] NOT NULL CONSTRAINT [DF_posts_text_in_file]  DEFAULT ((0)),
	[status] [int] NOT NULL CONSTRAINT [DF_posts_status]  DEFAULT ((11)),
 CONSTRAINT [PK_posts] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[pre_uploads](
	[id] [int] IDENTITY(10,1) NOT NULL,
	[position] [int] NOT NULL CONSTRAINT [DF_pre_uploads_position]  DEFAULT ((0)),
	[caption] [varchar](200) NOT NULL CONSTRAINT [DF_pre_uploads_caption]  DEFAULT (''),
	[file_ext] [varchar](10) NOT NULL,
	[file_name] [varchar](100) NOT NULL CONSTRAINT [DF_pre_uploads_file_name]  DEFAULT (''),
	[file_type] [varchar](100) NOT NULL CONSTRAINT [DF_pre_uploads_file_type]  DEFAULT (''),
	[file_size] [int] NOT NULL CONSTRAINT [DF_pre_uploads_file_size]  DEFAULT ((0)),
	[create_visit_id] [bigint] NOT NULL,
	[created_by] [int] NOT NULL,
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_pre_uploads_created_at]  DEFAULT (getdate()),
	[status] [int] NOT NULL CONSTRAINT [DF_pre_uploads_status]  DEFAULT ((11)),
 CONSTRAINT [PK_pre_uploads] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[priorities](
	[id] [int] NOT NULL,
	[priority] [varchar](40) NOT NULL,
	[description] [varchar](300) NOT NULL CONSTRAINT [DF_priorities_description]  DEFAULT (''),
	[icon] [varchar](100) NOT NULL CONSTRAINT [DF_priorities_icon]  DEFAULT (''),
	[class] [varchar](40) NOT NULL CONSTRAINT [DF_priorities_class]  DEFAULT (''),
	[status] [int] NOT NULL CONSTRAINT [DF_priorities_status]  DEFAULT ((10)),
 CONSTRAINT [PK_priorities] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[status_codes](
	[id] [int] NOT NULL,
	[code] [varchar](40) NOT NULL,
	[description] [varchar](300) NOT NULL CONSTRAINT [DF_status_codes_description]  DEFAULT (''),
	[icon] [varchar](100) NOT NULL CONSTRAINT [DF_status_codes_icon]  DEFAULT (''),
	[class] [varchar](40) NOT NULL CONSTRAINT [DF_status_codes_class]  DEFAULT (''),
	[status] [int] NOT NULL CONSTRAINT [DF_status_codes_status]  DEFAULT ((1)),
 CONSTRAINT [PK_status_codes] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE TABLE [dbo].[user_details](
	[user_id] [int] NOT NULL,
	[organisation_id] [int] NOT NULL,
	[name] [varchar](200) NOT NULL,
	[email] [varchar](100) NOT NULL CONSTRAINT [DF_user_details_email]  DEFAULT (''),
	[headline] [varchar](100) NOT NULL CONSTRAINT [DF_user_details_headline]  DEFAULT (''),
	[about] [varchar](800) NOT NULL CONSTRAINT [DF_user_details_about]  DEFAULT (''),
	[rights] [int] NOT NULL CONSTRAINT [DF_user_details_rights]  DEFAULT ((0)),
	[image_ext] [varchar](10) NOT NULL CONSTRAINT [DF_user_details_image_ext]  DEFAULT (''),
	[image_url] [varchar](200) NOT NULL CONSTRAINT [DF_user_details_image_url]  DEFAULT ('/assets/images/usr/9.gif'),
	[agreed_terms] [int] NOT NULL CONSTRAINT [DF_user_details_agreed_terms]  DEFAULT ((0)),
	[agreed_terms_at] [datetime] NULL CONSTRAINT [DF_user_details_agreed_terms_at]  DEFAULT (NULL),
	[create_visit_id] [bigint] NOT NULL CONSTRAINT [DF_user_details_create_visit_id]  DEFAULT ((0)),
	[created_by] [int] NOT NULL CONSTRAINT [DF_user_details_created_by]  DEFAULT ((0)),
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_user_details_created_at]  DEFAULT (getdate()),
	[update_visit_id] [bigint] NOT NULL CONSTRAINT [DF_user_details_update_visit_id]  DEFAULT ((0)),
	[updated_by] [int] NOT NULL CONSTRAINT [DF_user_details_updated_by]  DEFAULT ((0)),
	[updated_at] [datetime] NULL CONSTRAINT [DF_user_details_updated_at]  DEFAULT (NULL),
	[last_logged_in_at] [datetime] NULL CONSTRAINT [DF_user_details_last_logged_in_at]  DEFAULT (NULL),
	[status] [int] NOT NULL CONSTRAINT [DF_user_details_status]  DEFAULT ((11))
) ON [PRIMARY];

CREATE TABLE [dbo].[user_tokens](
	[token] [varchar](250) NOT NULL,
	[user_id] [int] NOT NULL,
  [status] [int] NOT NULL,
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_
  s_created_at]  DEFAULT (getdate()),
 CONSTRAINT [PK_user_tokens] PRIMARY KEY CLUSTERED 
(
	[token] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

ALTER TABLE [dbo].[user_tokens] ADD  CONSTRAINT [DF_user_tokens_status]  DEFAULT ((1)) FOR [status];

CREATE TABLE [dbo].[users](
  [id] [int] IDENTITY(100,1) NOT NULL,
  [provider] [varchar](50) NOT NULL,
  [user_name] [varchar](100) NOT NULL,
  [password] [varchar](250) NOT NULL,
 CONSTRAINT [PK_users] PRIMARY KEY NONCLUSTERED 
(
  [id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE UNIQUE CLUSTERED INDEX [IX_users] ON [dbo].[users]
(
  [provider] ASC,
  [user_name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

CREATE TABLE [dbo].[visits](
	[id] [bigint] IDENTITY(10,1) NOT NULL,
	[user_id] [int] NOT NULL CONSTRAINT [DF_visits_user_id]  DEFAULT ((0)),
	[method] [varchar](10) NOT NULL CONSTRAINT [DF_visits_method]  DEFAULT (''),
	[server_ip] [varchar](40) NOT NULL CONSTRAINT [DF_visits_server_ip]  DEFAULT (''),
	[remote_ip] [varchar](40) NOT NULL CONSTRAINT [DF_visits_remote_ip]  DEFAULT (''),
	[remote_host] [varchar](200) NOT NULL CONSTRAINT [DF_visits_remote_host]  DEFAULT (''),
	[user_agent] [varchar](200) NOT NULL,
	[referrer_url] [varchar](200) NOT NULL CONSTRAINT [DF_Table_1_referer_url]  DEFAULT (''),
	[request_url] [varchar](200) NOT NULL CONSTRAINT [DF_visits_request_url]  DEFAULT (''),
	[created_at] [datetime] NOT NULL CONSTRAINT [DF_visits_created_at]  DEFAULT (getdate()),
	[logged_in_at] [datetime] NULL CONSTRAINT [DF_visits_logged_in_at]  DEFAULT (NULL),
 CONSTRAINT [PK_visits] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

ALTER TABLE [dbo].[errors] ADD  CONSTRAINT [DF_errors_count]  DEFAULT ((0)) FOR [count];
ALTER TABLE [dbo].[errors] ADD  CONSTRAINT [DF_errors_last_visit_id]  DEFAULT ((0)) FOR [last_visit_id];
ALTER TABLE [dbo].[errors] ADD  CONSTRAINT [DF_errors_last_error_date]  DEFAULT (NULL) FOR [last_error_date];

select * from users where id = -1;
EOT;

      $res = $this->query($query);

  	  $error = $this->db_error;
      DEBUG > 1 && $debug = $this->db_debug;

      // initialise ?
      if ( strlen($error) <  1 ) {

      	$res = $this->initialise();

        $info = $res["info"];        
        $error = $res["errors"][0]["message"];
        DEBUG > 0 && $debug = $res["errors"][0]["debug"];

      }
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(),
      'protected' => "",
      'info' => $info
    );

    return ($result);
  }

  private function install_mysql() {
    // install tables - MySQL

    $error = "";
    $debug = "";
    $info = "";

    if ( strlen($error) < 1 ) {
      // attempt to install tracker
      $query = <<<EOT

CREATE TABLE IF NOT EXISTS categories (
  id INT NOT NULL AUTO_INCREMENT,
  organisation_id INT NOT NULL,
  position INT NOT NULL DEFAULT 0,
  category VARCHAR(100) NOT NULL,
  description VARCHAR(300) NOT NULL DEFAULT '',
  type INT NOT NULL DEFAULT 0,
  posts_status INT NOT NULL DEFAULT 11,
  create_visit_id BIGINT NOT NULL,
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id BIGINT NOT NULL DEFAULT 0,
  updated_by INT NOT NULL DEFAULT 0,
  updated_at DATETIME NULL DEFAULT NULL,
  status INT NOT NULL DEFAULT 11,
  PRIMARY KEY (id)
)
engine = InnoDB
AUTO_INCREMENT = 501;

CREATE TABLE IF NOT EXISTS errors (
  id int NOT NULL,
  error varchar(200) NOT NULL,
  count int NOT NULL,
  last_visit_id int NOT NULL,
  last_error_date datetime NULL,
  PRIMARY KEY (id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS featured_posts (
  post_id int NOT NULL,
  date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  create_visit_id bigint NOT NULL DEFAULT 0,
  created_by int NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id bigint NOT NULL DEFAULT 0,
  updated_by int NOT NULL DEFAULT 0,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (post_id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS organisations (
  id int NOT NULL AUTO_INCREMENT,
  organisation varchar(200) NOT NULL,
  description varchar(300) NOT NULL DEFAULT '',
  domain varchar(100) NOT NULL DEFAULT '',
  url varchar(100) NOT NULL DEFAULT '',
  create_visit_id bigint NOT NULL DEFAULT 0,
  created_by int NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id bigint NOT NULL DEFAULT 0,
  updated_by int NOT NULL DEFAULT 0,
  updated_at datetime NULL DEFAULT NULL,
  status int NOT NULL DEFAULT 10,
  PRIMARY KEY (id)
)
engine = InnoDB
AUTO_INCREMENT = 101;

CREATE TABLE IF NOT EXISTS post_comments (
  id int AUTO_INCREMENT NOT NULL,
  post_id int NOT NULL,
  comment varchar(800) NOT NULL,
  files int NOT NULL DEFAULT 0,
  create_visit_id bigint NOT NULL DEFAULT 0,
  created_by int NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id bigint NOT NULL DEFAULT 0,
  updated_by int NOT NULL DEFAULT 0,
  updated_at datetime NULL DEFAULT NULL,
  status int NOT NULL DEFAULT 11,
  PRIMARY KEY (id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS post_files (
  id int NOT NULL AUTO_INCREMENT,
  post_id int NOT NULL,
  comment_id int NOT NULL DEFAULT 0,
  position int NOT NULL DEFAULT 0,
  main int NOT NULL DEFAULT 0,
  caption varchar(200) NOT NULL DEFAULT '',
  file_ext varchar(10) NOT NULL,
  file_name varchar(100) NOT NULL DEFAULT '',
  file_type varchar(100) NOT NULL DEFAULT '',
  file_size int NOT NULL DEFAULT 0,
  is_image int NOT NULL DEFAULT 0,
  create_visit_id bigint NOT NULL DEFAULT 0,
  created_by int NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id bigint NOT NULL DEFAULT 0,
  updated_by int NOT NULL DEFAULT 0,
  updated_at datetime NULL DEFAULT NULL,
  status int NOT NULL DEFAULT 11,
  PRIMARY KEY (id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS posts (
  id int NOT NULL AUTO_INCREMENT,
  category_id int NOT NULL,
  assigned_to int NOT NULL DEFAULT 0,
  position int NOT NULL DEFAULT 0,
  title varchar(100) NOT NULL,
  post varchar(800) NOT NULL,
  url varchar(100) NOT NULL DEFAULT '',
  commenting int NOT NULL DEFAULT 5,
  priority int NOT NULL DEFAULT 0,
  start_date_proposed datetime NULL DEFAULT NULL,
  start_date_actual datetime NULL DEFAULT NULL,
  end_date_proposed datetime NULL DEFAULT NULL,
  end_date_actual datetime NULL DEFAULT NULL,
  days_proposed int NOT NULL DEFAULT -1,
  days_actual int NOT NULL DEFAULT -1,
  create_visit_id bigint NOT NULL,
  created_by int NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id bigint NOT NULL DEFAULT 0,
  updated_by int NOT NULL DEFAULT 0,
  updated_at datetime NULL DEFAULT NULL,
  text_in_file int NOT NULL DEFAULT 0,
  status int NOT NULL DEFAULT 11,
  PRIMARY KEY (id)
)
engine = InnoDB
AUTO_INCREMENT = 501;

CREATE TABLE IF NOT EXISTS pre_uploads (
  id int NOT NULL AUTO_INCREMENT,
  position int NOT NULL DEFAULT 0,
  caption varchar(200) NOT NULL DEFAULT '',
  file_ext varchar(10) NOT NULL,
  file_name varchar(100) NOT NULL DEFAULT '',
  file_type varchar(100) NOT NULL DEFAULT '',
  file_size int NOT NULL DEFAULT 0,
  create_visit_id bigint NOT NULL,
  created_by int NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status int NOT NULL DEFAULT 11,
  PRIMARY KEY (id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS priorities (
  id int NOT NULL,
  priority varchar(40) NOT NULL,
  description varchar(300) NOT NULL DEFAULT '',
  icon varchar(100) NOT NULL DEFAULT '',
  class varchar(40) NOT NULL DEFAULT '',
  status int NOT NULL DEFAULT 10,
  PRIMARY KEY (id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS status_codes (
  id int NOT NULL,
  code varchar(40) NOT NULL,
  description varchar(300) NOT NULL DEFAULT '',
  icon varchar(100) NOT NULL DEFAULT '',
  class varchar(40) NOT NULL DEFAULT '',
  status int NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS user_details (
  user_id int NOT NULL,
  organisation_id int NOT NULL,
  name varchar(200) NOT NULL,
  email varchar(100) NOT NULL DEFAULT '',
  headline varchar(100) NOT NULL DEFAULT '',
  about varchar(800) NOT NULL DEFAULT '',
  rights int NOT NULL DEFAULT 0,
  image_ext varchar(10) NOT NULL DEFAULT '',
  image_url varchar(200) NOT NULL DEFAULT '/assets/images/usr/9.gif',
  agreed_terms int NOT NULL DEFAULT 0,
  agreed_terms_at datetime NULL DEFAULT NULL,
  create_visit_id bigint NOT NULL DEFAULT 0,
  created_by int NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_visit_id bigint NOT NULL DEFAULT 0,
  updated_by int NOT NULL DEFAULT 0,
  updated_at datetime NULL DEFAULT NULL,
  last_logged_in_at datetime NULL DEFAULT NULL,
  status int NOT NULL DEFAULT 11,
  PRIMARY KEY (user_id)
)
engine = InnoDB
AUTO_INCREMENT = 101;

CREATE TABLE IF NOT EXISTS user_tokens (
  token varchar(250) NOT NULL,
  user_id int NOT NULL,
  status int NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (token)
)
engine = InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id int NOT NULL AUTO_INCREMENT,
  provider varchar(50) NOT NULL,
  user_name varchar(100) NOT NULL,
  password varchar(250) NOT NULL,
  PRIMARY KEY (id)
)
engine = InnoDB
AUTO_INCREMENT = 101;

CREATE UNIQUE INDEX `idx_users_provider_user_name` ON users (provider, user_name) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT;

CREATE TABLE IF NOT EXISTS visits (
  id bigint NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL DEFAULT 0,
  method varchar(10) NOT NULL DEFAULT '',
  server_ip varchar(40) NOT NULL DEFAULT '',
  remote_ip varchar(40) NOT NULL DEFAULT '',
  remote_host varchar(200) NOT NULL DEFAULT '',
  user_agent varchar(200) NOT NULL,
  referrer_url varchar(200) NOT NULL DEFAULT '',
  request_url varchar(200) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  logged_in_at datetime NULL DEFAULT NULL,
  PRIMARY KEY (id)
)
engine = InnoDB
AUTO_INCREMENT = 11;

select * from users where id = -1;
EOT;

      $res = $this->query($query);

      $error = $this->db_error;
      DEBUG > 1 && $debug = $this->db_debug;

      // initialise ?
      if ( strlen($error) <  1 ) {

        $res = $this->initialise();
        
        $info = $res["info"];
        $error = $res["errors"][0]["message"];
        DEBUG > 0 && $debug = $res["errors"][0]["debug"];

      }
    }

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(),
      'protected' => "",
      'info' => $info
    );

    return ($result);
  }





  private function setup_admin () {
    // set up admin if admin details were passed in config
    $error = "";
    $debug = "";
    $info = "An admin account was not set up because a valid sys admin email was not configured. Please, register an account normally and set rights manually in table user_details to 80 in the database.";

    $email = $this->admin_email;

    $name = $this->admin_name;
    strlen($name) < 1 && $name = "Tracker Admin";

    $user = array();

    if ( strlen($email) > 0 && filter_var($email, FILTER_VALIDATE_EMAIL) ) {
      $password = $this->codify($email);

      $u = new User_model($this->template, $this->query_string);
      $res = $u->add_user('self', $email, $password, $name, 10); 

      $error = $res["errors"][0]["message"];
      DEBUG > 1 && $debug = $res["errors"][0]["debug"];

      strlen($error) < 1 && !is_null($res) && isset($res["data"]) && $user = $res["data"];
      
      strlen($error) < 1 && count($user) < 1 && $error = "Attempt to create an admin account may have failed.";

      if ( strlen($error) > 0 ) {
        $info = "Please check the database and see if the admin user configured was added. If so, set their rights manually in table user_details to 80 and use the forgotton password feature to log in.";
      }
      else {
        // attempt to set rights
        $data = array (
            "rights" => 80
          );

        $res = $this->update('user_details', $data, $user["id"], true, 'user_id', null);

        $error = $this->db_error;
        DEBUG > 0 && $debug = $this->db_debug;

        if ( strlen($error) > 0 ) {
          $info = "Attempt to set admin rights may have failed. Please set rights manually in table user_details to 80 and use the forgotton password feature to log in.";
        }
        else {
          // success !
          $info = "An admin account was set up successfully. Please use the 'Forgot password' feature to set your password if needed.";
        }

      }

    }

    strlen($error) > 0 && $error = "Setup Admin user error: " . $error;

    $result = array (
      'errors' => array (
          array('message' => $error, 'debug' => $debug)
        ),
      'data' => array(
          'admin_name' => $name,
        ),
      'info' => $info
    );

    return ($result);
  }



}

?>
