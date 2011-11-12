<?php
/**
 * Provides an interface over wiki api objects such as pages
 * Logs into the wiki on construction
 *
 * @author Robert McLeod
 * @since December 2010
 * @version 0.5
 */

class Wikimate {

    const SECTIONLIST_BY_NAME = 1;
    const SECTIONLIST_BY_INDEX = 2;
    
    private $api;
    private $username;
    private $password;

    private $c = null;
    private $error = array();
    private $debugMode = false;

    /**
     * Creates a curl object and logs in
     * If it can't login the class will exit and return null
     */
    function __construct( $api ) {
    	
    		$this->api = $api;
    		
		$this->initCurl();		
		$this->checkCookieFileIsWritable();
    }
    
    private function initCurl() {
    	
    	if ( !class_exists('Curl') || !class_exists('CurlResponse') )
		throw new Exception("Failed to create Wikimate - could not find the Curl class");
		
	$this->c = new Curl();
	$this->c->user_agent = "Wikimate 0.5";
	$this->c->cookie_file = "wikimate_cookie.txt";
    }
    
    private function checkCookieFileIsWritable() {
    	
	if ( !file_exists( $this->c->cookie_file ) )
		if ( file_put_contents( $this->c->cookie_file, "" ) === FALSE )
			throw new Exception("Could not write to cookie file, please check that the web server can write to ".dirname(__SCRIPT__));

	if ( file_exists( $this->c->cookie_file ) )
		if ( !is_writable( $this->c->cookie_file ) )
			throw new Exception("The cookie file is not writable, please check that the web server can write to ".dirname(__SCRIPT__));
    }

    /**
     * Logs in to the wiki
     * @return boolean true if logged in
     */
    public function login($username,$password) {

		//Logger::log("Logging in");

		$details = array(
			'action' => 'login',
			'lgname' => $username,
			'lgpassword' => $password,
			'format' => 'json'
		);

		// Send the login request
		$loginResult = $this->c->post( $this->api, $details )->body;

		// Check if we got an API result or the API doc page (invalid request)
		if ( strstr( $loginResult, "This is an auto-generated MediaWiki API documentation page" ) ) {
		        $this->error['login'] = "The API could not understand the first login request";
		        return false;
		}

		$loginResult = json_decode( $loginResult );

		if ( $this->debugMode ) {
			echo "Login request:\n";
			print_r( $details );
			echo "Login request response:\n";
			print_r( $loginResult );
		}

		if ( $loginResult->login->result == "NeedToken" ) {
			//Logger::log("Sending token {$loginResult->login->token}");
			$details['lgtoken'] = strtolower(trim($loginResult->login->token));

			// Send the confirm token request
			$loginResult = $this->c->post( $this->api, $details )->body;
			
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
				switch ($loginResult->login->result) {
			            case 'NotExists':
			                $this->error['login'] = 'The username does not exist';
			                break;
			            default:
			                $this->error['login'] = 'The API result was: '. $loginResult->login->result;
			                break;
			        }
				return false;
			}
		}

		//Logger::log("Logged in");
		return true;

    }

    /**
     * Sets the debug mode
     *
     * @param boolean $debugMode true to turn debugging on
     * @return Wikimate this object
     */
    public function setDebugMode( $b ) {
	$this->debugMode = $b;
	return $this;
    }

    /**
     * Either return or print the curl settings.
     *
     * @param boolean $echo True to echo the configuration
     * @return mixed Array of config if $echo is false, (boolean)true if echo is true
     */
    public function debugCurlConfig( $echo=false ) {
        if ( $echo ) {
            echo "Curl Configuration:\n";
            echo "<pre>",print_r($this->c->options,1),"</pre>";
            return true;
        }
        
        return $this->c->options;
    }        

    /**
     * Returns a WikiPage object populated with the page data
     * @param string $title The name of the wiki article
     * @return WikiPage the page object
     */
    public function getPage( $title ) {
		return new WikiPage( $title, $this );
    }

    /**
     * Performs a query to the wiki api with the given details
     * @param array $array array of details to be passed in the query
     * @return array unserialized php output from the wiki
     */
    public function query( $array ) {

		$array['action'] = 'query';
		$array['format'] = 'php';

		$apiResult = $this->c->get( $this->api, $array );

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

		$apiResult = $c->post( $this->api, $array );

		return unserialize($apiResult);
    }
    public function getError(){
    	return $this->error;
    }
}

/**
 * Models a wiki article page that can have its text altered and retrieved.
 * @author Robert McLeod
 * @since December 2010
 * @version 0.5
 */
class WikiPage {

    const SECTIONLIST_BY_INDEX = 1;
    const SECTIONLIST_BY_NAME = 2;
    const SECTIONLIST_BY_NUMBER = 3;

