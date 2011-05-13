<?php
 /**
  * Provides a nice generic class to allow WikiMate objects
  * to make queries to the wiki api
  * 
  * @author Robert McLeod
  * @version 0.1
  */
 class WikiCurl {
	
	private $c;

	function __construct() {

		$this->c = self::getCurlObject();
	}
	
	/**
	 * Gets a curl object initialized with our settings
	 *
	 * @return Curl wrapper initalized for WikiMate
	 */
	public static function getCurlObject() {
		
		// Setup curl
		$c = new Curl();
		$c->user_agent = "Wikimate 0.5-oop";
		$c->cookie_file = "wikimate_cookie.txt";
		
		return $c;
	}
	
    /**
     * Performs a query to the wiki api with the given details
     * @param array $array array of details to be passed in the query
     * @return array unserialized php output from the wiki
     */
    public function query( $array ) {

		$array['action'] = 'query';
		$array['format'] = 'php';

		$apiResult = $this->c->get( WIKI_API, $array );

		return unserialize($apiResult);
		
    }

    /**
     * Perfoms an edit query to the wiki api
     * @param array $array array of details to be passed in the query
     * @return array unserialized php output from the wiki
     */
    public function edit( $array ) {
		$c = $this->c;
		$c->headers['Content-Type'] = "application/x-www-form-urlencoded";

		$array['action'] = 'edit';
		$array['format'] = 'php';

		$apiResult = $c->post( WIKI_API, $array );

		return unserialize($apiResult);
    }
	
	/**
     * Print the curl settings.
     */
    public function debugCurlConfig() {
		Util::printDebug( "Curl Configuration", array( 'Curl Options' => $this->c->options ) );
    }        
	
}
