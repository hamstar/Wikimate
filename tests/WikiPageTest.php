<?php

include "test_includes.php";

class WikiMateTest extends PHPUnit_Framework_TestCase {
  
  private $api_url = "";
  private $user = "";
  private $pass = "";
  
  function testMissingPageTextIsEmpty() {
    
    $wm = new WikiMate($this->api_url, $this->user, $this->pass );
    $page = $wm->get_page("test_".mktime());
    $this->assertFalse( $page->exists() );
    $this->assertEquals( $page->get_text(), "" );
  }
  
  function testCreateNewPage() {
    
    $wm = new WikiMate($this->api_url, $this->user, $this->pass );
    $page = $wm->get_page("test_".mktime());
    $this->assertFalse( $page->exists() );
    $page->set_text("Test text")->save();
    $this->assertTrue( $page->exists() );
    $this->assertEquals( $page->get_text(), "Test text" );
  }
  
  /**
   * @expectedException WikiPageInvalidPageException
   */
  function testInvalidPage() {
    
    $wm = new WikiMate($this->api_url, $this->user, $this->pass );
    $page = $wm->get_page("/*-+~!@{}:<>;?#$%^&*()");
  }
  
  function testRemoveAllCategories() {
    
    $expected_array = array('test1', 'test2', 'test3');
    
    $wm = new WikiMate($this->api_url, $this->user, $this->pass );
    $page = $wm->get_page("category_test");
    
    $page->set_categories($expected_array)->save();
    $this->assertEquals( $expected_array, $page->get_categories() );
    $page->set_categories( array() )->save();
    $this->assertEquals( array(), $page->get_categories() );
  }
  
  /**
   * @depends testRemoveAllCategories
   */
  function testSetAndGetCategories() {
    
    $expected_array = array('test1', 'test2', 'test3');
    
    $wm = new WikiMate($this->api_url, $this->user, $this->pass );
    $page = $wm->get_page("category_test");
    
    $page->set_categories($expected_array)->save();
    $this->assertEquals( $expected_array, $page->get_categories() );
    $page->set_categories( array() )->save();
  }
}