    private $title = null;
    private $exists = false;
    private $text = null;
    private $edittoken = null;
    private $starttimestamp = null;
    private $wikimate = null;
    private $error = null;
    private $invalid = false;
    private $sections = null;

    /*
     *
     * Magic methods
     *
     */

    /**
     * Constructs a WikiPage object from the title given and adds
     * a wikibot object
     * @param string $title name of the wiki article
     * @param WikiBot $wikibot WikiBot object
     */
    function __construct( $title, $wikimate ) {

		$this->wikimate = $wikimate;
		$this->title = $title;
		$this->text = $this->getText(true);
		
		if ( $this->invalid ) {
			echo "Invalid page title - cannot create WikiPage";
			return null;
		}

    }

    /**
     *
     * @return <type> Destructor
     */
    function  __destruct() {
		$this->title = null;
		$this->exists = false;
		$this->text = null;
		$this->edittoken = null;
		$this->starttimestamp = null;
		$this->wikimate = null;
		$this->error = null;
		$this->invalid = false;
		return null;
    }

    /**
     * Returns the wikicode of the page
     * @return string of wikicode
     */
    function __toString() {
		return $this->text;
    }

    /**
     * Returns an array sections with the section name as the key and the text
     * as the element e.g.
     *
     * array(
     *   'intro' => 'this text is the introduction',
     *   'History' => 'this is text under the history section'
     * )
     *
     * @return array of sections
     */
    function __invoke() {
		return $this->getAllSections( false, self::SECTIONLIST_BY_NAME );
    }

    /**
     * Returns the page existance status
     * @return boolean true if page exists
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
     * @return mixed null for no errors or an error array object
     */
    function getError() {
		return $this->error;
    }

    /**
     * Returns the title of this page
     * @return string the title of this page
     */
    function getTitle() {
		return $this->title;
    }

    /**
     * Returns the number of sections in this page
     * @return integer the number of sections in this page
     */
    function getNumSections() {
		return count( $this->sections->byIndex );
    }
	
	/**
	 * Returns the sections offsets and lengths
	 * @return StdClass section class
	 */
	function getSectionOffsets() {
		return $this->sections;
	}

    /*
     *
     * Getting functions
     *
     */

    /**
     * Gets the text of the page.  If refesh is true then this method will
     * query the wiki api again for the page details
     * @param boolean $refresh true to query the wiki api again
     * @return string the text of the page
     */
    function getText($refresh=false) {

		if ( $refresh ) { // we want to query the api

			$data = array(
			'prop' => 'info|revisions',
			'intoken' => 'edit',
			'titles' => $this->title,
			'rvprop' => 'content' // need to get page text
			);

			$r = $this->wikimate->query( $data ); // run the query

			// Check for errors
			if ( isset( $r['error'] ) ) {
				$this->error = $r; // set the error if there was one
			} else {
				$this->error = null; // reset the error status
			}

			$page = array_pop( $r['query']['pages'] ); // get the page (there should only be one)

			unset( $r, $data );

			if ( isset( $page['invalid'] ) ) {
				$this->invalid = true;
			}

			$this->edittoken = $page['edittoken'];
			$this->starttimestamp = $page['starttimestamp'];

			if ( !isset( $page['missing'] ) ) {
				$this->exists = true; // update the existance if the page is there
				$this->text = $page['revisions'][0]['*']; // put the content into text
			}

			unset( $page );

			// Now we need to get the section information
			preg_match_all('/((\r|\n)={1,5}.*={1,5}(\r|\n))/', $this->text, $m ); // TODO: improve regexp if possible
			
			// Set the intro section (between title and first section)
			$this->sections->byIndex[0]['offset'] = 0;
			$this->sections->byName['intro']['offset'] = 0;
			
			if ( !empty( $m[1] ) ) {
				
				// Array of section names
				$sections = $m[1];
				
				// Setup the current section
				$currIndex = 0;
				$currName = 'intro';
				
				foreach ( $sections as $i => $section ) {
					
					// Get the current offset
					$currOffset = strpos( $this->text, $section );
					
					// Are we still on the first section?
					if ( $currIndex == 0 ) {
						$this->sections->byIndex[$currIndex]['length'] = $currOffset;
						$this->sections->byName[$currName]['length'] = $currOffset;
					}
					
					// Get the current name and index
					$currName = trim(str_replace('=','',$section));
					$currIndex++;
					
					// Set the offset for the current section
					$this->sections->byIndex[$currIndex]['offset'] = $currOffset;
					$this->sections->byName[$currName]['offset'] = $currOffset;
				
					// If there is a section after this, set the length of this one
					if ( isset( $sections[$currIndex] ) ) {
						$nextOffset = strpos( $this->text, $sections[$currIndex] ); // get the offset of the next section
						$length = $nextOffset - $currOffset; // calculate the length of this one
						
						// Set the length of this section
						$this->sections->byIndex[$currIndex]['length'] = $length;
						$this->sections->byName[$currName]['length'] = $length;
					}
				
				}
			}
		}

		return $this->text; // return the text in any case

    }

