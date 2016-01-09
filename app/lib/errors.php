<?php
// error handlers

class Errors_Lib extends Exception {

  // normal php errors
  public static function handle_error ($errNo, $errMsg, $errFile, $errLine, $errContext) {
    echo "<div class='error'>{Error handler}" . PHP_EOL . "[" . $errNo . "] " . $errMsg . "<br />" . PHP_EOL;
    //echo "Line [" . $errLine . "] in file [" . $errFile . "<br />" . PHP_EOL . $errContext . "<br />" . PHP_EOL;
    echo "Line [" . $errLine . "] in file [" . $errFile . "]<br />" . PHP_EOL;
    echo "</div>" . PHP_EOL;

    return true;
  }

  public static function handle_exception (Exception $e) {
    echo "<div class='error'>{Exception handler}" . PHP_EOL . "[" . $e->getCode() . "] " . $e->getMessage() . "<br />" . PHP_EOL;
    echo "Line [" . $e->getLine() . "] in file [" . $e->getFile() . "]<br />" . PHP_EOL . $e->getTraceAsString() . "<br />" . PHP_EOL;
    echo "</div>" . PHP_EOL;

    return true;
  }

  public static function handle_shutdown() {
    $when = date("Y-m-d H:i:s (T)");

    $err = error_get_last();
    $msg = "";

    if ($err) {
      //$msg = $err['message'];

      $msg = "<div class='error'>{Shutdown handler}" . PHP_EOL;
      $msg .= $when . "<br />" . PHP_EOL;
      $msg .= "[" . $err['type'] . "] " . $err['message'] . "<br />" . PHP_EOL;
      $msg .= "Line [" . $err['line'] . "] in file [" . $err['file'] . "]<br />" . PHP_EOL;
      $msg .= "  </div>" . PHP_EOL;

    }

    //error_log(strip_tags($err), 0); // write to php log file

    echo $msg;
  }


}

//set_error_handler("handle_error");
//set_exception_handler('handle_exception');
//register_shutdown_function('handle_shutdown'); 


?>
