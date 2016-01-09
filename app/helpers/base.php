<?php

abstract class Base_Helper {
// common functionalities for helpers should be defined here.

  protected $router = null;
  protected $template = null;

  public function __construct(Template_Lib $templ = null, Router_Lib $routr = null) {

  	$routr && $this->router = $routr;
    $templ && $this->template = $templ;

  }


}


?>
