<?php
  // application level helpers

class App_Helper extends Base_Helper {
  // some globs 

  // Dynamic: these are set when needed from DB
  public $status_codes = array(); // 
  public $priorities = array();

  // Hard-coded: these are currently hard-coded
  public $rights = array();
  public $rates = array();
  public $page_sizes = array();

  public function __construct(Template_Lib $templ = null, Router_Lib $routr = null) {

    parent::__construct($templ, $routr);

    // set globs
    $this->set_globs();
  }

  public function rate($raters, $points) {
    // return a star ratings string for the number;    
    $rate = -1;
    $percent = 0;

    is_numeric($raters) || $raters = 0;
    is_numeric($points) || $points = 0;

    if ($raters > 0 && $points >= 0) {
      $percent = abs( ($points * 25) / $raters );
      $percent > 100 && $percent = 100;
      //float round ( float $val [, int $precision = 0 [, int $mode = PHP_ROUND_HALF_UP ]] )
      $rate = round($percent/25, 0, PHP_ROUND_HALF_UP);
    }
    //$title = "Rate: $rate; Percent: $percent; Points: $points; Raters: $raters";
    $title = array_key_exists("$rate", $this->rates) ? $this->rates[$rate] : "n/a";
    $raters > 0 && $points >= 0 && $title .= " (by " . $raters . " people)";
    
    $res = "<span title='" . $title . "' style='display: block; width: 65px; height: 13px; background: url(/assets/images/ratings.png) 0 0;'>" . PHP_EOL;
    $res .= "  <span style='display: block; clear: left; width: " . $percent . "%; height: 13px; background: url(/assets/images/ratings.png) 0 -13px;'></span>" . PHP_EOL;
    $res .= "</span>" . PHP_EOL;

    return ($res);
  }


  // HTML related functions

  public function get_select_options ($array = null, $select = null) {
    // returns an options list in a string
    $res = "";
    if ($array && is_array($array)) {

      foreach ($array as $key => $value) {
        $res .= "<option value='" . $key . "'";
        !($select === null) && $select == $key && $res .= " selected";
        $res .= ">" . $value . "</option>" . PHP_EOL;
      }
    }

    return $res;
  }

  public function get_rowset_select_options ($rowset = null, $values = null, $text = null, $select = null) {
    // returns an options list in a string from a rowset; $values = values field; $text = options text field
    $res = ""; 
    if ($rowset && $values && $text) {

      foreach ($rowset as $item) {
        $res .= "<option value='" . $item[$values] . "'";
        !($select === null) && $select == $item[$values] && $res .= " selected";
        $res .= ">" . $item[$text] . "</option>" . PHP_EOL;
      }

    }

    return $res;
  }

  public function get_radio_options ($array = null, $select = null, $name = "radio") {
    // returns a radio buttons options list in a string
    $res = "";
    if ($array && is_array($array)) {

      foreach ($array as $key => $value) {
        $res .= "  <input type='radio' value='" . $key . "' id='" . $name . "' name='" . $name ."'";
        !($select === null) && $select == $key && $res .= " checked";
        $res .= ">" . $value . PHP_EOL;
      }
    }

    return $res;
  }


  public function get_status($id = -1, $alt = 'unknown') {
    return($this->get_hash_value($this->status_codes, $id, $alt));
  }

 public function get_status_html($id = -1, $alt = 'unknown') {
    // return full html to display a status
    $status = $this->get_hash_value($this->status_codes, $id, $alt);

    $icon = strlen($status[2]) > 0 ? $status[2] : "<span class='fa fa-signal'></span>";

    $result = "<span title='Status: " . $status[1] . "'>" .
      $icon . " " .
      $status[0] .
      "</span>";

    return ($result);
  }

  public function get_right($id = -1, $alt = 'unknown') {
    // get publish code
    return($this->get_hash_value($this->rights, $id, $alt));
  }

  public function get_priority($id = -1, $alt = 'unknown') {
    // get priority code
    return($this->get_hash_value($this->priorities, $id, $alt));
  }

  public function get_priority_html($id = -1, $alt = 'unknown') {
    // get priority html
    $priority = $this->get_hash_value($this->priorities, $id, $alt);

    $icon = strlen($priority[2]) > 0 ? $priority[2] : "<span class='fa fa-star'></span>";
    $result = "<span class='pull-right priority " . $priority[3] .
      "' title='Priority: " . $priority[1] . "'>" .
      $icon . " " . $priority[0] .
      "</span>";

    return ($result);
  }

  public function get_hash_value($hash, $key, $alt) {
    // returns null if no hash, unknown, "" if no key or the value associated with the key
    $res = $alt;
    $hash && is_array($hash) && $res = array_key_exists($key, $hash) ? $hash[$key] : array("unknown", "");

    return($res);
  }





  private function set_globs() {
    // set hard-coded globs

    // rights
    $this->rights = array(
        "100" => array("Super user", "This is the most powerful user of the system - reserved for user Super User only"),
        "80" => array("System administrator", "This is the system administrator with access to everything"),
        "30" => array("Administrator", "Manages users and content for their own organisation"),
        "0" => array("User", "Ordinary user with no rights"),
      );

    // rates
    $this->rates = array(
        //"-1" => "Not rated yet",
        "0" => "Useless",
        "1" => "Not bad",
        "2" => "Ok",
        "3" => "Good",
        "4" => "Excellent",
      );

    // page sizes
    $this->page_sizes = array(
        "1" => "1",
        "5" => "5",
        "10" => "10",
        "15" => "15",
        "20" => "20",
        "30" => "30",
        "40" => "40",
        "50" => "50",
        "100" => "100",
      );
  }




}

?>
