<?php

class WikiPage {
	
	private $title;
	private $text;
	private $query;
	private $categories;
	
	function __construct($title, WikiQuery $query) {
		
		$page_result = $query->get_page( $title );
		
		$data = array( )
	}
	
	function save() {
		
		$text = $this->text . $this->build_categories();
		
		$data = array(
			"title" => $this->title,
			"text" => $text
		);
		
		$this->query->edit( $data );
	}
	
	function extract_categories( $text ) {
		
		$array = array();
		
		if ( preg_match( "/\[\[Category:(.*)\]\]", $text, $matches ) )
			$array = array_slice($matches, 1);
			
		return $array;
	}
	
	function build_categories() {
		
		$category_string = "";
		
		foreach ( $this->categories as $cat )
			$category_string.= "\n[[Category:$cat]]";
			
		return $category_string
	}
	
	function get_text() {
		
		return $this->text;
	}
	
	function set_text( $text ) {
		
		$this->categories = $this->extract_categories($text);
		$this->text = preg_replace("/\[\[Category:.*\]\]","", $text);
	}
	
	function get_section() {
		
	}
	
	function set_section() {
		
	}
	
	function get_categories() {
		
		return $this->categories();
	}
	
	function add_categories( $cats=array() ) {
		
	}
}
