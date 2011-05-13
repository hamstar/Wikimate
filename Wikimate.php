<?php

 /**
 * Provides an interface over wiki api objects such as pages
 * Logs into the wiki on construction
 *
 * @author Robert McLeod
 * @since December 2010
 * @version 0.5-oop
 */
class Wikimate {

    const SECTIONLIST_BY_NAME = 1;
    const SECTIONLIST_BY_INDEX = 2;

    /**
     * Creates a curl object and logs in
     * If it can't login the class will exit and return null
     */
    function __construct() {
		
		$wl = new WikiLogin;
		$wl->login();
    }

    /**
     * Returns a WikiPage object populated with the page data
     * @param string $title The name of the wiki article
     * @return WikiPage the page object
     */
    public function getPage( $title ) {
		return new WikiPage( $title, new WikiCurl );
    }

}

