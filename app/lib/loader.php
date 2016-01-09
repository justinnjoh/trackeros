<?php

// autoloder - loaded first and registered

class Loader_Lib { 

  /* all classes intended for autoloaded will be named thus:
  1) ClassName_Controller - controllers to be found in /app/controllers
  2) ClassName_Model - models to be found in /app/models
  3) ClassName_Lib - library files to be found in /app/lib
  */

  public static function load_class($class) {
    if ( $class && strlen($class) > 1 ) {
      list($file, $dir) = preg_split('/_/', strtolower($class), 2);
      $dir && strtolower($dir) == "lib" || $dir .= "s";
      $file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file . ".php";
      is_readable($file) && include($file);

      //echo "<p>File to load: " . $file . "<p/>"; 
    }
  } // loadclass


} 


?>
