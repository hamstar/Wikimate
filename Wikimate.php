<?php
/// =============================================================================
/// Wikimate is a wrapper for the MediaWiki API that aims to be very easy to use.
/// 
/// @version    0.11.0
/// @copyright  SPDX-License-Identifier: MIT
/// =============================================================================

/**
 * Provides an interface over wiki API objects such as pages.
 *
 * @author  Robert McLeod
 * @since   December 2010
 */
class Wikimate {
	/**
	 * @var  string  The current version number (conforms to http://semver.org/).
	 */
	const VERSION = '0.11.0';

	protected $api;
	protected $username;
	protected $password;

	/** @var  Requests_Session */
	protected $session;
	protected $useragent;

	protected $error = array();
	protected $debugMode = false;
	
	/**
	 * Create a new Wikimate object.
	 *
	 * @return  Wikimate
	 */
	function __construct( $api ) {
		$this->api = $api;
		
		$this->initRequests();
	}

	/**
	 * Set up a Requests_Session with appropriate user agent.
	 *
	 * @todo  Pull version number from elsewhere.
	 *
	 * @return  void
	 */
	protected function initRequests() {
		$this->session = new Requests_Session( $this->api );
		$this->useragent = "Wikimate ".self::VERSION." (https://github.com/hamstar/Wikimate)";
	}

	/**
	 * Logs in to the wiki
	 *
	 * @return  boolean  True if logged in
	 */
	public function login( $username, $password, $domain = NULL ) {
		//Logger::log( "Logging in" );
		
		$details = array(
			'action' => 'login',
			'lgname' => $username,
			'lgpassword' => $password,
			'format' => 'json'
		);

		// If $domain is provided, set the corresponding detail in the request information array
		if( is_string( $domain ) )
		{
			$details['lgdomain'] = $domain;
		}
		
		// Send the login request
		$response = $this->session->post( $this->api, array(), $details );
		// Check if we got an API result or the API doc page (invalid request)
		if ( strstr( $response->body, "This is an auto-generated MediaWiki API documentation page" ) ) {
			$this->error['login'] = "The API could not understand the first login request";
			return false;
		}

		$loginResult = json_decode( $response->body );
		
		if ( $this->debugMode ) {
			echo "Login request:\n";
			print_r( $details );
			echo "Login request response:\n";
			print_r( $loginResult );
		}
		
		if ( $loginResult->login->result == "NeedToken" ) {
			//Logger::log( "Sending token {$loginResult->login->token}" );
			$details['lgtoken'] = strtolower( trim( $loginResult->login->token ) );

			// Send the confirm token request
			$loginResult = $this->session->post( $this->api, array(), $details )->body;
			
			// Check if we got an API result or the API doc page (invalid request)
			if ( strstr( $loginResult, "This is an auto-generated MediaWiki API documentation page" ) ) {
				$this->error['login'] = "The API could not understand the confirm token request";
				return false;
			}
			
			$loginResult = json_decode( $loginResult );
			
			if ( $this->debugMode ) {
				echo "Confirm token request:\n";
				print_r( $details );
				echo "Confirm token response:\n";
				print_r( $loginResult );
			}
			
			if ( $loginResult->login->result != "Success" ) {
				// Some more comprehensive error checking
				switch ( $loginResult->login->result ) {
					case 'NotExists':
						$this->error['login'] = 'The username does not exist';
						break;
					default:
						$this->error['login'] = 'The API result was: ' . $loginResult->login->result;
						break;
				}
				return false;
			}
		}
		
		//Logger::log( "Logged in" );
		return true;
	}
	
	/**
	 * Sets the debug mode
	 *
	 * @param   boolean  $debugMode  True to turn debugging on
	 * @return  Wikimate             This object
	 */
	public function setDebugMode( $b ) {
		$this->debugMode = $b;
		return $this;
	}

	/**
	 * Used to return or print the curl settings, but now prints an error and
	 * returns Wikimate::debugRequestsConfig()
	 *
	 * @deprecated                  Since version 0.10.0
	 * @param       boolean  $echo  True to echo the configuration
	 * @return      mixed           Array of config if $echo is false, (boolean) true if echo is true
	 */
	public function debugCurlConfig( $echo = false ) {
		if ( $echo ) {
			echo "ERROR: Curl is no longer used by Wikimate.\n";
		}
		return $this->debugRequestsConfig( $echo );
	}

	/**
	 * Get or print the Requests configuration.
	 *
	 * @param   boolean  $echo  Whether to echo the options
	 * @return  array           Options if $echo is FALSE
	 * @return  boolean         True if options have been echoed to STDOUT
	 */
	public function debugRequestsConfig( $echo = FALSE ) {
		if ( $echo ) {
			echo "<pre>Requests options:\n";
			print_r( $this->session->options );
			echo "Requests headers:\n";
			print_r( $this->session->headers );
			echo "</pre>";
			return true;
		}
		return $this->session->options;
	}

