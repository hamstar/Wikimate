<?php
/**
 * Provides an interface over wiki api objects such as pages
 * Logs into the wiki on construction
 *
 * @author Robert McLeod
 * @since December 2010
 * @version 0.2
 */

class Wikimate {

    private $c = null;

    /**
     * Creates a curl object and logs in
     * If it can't login the class will exit and return null
     */
    function __construct() {
	$this->c = new Curl();
	if ( !$this->login() ) {
	    echo "Failed to authenticate - cannot create Wikimate";
	    return null;
	}
    }

    /**
     * Logs in to the wiki
     * @return boolean true if logged in
     */
    private function login() {

	Logger::log("Logging in");

	$details = array(
		'action' => 'login',
		'lgname' => WIKI_USERNAME,
		'lgpassword' => WIKI_PASSWORD,
		'format' => 'json'
	);

	$loginResult = $this->c->post( WIKI_API, $details )->body;

	$loginResult = json_decode( $loginResult );

	if ( $loginResult->login->result == "NeedToken" ) {
	    Logger::log("Sending token {$loginResult->login->token}");
	    $details['lgtoken'] = $loginResult->login->token;
	    $loginResult = $this->c->post( WIKI_API, $details )->body;
	    $loginResult = json_decode( $loginResult );

	    if ( $loginResult->login->result != "Success" ) {
		return false;
	    }
	}

	Logger::log("Logged in");
	return true;

    }

    /**
     * Returns a WikiPage object populated with the page data
     * @param string $title The name of the wiki article
     * @return WikiPage the page object
     */
    function getPage( $title ) {
	return new WikiPage( $title, $this );
    }

    /**
     * Performs a query to the wiki api with the given details
     * @param array $array array of details to be passed in the query
     * @return array unserialized php output from the wiki
     */
    function query( $array ) {

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
    function edit( $array ) {
	$c = $this->c;
	$c->headers['Content-Type'] = "application/x-www-form-urlencoded";

	$array['action'] = 'edit';
	$array['format'] = 'php';

	$apiResult = $c->post( WIKI_API, $array );

	return unserialize($apiResult);
    }
}

/**
 * Models a wiki article page that can have its text altered and retrieved.
 * @author Robert McLeod
 * @since December 2010
 * @version 0.2
 */
class WikiPage {

    private $title = null;
    private $exists = false;
    private $text = null;
    private $edittoken = null;
    private $starttimestamp = null;
    private $wikimate = null;
    private $error = null;
    private $invalid = false;

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
     * Returns the page existance status
     * @return boolean true if page exists
     */
    function exists() {
	return $this->exists;
    }

    /**
     * Returns an error if there is one, null shows no error
     * @return mixed null for no errors or an error array object
     */
    function getError() {
	return $this->error;
    }

    function getTitle() {
	return $this->title;
    }

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

	    $page = array_pop( $r['query']['pages'] ); // get the page (there should only be one)

	    if ( isset( $page['invalid'] ) ) {
		$this->invalid = true;
	    }

	    $this->edittoken = $page['edittoken'];
	    $this->starttimestamp = $page['starttimestamp'];

	    if ( !isset( $page['missing'] ) ) {
		$this->exists = true; // update the existance if the page is there
		$this->text = $page['revisions'][0]['*']; // put the content into text
	    }

	    if ( isset( $r['error'] ) ) {
		$this->error = $r; // set the error if there was one
	    } else {
		$this->error = null; // reset the error status
	    }

	}

	return $this->text; // return the text in any case

    }

    /**
     * Sets the text in the page.  Updates the starttimestamp to the timestamp
     * after the page edit (if the edit is successful)
     * @param string $text the article text
     * @param string $section the section to edit (null for whole page)
     * @return boolean true if page was edited successfully
     */
    function setText( $text, $section=null ) {

	$data = array(
	    'title' => $this->title,
	    'text' => $text,
	    'md5' => md5( $text ),
	    'bot' => "true",
	    'token' => $this->edittoken,
	    'starttimestamp' => $this->starttimestamp
	);

	if ( !is_null( $section ) ) {
	    $data['section'] = $section;
	}

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
	    $this->text = $text;

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

}