    /**
     * Returns the section requested, section can be the following:
     * - section name (string:"History")
     * - section index (int:3)
     * 
     * @param mixed $section the section to get
     * @param boolan $includeHeading false to get section text only
     * @return string wikitext of the section on the page 
     */
    function getSection( $section, $includeHeading=false ) {
		// Check if we have a section name or index
		if ( is_int($section) ) {
			$coords = $this->sections->byIndex[$section];
		} else if( is_string($section) ) {
			$coords = $this->sections->byName[$section];
		}

		// Extract the text
		@extract( $coords );
		if ( isset( $length ) ) {
			$text = substr( $this->text, $offset, $length );
		} else {
			$text = substr( $this->text, $offset );
		}
		
		// Whack of the heading if need be
		if ( !$includeHeading && $offset > 0 ) {
			$text = substr( $text, strpos( trim($text), "\n" ) ); // chop off the first line
		}
		
		return $text;
	
    }

    /**
     * Return all the sections of the page in an array - the key names can be
     * set to name or index by using the following for the second param
     * - self::SECTIONLIST_BY_NAME
     * - self::SECTIONLIST_BY_INDEX
     *
     * @param boolean $includeHeading false to get section text only
     * @param integer $keyNames modifier for the array key names
     * @return array of sections
     */
    function getAllSections($includeHeading=false, $keyNames=self::SECTIONLIST_BY_INDEX ) {

		$sections = array();

		switch ( $keyNames ) {
			case self::SECTIONLIST_BY_INDEX:
				$array = array_keys( $this->sections->byIndex );
				break;
			case self::SECTIONLIST_BY_NAME:
				$array = array_keys( $this->sections->byName );
				break;
			default:
				throw new Exception('Unexpected parameter $keyNames given to WikiPage::getAllSections()');
				break;
		}

		foreach ( $array as $key ) {
			$sections[$key] = $this->getSection( $key, $includeHeading );
		}

		return $sections;

    }

    /*
     *
     * Setting functions
     *
     */

    /**
     * Sets the text in the page.  Updates the starttimestamp to the timestamp
     * after the page edit (if the edit is successful)
     * @param string $text the article text
     * @param string $section the section to edit (null for whole page)
     * @return boolean true if page was edited successfully
     */
    function setText( $text, $section=null, $minor=false, $summary=null ) {

		$data = array(
			'title' => $this->title,
			'text' => $text,
			'md5' => md5( $text ),
			'bot' => "true",
			'token' => $this->edittoken,
			'starttimestamp' => $this->starttimestamp
		);

		// set options from arguments
		if ( !is_null( $section ) ) $data['section'] = $section;
		if ( $minor ) $data['minor'] = $minor;
		if ( !is_null($summary) ) $data['summary'] = $summary;

		// Make sure we don't create a page by accident or overwrite another one
		if ( !$this->exists ) {
			$data['createonly'] = "true"; // createonly if not exists
		} else {
			$data['nocreate'] = "true"; // don't create, it should exist
		}

		$r = $this->wikimate->edit( $data ); // the edit query

		// Check if it worked
		if ( $r['edit']['result'] == "Success" ) {
			$this->exists = true;

			if ( is_null($section) ) {
			$this->text = $text;
			} else {

			}

			// Get the new starttimestamp
			$data = array(
			'prop' => 'info',
			'intoken' => 'edit',
			'titles' => $this->title,
			);

			$r = $this->wikimate->query( $data );

			$page = array_pop( $r['query']['pages'] ); // get the page

			$this->starttimestamp = $page['starttimestamp']; // update the starttimestamp

			$this->error = null; // reset the error
			return true;
		}

		$this->error = $r;
		return false;
    }

    /**
     * Sets the text of the given section.
     * Essentially an alias of WikiPage:setText() with the summary and minor
     * parameters switched.
     *
     * @param string $text The text of the section
     * @param mixed $section section index, new by default
     * @param string $summary summary text
     * @param boolean $minor true for minor edit
     * @return boolean true if the section was saved
     */
    function setSection( $text, $section=0, $summary=null, $minor=false ) {
		$this->setText( $text, $section, $minor, $summary );
    }

    /**
     * Alias of WikiPage::setSection() specifically for creating new sections
     *
     * @param string $name the heading name for the new section
     * @param string $text The text of the new section
     * @return boolean true if the section was saved
     */
    function newSection( $name, $text ) {
		return $this->setSection( $text, $section='new', $summary=$name, $minor=false);
    }

}
