<?php

class WikiMate {
	
	private $auth;
	
	function __construct( $api_url, $user, $pass ) {
		
		$auth = new WikiAuth( $api_url, $user, $pass, $curl );
		$auth->login();
		$this->auth = auth;
		$this->query = new WikiQuery( $this->auth, $curl );
	}
	
	function get_page( $title ) {
		
		return new WikiPage( $title, $this->query );
	}
}
