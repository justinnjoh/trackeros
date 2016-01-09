<?php
define('SITE_ROOT', 'location of tracker, one level above app - eg D:\websites\tracker'); // for references OUTSIDE of application root ie $_SERVER['DOCUMENT_ROOT']; 
define('DEBUG', 0);

// load loader
$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "loader.php";
if (is_readable($file)) {
  include_once($file);
  spl_autoload_register("Loader_Lib::load_class");
}
else {
	die('Loader [' . $file . '] not found');
}

// register error handlers - the class file will be automatically loaded
register_shutdown_function("Errors_Lib::handle_shutdown"); 
set_exception_handler("Errors_Lib::handle_exception");
set_error_handler("Errors_Lib::handle_error");

$template = new Template_Lib();
$router = new Router_Lib($template);
$router->run();

?>