	/**
	 * Returns a WikiPage object populated with the page data
	 *
	 * @param   string   $title  The name of the wiki article
	 * @return  WikiPage         The page object
	 */
	public function getPage( $title ) {
		return new WikiPage( $title, $this );
	}
	
	/**
	 * Performs a query to the wiki api with the given details
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Unserialized php output from the wiki API
	 */
	public function query( $array ) {
		$array['action'] = 'query';
		$array['format'] = 'php';
		
		$apiResult = $this->session->get( $this->api.'?'.http_build_query( $array ) );
		return unserialize( $apiResult->body );
	}

	/**
	 * Performs a parse query to the wiki API.
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Unserialized php output from the wiki API
	 */
	public function parse( $array ) {
		$array['action'] = 'parse';
		$array['format'] = 'php';
		
		$apiResult = $this->session->get( $this->api.'?'.http_build_query( $array ) );
		
		return unserialize( $apiResult->body );
	}
	
	/**
	 * Perfoms an edit query to the wiki API
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Unserialized php output from the wiki API
	 */
	public function edit( $array ) {
		$headers = array(
			'Content-Type' => "application/x-www-form-urlencoded"
		);
		
		$array['action'] = 'edit';
		$array['format'] = 'php';
		
		$apiResult = $this->session->post( $this->api, $headers, $array );
		
		return unserialize( $apiResult->body );
	}
	
	/**
	 * Perfoms a delete query to the wiki API
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Unserialized php output from the wiki API
	 */
	public function delete( $array ) {
		$headers = array(
			'Content-Type' => "application/x-www-form-urlencoded"
		);
		
		$array['action'] = 'delete';
		$array['format'] = 'php';
		
		$apiResult = $this->session->post( $this->api, $headers, $array );
		
		return unserialize( $apiResult );
	}
	
	public function getError() {
		return $this->error;
	}
}

/**
 * Models a wiki article page that can have its text altered and retrieved.
 * 
 * @author  Robert McLeod
 * @since   December 2010
 */
class WikiPage {
	const SECTIONLIST_BY_INDEX = 1;
	const SECTIONLIST_BY_NAME = 2;
	const SECTIONLIST_BY_NUMBER = 3;
	
	protected $title = null;
	protected $exists = false;
	protected $text = null;
	protected $edittoken = null;
	protected $starttimestamp = null;
	protected $wikimate = null;
	protected $error = null;
	protected $invalid = false;
	protected $sections = null;
	
	/*
	 *
	 * Magic methods
	 *
	 */
	
	/**
	 * Constructs a WikiPage object from the title given
	 * and adds a Wikimate object
	 *
	 * @param  string    $title     Name of the wiki article
	 * @param  Wikimate  $wikimate  Wikimate object
	 */
	function __construct( $title, $wikimate ) {
		$this->wikimate = $wikimate;
		$this->title    = $title;
		$this->text     = $this->getText( true );
		
		if ( $this->invalid ) {
			echo "Invalid page title - cannot create WikiPage";
			return null;
		}
	}
	
	/**
	 * Forget all object properties
	 *
	 * @return  <type> Destructor
	 */
	function __destruct() {
		$this->title          = null;
		$this->exists         = false;
		$this->text           = null;
		$this->edittoken      = null;
		$this->starttimestamp = null;
		$this->wikimate       = null;
		$this->error          = null;
		$this->invalid        = false;
		$this->sections       = null;
		return null;
	}
	
	/**
	 * Returns the wikicode of the page
	 *
	 * @return  string  String of wikicode
	 */
	function __toString() {
		return $this->text;
	}
	
	/**
	 * Returns an array sections with the section name as the key
	 *  and the text as the element, e.g.
	 *
	 * array(
	 *   'intro' => 'this text is the introduction',
	 *   'History' => 'this is text under the history section'
	 * )
	 *
	 * @return  array  Array of sections
	 */
	function __invoke() {
		return $this->getAllSections( false, self::SECTIONLIST_BY_NAME );
	}
	
	/**
	 * Returns the page existance status
	 *
	 * @return  boolean  True if page exists
	 */
	function exists() {
		return $this->exists;
	}
	
	/**
	 * Alias of self::__destruct()
	 */
	function destroy() {
		$this->__destruct();
	}
	
	/*
	 *
	 * Page meta functions
	 *
	 */
	
	/**
	 * Returns an error if there is one, null shows no error
	 * 
	 * @return  mixed  Null for no errors, or an error array object
	 */
	function getError() {
		return $this->error;
	}
	
	/**
	 * Returns the title of this page
	 *
	 * @return  string  The title of this page
	 */
	function getTitle() {
		return $this->title;
	}
	
