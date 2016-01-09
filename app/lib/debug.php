<?php

// debug utilities - can be removed in production mode
class Debug_lib {

  public function print_hash($h, $name = null) {
    echo is_null($name) ? "" : "<p><strong>" . $name . "</strong> </p>";

    echo "<table border='1'>";

    foreach ($h as $k => $v) {
      //echo "<tr> <td>" . $k . "</td> <td>" . $v . "</td> </tr>";

      echo "<tr> <td>" . $k . "</td> <td>";

      if ( is_array($v) ) {
        $this->print_hash($v, null);
      }
      else {
        echo $v . "</td> </tr>";
      }
    }

    echo "</table>";
  }

  public function print_object($obj) {
  	print_r($obj);
  }


}



?>
