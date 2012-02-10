<?php

/**
 * The client usable part of the bot.  Acts as a manager of all the wiki
 * api objects, handles authentication and generates pages
 * @author	Robert McLeod
 * @since	Febuary 10th 2012
 * @licence	http://creativecommons.org/licenses/by-nc/3.0/nz/
 */
class WikiMate {
	
	private $auth;
	private $query;
	
	function __construct( $api_url, $user, $pass ) {
		
		$auth = new WikiAuth( $api_url, $user, $pass, $curl );
		$auth->login();
		$this->auth = auth;
		$this->query = new WikiQuery( $curl, $api_url );
	}
	
	function get_page( $title ) {
		
		return new WikiPage( $title, $this->query );
	}
}