	/**
	 * Returns the number of sections in this page
	 *
	 * @return  integer  The number of sections in this page
	 */
	function getNumSections() {
		return count( $this->sections->byIndex );
	}
	
	/**
	 * Returns the sections offsets and lengths
	 *
	 * @return  StdClass  Section class
	 */
	function getSectionOffsets() {
		return $this->sections;
	}
	
	/*
	 *
	 * Getter functions
	 *
	 */
	
	/**
	 * Gets the text of the page. If refresh is true,
	 * then this method will query the wiki api again for the page details.
	 *
	 * @param   boolean  $refresh  True to query the wiki api again
	 * @return  string             The text of the page
	 */
	function getText( $refresh = false ) {
		if ( $refresh ) { // We want to query the API
			
			$data = array(
				'prop' => 'info|revisions',
				'intoken' => 'edit',
				'titles' => $this->title,
				'rvprop' => 'content' // Need to get page text
			);
			
			$r = $this->wikimate->query( $data ); // Run the query
			
			// Check for errors
			if ( isset( $r['error'] ) ) {
				$this->error = $r; // Set the error if there was one
			} else {
				$this->error = null; // Reset the error status
			}
			
			// Get the page (there should only be one)
			$page = array_pop( $r['query']['pages'] );
			
			unset( $r, $data );
			
			if ( isset( $page['invalid'] ) ) {
				$this->invalid = true;
			}
			
			$this->edittoken      = $page['edittoken'];
			$this->starttimestamp = $page['starttimestamp'];
			
			if ( !isset( $page['missing'] ) ) {
				// Update the existance if the page is there
				$this->exists = true;
				// Put the content into text
				$this->text   = $page['revisions'][0]['*'];
			}
			
			unset( $page );
			
			// Now we need to get the section information
			preg_match_all( '/(={1,6}).*?\1 *\n/', $this->text, $m );
			
			// Set the intro section (between title and first section)
			$this->sections->byIndex[0]['offset']      = 0;
			$this->sections->byName['intro']['offset'] = 0;
			
			if ( empty( $m[0] ) ) {
				// Define lengths for page without sections
				$this->sections->byIndex[0]['length']      = strlen( $this->text );
				$this->sections->byName['intro']['length'] = strlen( $this->text );
			} else {
				// Array of section names
				$sections = $m[0];
				
				// Setup the current section
				$currIndex = 0;
				$currName  = 'intro';
				
				foreach ( $sections as $section ) {
					// Get the current offset
					$currOffset = strpos( $this->text, $section, $this->sections->byIndex[$currIndex]['offset'] );
					
					// Are we still on the first section?
					if ( $currIndex == 0 ) {
						$this->sections->byIndex[$currIndex]['length'] = $currOffset;
						$this->sections->byName[$currName]['length']   = $currOffset;
					}
					
					// Get the current name and index
					$currName = trim( str_replace( '=', '', $section ) );
					$currIndex++;
					
					// Search for existing name and create unique one
					$cName = $currName;
					for ($seq = 2; array_key_exists($cName, $this->sections->byName); $seq++) {
						$cName = $currName . '_' . $seq;
					}
					if ($seq > 2) {
						$currName = $cName;
					}

					// Set the offset for the current section
					$this->sections->byIndex[$currIndex]['offset'] = $currOffset;
					$this->sections->byName[$currName]['offset']   = $currOffset;
					
					// If there is a section after this, set the length of this one
					if ( isset( $sections[$currIndex] ) ) {
						// Get the offset of the next section
						$nextOffset = strpos( $this->text, $sections[$currIndex], $currOffset );
						// Calculate the length of this one
						$length     = $nextOffset - $currOffset;
						
						// Set the length of this section
						$this->sections->byIndex[$currIndex]['length'] = $length;
						$this->sections->byName[$currName]['length']   = $length;
					}
					else {
						// Set the length of last section
						$this->sections->byIndex[$currIndex]['length'] = strlen($this->text) - $currOffset;
						$this->sections->byName[$currName]['length']   = strlen($this->text) - $currOffset;
					}
				}
			}
		}
		
		return $this->text; // Return the text in any case
	}
	
	/**
	 * Returns the section requested. Section can be the following:
	 * - section name (string:"History")
	 * - section index (int:3)
	 *
	 * @param   mixed    $section         The section to get
	 * @param   boolean  $includeHeading  False to get section text only
	 * @return  string                    Wikitext of the section on the page
	 */
	function getSection( $section, $includeHeading = false ) {
		// Check if we have a section name or index
		if ( is_int( $section ) ) {
			$coords = $this->sections->byIndex[$section];
		} else if ( is_string( $section ) ) {
			$coords = $this->sections->byName[$section];
		}
		
		// Extract the text
		@extract( $coords );
		$text = substr( $this->text, $offset, $length );
		
		// Whack off the heading if need be
		if ( !$includeHeading && $offset > 0 ) {
			// Chop off the first line
			$text = substr( $text, strpos( trim( $text ), "\n" ) );
		}
		
		return $text;
	}
	
