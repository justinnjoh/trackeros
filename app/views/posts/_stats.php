<?php
//print_r($this->params["stats"]);

if ( isset($this->params["stats"]) ) {

  $errors = $this->get_errors($this->params["stats"]);

  if ( strlen($errors) > 0 ) {
    echo $errors;
  }

  else {

    $main = array();
    isset($this->params["stats"]["data"]["main"]) && $main = $this->params["stats"]["data"]["main"];

    if ( count($main) > 0 ) {

?>
    <h5 class="bg-info side-header">
      Main Stats
    </h5>

    <table class='stats stats-main'>
      <thead>
        <tr>
        <?php
          $counts = "";

          foreach ($main as $item ) {
            echo "<th title='" . $this->escape_string($item['description']) . "'>" .
              $item["icon"] . " " . $item["code"] .
              "</th>" . PHP_EOL;

            $count = is_null($item["count"]) ? 0 : $item["count"];
            $counts .= "<td>" . $count . "</td> " . PHP_EOL;
          }
        ?>
        </tr>
      </thead>

      <tbody>
        <?php
          echo "<tr> " .
            $counts .
            "</tr>" . PHP_EOL;
        ?>
      </tbody>

    </table>

<?php
    } // main

  } // else error

} // isset
?>
