<?php
 /**
 * Class to handle logging into the wiki API
 *
 * @author Robert McLeod
 * @version 0.1
 */
 class WikiLogin {
 
	private $c;
	private $details;
 
	public function __construct( ) {
		
		$this->c = WikiCurl::getCurlObject();
		$this->details = self::getDefaultDetails();
	}
 
	/**
	 * Returns an array of default details for logging into the API
	 *
	 * @return array of default details
	 */
	private static function getDefaultDetails() {
		
		return array(
			'action' => 'login',
			'lgname' => WIKI_USERNAME,
			'lgpassword' => WIKI_PASSWORD,
			'format' => 'json'
		);
	}
 
	/**
	 * Check that our API request was recognized
	 *
	 * @param StdClass $loginResult the json object
	 */
	private function preconditions( $loginResult ) {
		
		// Check if we got an API result or the API doc page (invalid request)
		if ( strstr( $loginResult, "This is an auto-generated MediaWiki API documentation page" ) ) {
		    throw new WikiLogonException("The API could not understand the first login request");
		}
	}
 
	/**
	 * The initial login request to the API
	 *
	 * @return StdClass containing json data
	 */
	private function initialRequest() {
		
		$loginResult = $this->c->post( WIKI_API, $this->details )->body;

		$this->preconditions( $loginResult );

		$loginResult = json_decode( $loginResult );
		
		WikiUtil::printDebug(
			"Debug output after first login request",
			array(
				'Login details' => $this->details,
				'Login result' => $loginResult
			)
		);
		
		return $loginResult;
		
	}
 
	/**
	 * If the api requests a token this method will try give the
	 * the api the token when given the login result
	 *
	 * @param StdClass $loginResult The json object from the initial request
	 */
	private function getToken( $loginResult ) {
		
		//Logger::log("Sending token {$loginResult->login->token}");
		$this->details['lgtoken'] = strtolower(trim($loginResult->login->token));

		// Send the confirm token request
		$loginResult = $this->c->post( WIKI_API, $this->details )->body;
		
		$this->preconditions( $loginResult );

		$loginResult = json_decode( $loginResult );
		
		WikiUtil::printDebug(
			"Debug output after first login request",
			array(
				'Login details' => $this->details,
				'Login result' => $loginResult
			)
		);
		
		if ( $loginResult->login->result == 'Success' ) {
			return true;
		}
		
		throw new WikiLoginException("The API will not accept the token");

	}
 
	/**
     * Logs in to the wiki
     * @return boolean true if logged in
     */
    public function login() {
	
		//Logger::log("Logging in");

		// Send the login request
		$loginResult = $this->initialRequest();
		
		switch ( $loginResult->login->result ) {
			case 'NeedToken': $this->getToken( $loginResult ); // will return true if no exception thrown
			case "Success": return true; break;
			case 'NotExists': throw new WikiLoginException('The username does not exist'); break;
			default: throw new WikiLoginException('The API result was: '. $loginResult->login->result); break;
		}
		
    }
 
 }
