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
	
	/**
	 * Prints a debug message along with any data given
	 * Debugging can be turned on by setting the WIKIMATE_DEBUG
	 * constant.
	 * @param string $message a debug message
	 * @param array $data array data to be printed out
	 * @return boolean false if debugging is turned off
	 */
	static function print_debug( $message, $data=array() ) {
		
		if ( !defined('WIKIMATE_DEBUG') )
			return false;
		
		echo "$message\n";
		
		if ( count( $data ) > 0 )
			echo "<pre>", print_r( $data, 1 ), "</pre>\n";
	}
}
