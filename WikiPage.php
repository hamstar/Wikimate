<?php

class WikiPageInvalidPageException extends Exception{}

class WikiPage {
	
	private $query;
	
	private $title;
	private $text;
	private $categories;
	private $exists = false;
	private $edittoken;
	private $starttimestamp;
	
	function __construct($title, WikiQuery $query) {
		
		$page = $query->get_page_data( $title );
		$this->check_page_response( $page );
	}
	
	/**
	 * Process the page response, sets up this WikiPage with variables
	 * returned from the wiki api
	 * @param array $page the page data returned from WikiQuery::get_page_data()
	 */
	private function process_page_response( $page ) {
		
		if ( isset( $page['invalid'] ) )
			throw new WikiQueryInvalidPageException();
			
		if ( !isset( $page['missing'] ) ) {
			$this->exists = true;
			$this->text = $page['revisions'][0]['*']; // put the content into text
		}
		
		$this->edittoken = $page['edittoken'];
		$this->starttimestamp = $page['starttimestamp'];
	}
	
	function save() {
		
		$text = $this->text . $this->build_categories();
		
		$data = array(
			"title" => $this->title,
			"text" => $text,
			'token' => $this->edittoken,
			'starttimestamp' => $this->starttimestamp,
		);
		
		if ( !$this->exists ) {
			$data['createonly'] = "true"; // createonly if not exists
		} else {
			$data['nocreate'] = "true"; // don't create, it should exist
		}
		
		$this->query->set_page_data( $data );
	}
	
	/**
	 * Refreshes this page with the latest data from the API
	 * WARNING: Destroys all unsaved data
	 */
	function refresh() {
		
		$this->__construct( $this->title, $this->query );
	}
	
	/**
	 * Extracts categories from the text
	 * @param string $text the page text (wiki code)
	 * @return array the array of categories found in the text
	 */
	private function extract_categories( $text ) {
		
		$array = array();
		
		if ( preg_match( "/\[\[Category:(.*)\]\]", $text, $matches ) )
			$array = array_slice($matches, 1);
			
		return $array;
	}
	
	/**
	 * Builds the categories on this array into a string for
	 * appending to the end of the wiki text
	 * @return string the categories of this page in wikicode
	 */
	private function build_categories() {
		
		$category_string = "";
		
		foreach ( $this->categories as $cat )
			$category_string.= "\n[[Category:$cat]]";
			
		return $category_string
	}
	
	/**
	 * @return boolean true if this page exists
	 */
	function exists() {
		
		return $this->exists;
	}
	
	function get_text() {
		
		return $this->text;
	}
	
	/**
	 * Sets the pages text (wiki code) after stripping
	 * the categories from it
	 * @param string $text the wiki code text
	 * @return WikiPage this
	 */
	function set_text( $text ) {
		
		$this->categories = $this->extract_categories($text);
		$this->text = preg_replace("/\[\[Category:.*\]\]","", $text);
		return $this;
	}
	
	function get_sections() {
		
	}
	
	function get_section( $name_or_number ) {
		
	}
	
	function set_section() {
		
	}
	
	function get_categories() {
		return $this->categories();
	}
	
	function add_categories( $cats=array() ) {
		$this->categories = array_merge( $this->categories, $cats );
	}
	
	function set_categories( $cats = array() ) {
		$this->categories = $cats;
	}
}