	/**
	 * Return all the sections of the page in an array - the key names can be
	 * set to name or index by using the following for the second param
	 * - self::SECTIONLIST_BY_NAME
	 * - self::SECTIONLIST_BY_INDEX
	 *
	 * @param   boolean  $includeHeading  False to get section text only
	 * @param   integer  $keyNames        Modifier for the array key names
	 * @return  array                     Array of sections
	 */
	function getAllSections( $includeHeading = false, $keyNames = self::SECTIONLIST_BY_INDEX ) {
		$sections = array();
		
		switch ( $keyNames ) {
			case self::SECTIONLIST_BY_INDEX:
				$array = array_keys( $this->sections->byIndex );
				break;
			case self::SECTIONLIST_BY_NAME:
				$array = array_keys( $this->sections->byName );
				break;
			default:
				throw new Exception( 'Unexpected parameter $keyNames given to WikiPage::getAllSections()' );
				break;
		}
		
		foreach ( $array as $key ) {
			$sections[$key] = $this->getSection( $key, $includeHeading );
		}
		
		return $sections;
	}
	
	/*
	 *
	 * Setter functions
	 *
	 */
	
	/**
	 * Sets the text in the page.  Updates the starttimestamp to the timestamp
	 * after the page edit (if the edit is successful)
	 *
	 * @param   string   $text     The article text
	 * @param   string   $section  The section to edit (null for whole page)
	 * @param   boolean  $minor    True for minor edit
	 * @param   string   $summary  Summary text
	 * @return  boolean            True if page was edited successfully
	 */
	function setText( $text, $section = null, $minor = false, $summary = null ) {
		$data = array(
			'title' => $this->title,
			'text' => $text,
			'md5' => md5( $text ),
			'bot' => "true",
			'token' => $this->edittoken,
			'starttimestamp' => $this->starttimestamp
		);
		
		// Set options from arguments
		if ( !is_null( $section ) )
			$data['section'] = $section;
		if ( $minor )
			$data['minor'] = $minor;
		if ( !is_null( $summary ) )
			$data['summary'] = $summary;
		
		// Make sure we don't create a page by accident or overwrite another one
		if ( !$this->exists ) {
			$data['createonly'] = "true"; // createonly if not exists
		} else {
			$data['nocreate'] = "true"; // Don't create, it should exist
		}
		
		$r = $this->wikimate->edit( $data ); // The edit query
		
		// Check if it worked
		if ( $r['edit']['result'] == "Success" ) {
			$this->exists = true;
			
			if ( is_null( $section ) ) {
				$this->text = $text;
			} else {
			}
			
			// Get the new starttimestamp
			$data = array(
				'prop' => 'info',
				'intoken' => 'edit',
				'titles' => $this->title
			);
			
			$r = $this->wikimate->query( $data );
			
			$page = array_pop( $r['query']['pages'] ); // Get the page
			
			$this->starttimestamp = $page['starttimestamp']; // Update the starttimestamp
			
			$this->error = null; // Reset the error
			return true;
		}
		
		$this->error = $r;
		return false;
	}
	
	/**
	 * Sets the text of the given section.
	 * Essentially an alias of WikiPage:setText()
	 * with the summary and minor parameters switched.
	 *
	 * @param   string   $text     The text of the section
	 * @param   mixed    $section  Section index, new by default
	 * @param   string   $summary  Summary text
	 * @param   boolean  $minor    True for minor edit
	 * @return  boolean            True if the section was saved
	 */
	function setSection( $text, $section = 0, $summary = null, $minor = false ) {
		return $this->setText( $text, $section, $minor, $summary );
	}
	
	/**
	 * Alias of WikiPage::setSection() specifically for creating new sections
	 *
	 * @param   string   $name  The heading name for the new section
	 * @param   string   $text  The text of the new section
	 * @return  boolean         True if the section was saved
	 */
	function newSection( $name, $text ) {
		return $this->setSection( $text, $section = 'new', $summary = $name, $minor = false );
	}
	
	/**
	 * Delete the page
	 *
	 * @param   string   $reason  Reason for the deletion
	 * @return  boolean           True if page was deleted successfully
	 */
	function delete( $reason = null ) {
		$data = array(
			'title' => $this->title,
			'token' => $this->edittoken
		);
		
		// Set options from arguments
		if ( !is_null( $reason ) )
			$data['reason'] = $reason;
		
		$r = $this->wikimate->delete( $data ); // The delete query
		
		// Check if it worked
		if ( $r['delete'] ) {
			$this->exists = false; // The page was deleted
			
			$this->error = null; // Reset the error
			return true;
		}
		
		$this->error = $r;
		return false;
	}
}
