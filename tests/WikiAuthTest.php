<?php

include "test_includes.php";

class WikiAuthTest extends PHPUnit_Framework_TestCase {
  
  private $api_url = "";
  private $good_user = "";
  private $good_pass = "";
  private $bad_user = "";
  private $bad_pass = "";
  
  function testLoginWithGoodDetails() {
    
    $curl = new Curl();
    $wa = new WikiAuth( $this->api_url, $this->good_user, $this->good_pass, $curl );
    $wa->login(); // throws exception on failure
    $this->assertTrue(true);
  }
  
  function testLoginWithBadDetails() {
    
  }
}