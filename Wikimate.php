<?php
/**
 * Wikimate is a wrapper for the MediaWiki API that aims to be very easy to use.
 *
 * @package    Wikimate
 * @version    0.13.0
 * @copyright  SPDX-License-Identifier: MIT
 */

/**
 * Provides an interface over wiki API objects such as pages and files.
 *
 * @author  Robert McLeod & Frans P. de Vries
 * @since   0.2  December 2010
 */
class Wikimate
{
	/**
	 * The current version number (conforms to Semantic Versioning)
	 *
	 * @var string
	 * @link https://semver.org/
	 */
	const VERSION = '0.13.0';

	/**
	 * Identifier for CSRF token
	 *
	 * @var string
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Tokens
	 */
	const TOKEN_DEFAULT = 'csrf';

	/**
	 * Identifier for Login token
	 *
	 * @var string
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Tokens
	 */
	const TOKEN_LOGIN = 'login';

	/**
	 * Default lag value in seconds
	 *
	 * @var integer
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Maxlag_parameter
	 */
	const MAXLAG_DEFAULT = 5;

	/**
	 * Base URL for API requests
	 *
	 * @var string
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Main_page#Endpoint
	 */
	protected $api;

	/**
	 * Username for API requests
	 *
	 * @var string
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Login#Method_1._login
	 */
	protected $username;

	/**
	 * Password for API requests
	 *
	 * @var string
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Login#Method_1._login
	 */
	protected $password;

	/**
	 * Session object for HTTP requests
	 *
	 * @var Requests_Session
	 * @link https://requests.ryanmccue.info/
	 */
	protected $session;

	/**
	 * User agent string for Requests_Session
	 *
	 * @var string
	 * @link https://requests.ryanmccue.info/docs/usage-advanced.html#session-handling
	 */
	protected $useragent;

	/**
	 * Error array with API and Wikimate errors
	 *
	 * @var array|null
	 */
	protected $error = null;

	/**
	 * Whether to output debug logging
	 *
	 * @var boolean
	 */
	protected $debugMode = false;

	/**
	 * Maximum lag in seconds to accept in requests
	 *
	 * @var integer
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Maxlag_parameter
	 */
	protected $maxlag = self::MAXLAG_DEFAULT;

	/**
	 * Maximum number of retries for lagged requests (-1 = indefinitely)
	 *
	 * @var integer
	 */
	protected $maxretries = -1;

	/**
	 * Creates a new Wikimate object.
	 *
	 * @param   string    $api      Base URL for the API
	 * @param   array     $headers  Default headers for API requests
	 * @param   array     $data     Default data for API requests
	 * @param   array     $options  Default options for API requests
	 * @return  Wikimate
	 */
	public function __construct($api, $headers = array(), $data = array(), $options = array())
	{
		$this->api     = $api;
		$this->headers = $headers;
		$this->data    = $data;
		$this->options = $options;

		$this->initRequests();
	}

	/**
	 * Sets up a Requests_Session with appropriate user agent.
	 *
	 * @return  void
	 */
	protected function initRequests()
	{
		$this->useragent = 'Wikimate '.self::VERSION.' (https://github.com/hamstar/Wikimate)';

		$this->session = new Requests_Session($this->api, $this->headers, $this->data, $this->options);
		$this->session->useragent = $this->useragent;
	}

	/**
	 * Sends a GET or POST request in JSON format to the API.
	 *
	 * This method handles maxlag errors as advised at:
	 * {@see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Maxlag_parameter)
	 * The request is sent with the current maxlag value (default 5 seconds).
	 * If a lag error is received, the method waits (sleeps) for the
	 * recommended time (per the Retry-After header), then tries again.
	 * It will do this indefinitely unless the number of retries is limited,
	 * in which case an exception is thrown.
	 *
	 * The string type for $data is used only for upload POST requests,
	 * and must contain the complete multipart body, including maxlag.
	 *
	 * @param  array|string  $data     Data for the request
	 * @param  array         $headers  Optional extra headers to send with the request
	 * @param  boolean       $post     True to send a POST request, otherwise GET
	 * @return Requests_Response       The API response
	 * @throw  Exception               If lagged and ran out of retries
	 */
	private function request($data, $headers = array(), $post = false)
	{
		$retries = 0;

		// Add format & maxlag parameter to request
		if (is_array($data)) {
			$data['format'] = 'json';
			$data['maxlag'] = $this->getMaxlag();
		}

		// Send appropriate type of request, once or multiple times
		do {
			if ($post) {
				$response = $this->session->post($this->api, $headers, $data);
			} else {
				$response = $this->session->get($this->api.'?'.http_build_query($data), $headers);
			}

			// Check for replication lag error
			$server_lagged = ($response->headers->offsetGet('X-Database-Lag') !== null);
			if ($server_lagged) {
				// Determine recommended or default delay
				if ($response->headers->offsetGet('Retry-After') !== null) {
					$sleep = (int)$response->headers->offsetGet('Retry-After');
				} else {
					$sleep = $this->getMaxlag();
				}

				if ($this->debugMode) {
					preg_match('/Waiting for [^ ]*: ([0-9.-]+) seconds? lagged/', $response->body, $match);
					echo "Server lagged for {$match[1]} seconds; retry in {$sleep} seconds\n";
				}
				sleep($sleep);

				// Check retries limit
				if ($this->getMaxretries() >= 0) {
					$retries++;
				} else {
					$retries == -1; // continue indefinitely
				}
			}
		} while ($server_lagged && $retries <= $this->getMaxretries());

		// Throw exception if we ran out of retries
		if ($server_lagged) {
			throw new Exception("Server lagged ($retries consecutive maxlag responses)");
		} else {
			return $response;
		}
	}

	/**
	 * Obtains a wiki token for logging in or data-modifying actions.
	 * For now this method, in Wikimate tradition, is kept simple and supports
	 * only the two token types needed elsewhere in the library.  It also
	 * doesn't support the option to request multiple tokens at once.
	 * See {@see https://www.mediawiki.org/wiki/Special:MyLanguage/API:Tokens}
	 * for more information.
	 *
	 * @param   string  $type  The token type
	 * @return  string         The requested token
	 */
	protected function token($type = self::TOKEN_DEFAULT)
	{
		// Check for supported token types
		if ($type != self::TOKEN_DEFAULT && $type != self::TOKEN_LOGIN) {
			$this->error = array();
			$this->error['token'] = 'The API does not support the token type';
			return false;
		}

		$details = array(
			'action' => 'query',
			'meta' => 'tokens',
			'type' => $type,
		);

		// Send the token request
		$response = $this->request($details, array(), true);

		// Check if we got an API result or the API doc page (invalid request)
		if (strpos($response->body, "This is an auto-generated MediaWiki API documentation page") !== false) {
			$this->error = array();
			$this->error['token'] = 'The API could not understand the token request';
			return false;
		}

		$tokenResult = json_decode($response->body, true);
		// Check if we got a JSON result
		if ($tokenResult === null) {
			$this->error = array();
			$this->error['token'] = 'The API did not return the token response';
			return false;
		}

		if ($this->debugMode) {
			echo "Token request:\n";
			print_r($details);
			echo "Token response:\n";
			print_r($tokenResult);
		}

		if ($type == self::TOKEN_LOGIN) {
			return $tokenResult['query']['tokens']['logintoken'];
		} else {
			return $tokenResult['query']['tokens']['csrftoken'];
		}
	}

	/**
	 * Logs in to the wiki.
	 *
	 * @param   string   $username  The user name
	 * @param   string   $password  The user password
	 * @param   string   $domain    The domain (optional)
	 * @return  boolean             True if logged in
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Login#Method_1._login
	 */
	public function login($username, $password, $domain = null)
	{
		// Obtain login token first
		if (($logintoken = $this->token(self::TOKEN_LOGIN)) === false) {
			return false;
		}

		$details = array(
			'action' => 'login',
			'lgname' => $username,
			'lgpassword' => $password,
			'lgtoken' => $logintoken,
		);

		// If $domain is provided, set the corresponding detail in the request information array
		if (is_string($domain)) {
			$details['lgdomain'] = $domain;
		}

		// Send the login request
		$response = $this->request($details, array(), true);

		// Check if we got an API result or the API doc page (invalid request)
		if (strpos($response->body, "This is an auto-generated MediaWiki API documentation page") !== false) {
			$this->error = array();
			$this->error['login'] = 'The API could not understand the login request';
			return false;
		}

		$loginResult = json_decode($response->body, true);
		// Check if we got a JSON result
		if ($loginResult === null) {
			$this->error = array();
			$this->error['login'] = 'The API did not return the login response';
			return false;
		}

		if ($this->debugMode) {
			echo "Login request:\n";
			print_r($details);
			echo "Login response:\n";
			print_r($loginResult);
		}

		if (isset($loginResult['login']['result']) && $loginResult['login']['result'] != 'Success') {
			// Some more comprehensive error checking
			$this->error = array();
			switch ($loginResult['login']['result']) {
				case 'Failed':
					$this->error['login'] = 'Incorrect username or password';
					break;
				default:
					$this->error['login'] = 'The API result was: ' . $loginResult['login']['result'];
					break;
			}
			return false;
		}

		return true;
	}

	/**
	 * Gets the current value for the maxlag parameter.
	 *
	 * @return  integer  The maxlag value in seconds
	 */
	public function getMaxlag()
	{
		return $this->maxlag;
	}

	/**
	 * Sets the new value for the maxlag parameter.
	 *
	 * @param   integer   $ml  The new maxlag value in seconds
	 * @return  Wikimate       This object
	 */
	public function setMaxlag($ml)
	{
		$this->maxlag = (int)$ml;
		return $this;
	}

	/**
	 * Gets the current value for the max retries limit.
	 *
	 * @return  integer  The max retries limit
	 */
	public function getMaxretries()
	{
		return $this->maxretries;
	}

	/**
	 * Sets the new value for the max retries limit.
	 *
	 * @param   integer   $mr  The new max retries limit
	 * @return  Wikimate       This object
	 */
	public function setMaxretries($mr)
	{
		$this->maxretries = (int)$mr;
		return $this;
	}

	/**
	 * Gets the user agent for API requests.
	 *
	 * @return  string  The default user agent, or the current one defined
	 *                  by {@see Wikimate::setUserAgent()}
	 */
	public function getUserAgent()
	{
		return $this->useragent;
	}

	/**
	 * Sets the user agent for API requests.
	 *
	 * In order to use a custom user agent for all requests in the session,
	 * call this method before invoking {@see Wikimate::login()}.
	 *
	 * @param   string   $ua  The new user agent
	 * @return  Wikimate      This object
	 */
	public function setUserAgent($ua)
	{
		$this->useragent = (string)$ua;
		// Update the session
		$this->session->useragent = $this->useragent;
		return $this;
	}

	/**
	 * Sets the debug mode.
	 *
	 * @param   boolean   $b  True to turn debugging on
	 * @return  Wikimate      This object
	 */
	public function setDebugMode($b)
	{
		$this->debugMode = $b;
		return $this;
	}

	/**
	 * Used to return or print the curl settings, but now prints an error and
	 * returns Wikimate::debugRequestsConfig().
	 *
	 * @deprecated                  Since version 0.10.0
	 * @param       boolean  $echo  True to echo the configuration
	 * @return      mixed           Array of config if $echo is false, (boolean) true if echo is true
	 */
	public function debugCurlConfig($echo = false)
	{
		if ($echo) {
			echo "ERROR: Curl is no longer used by Wikimate.\n";
		}
		return $this->debugRequestsConfig($echo);
	}

	/**
	 * Gets or prints the Requests configuration.
	 *
	 * @param   boolean  $echo  Whether to echo the options
	 * @return  array           Options if $echo is false
	 * @return  boolean         True if options have been echoed to STDOUT
	 */
	public function debugRequestsConfig($echo = false)
	{
		if ($echo) {
			echo "<pre>Requests options:\n";
			print_r($this->session->options);
			echo "Requests headers:\n";
			print_r($this->session->headers);
			echo "</pre>";
			return true;
		}
		return $this->session->options;
	}

	/**
	 * Returns a WikiPage object populated with the page data.
	 *
	 * @param   string    $title  The name of the wiki article
	 * @return  WikiPage          The page object
	 */
	public function getPage($title)
	{
		return new WikiPage($title, $this);
	}

	/**
	 * Returns a WikiFile object populated with the file data.
	 *
	 * @param   string    $filename  The name of the wiki file
	 * @return  WikiFile             The file object
	 */
	public function getFile($filename)
	{
		return new WikiFile($filename, $this);
	}

	/**
	 * Performs a query to the wiki API with the given details.
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Decoded JSON output from the wiki API
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Query
	 */
	public function query($array)
	{
		$array['action'] = 'query';

		if ($this->debugMode) {
			echo "query GET parameters:\n";
			echo http_build_query($array) . "\n";
		}
		$apiResult = $this->request($array);

		if ($this->debugMode) {
			echo "query GET response:\n";
			print_r(json_decode($apiResult->body, true));
		}
		return json_decode($apiResult->body, true);
	}

	/**
	 * Performs a parse query to the wiki API.
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Decoded JSON output from the wiki API
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Parsing_wikitext
	 */
	public function parse($array)
	{
		$array['action'] = 'parse';

		if ($this->debugMode) {
			echo "parse GET parameters:\n";
			echo http_build_query($array) . "\n";
		}
		$apiResult = $this->request($array);

		if ($this->debugMode) {
			echo "parse GET response:\n";
			print_r(json_decode($apiResult->body, true));
		}
		return json_decode($apiResult->body, true);
	}

	/**
	 * Perfoms an edit query to the wiki API.
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Decoded JSON output from the wiki API
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Edit
	 */
	public function edit($array)
	{
		// Obtain default token first
		if (($edittoken = $this->token()) === false) {
			return false;
		}

		$headers = array(
			'Content-Type' => "application/x-www-form-urlencoded"
		);

		$array['action'] = 'edit';
		$array['token'] = $edittoken;

		if ($this->debugMode) {
			echo "edit POST parameters:\n";
			print_r($array);
		}
		$apiResult = $this->request($array, $headers, true);

		if ($this->debugMode) {
			echo "edit POST response:\n";
			print_r(json_decode($apiResult->body, true));
		}
		return json_decode($apiResult->body, true);
	}

	/**
	 * Perfoms a delete query to the wiki API.
	 *
	 * @param   array  $array  Array of details to be passed in the query
	 * @return  array          Decoded JSON output from the wiki API
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Delete
	 */
	public function delete($array)
	{
		// Obtain default token first
		if (($deletetoken = $this->token()) === false) {
			return false;
		}

		$headers = array(
			'Content-Type' => "application/x-www-form-urlencoded"
		);

		$array['action'] = 'delete';
		$array['token'] = $deletetoken;

		if ($this->debugMode) {
			echo "delete POST parameters:\n";
			print_r($array);
		}
		$apiResult = $this->request($array, $headers, true);

		if ($this->debugMode) {
			echo "delete POST response:\n";
			print_r(json_decode($apiResult->body, true));
		}
		return json_decode($apiResult->body, true);
	}

	/**
	 * Downloads data from the given URL.
	 *
	 * @param   string  $url  The URL to download from
	 * @return  mixed         The downloaded data (string), or null if error
	 */
	public function download($url)
	{
		$getResult = $this->session->get($url);

		if (!$getResult->success) {
			if ($this->debugMode) {
				echo "download GET response:\n";
				print_r($getResult);
			}
			$this->error = array();
			$this->error['file'] = 'Download error (HTTP status: ' . $getResult->status_code . ')';
			$this->error['http'] = $getResult->status_code;
			return null;
		}
		return $getResult->body;
	}

	/**
	 * Uploads a file to the wiki API.
	 *
	 * @param   array    $array  Array of details to be used in the upload
	 * @return  array            Decoded JSON output from the wiki API
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Upload
	 */
	public function upload($array)
	{
		// Obtain default token first
		if (($uploadtoken = $this->token()) === false) {
			return false;
		}

		$array['action'] = 'upload';
		$array['format'] = 'json';
		$array['maxlag'] = $this->getMaxlag();
		$array['token'] = $uploadtoken;

		// Construct multipart body:
		// https://www.mediawiki.org/w/index.php?title=API:Upload&oldid=2293685#Sample_Raw_Upload
		// https://www.mediawiki.org/w/index.php?title=API:Upload&oldid=2339771#Sample_Raw_POST_of_a_single_chunk
		$boundary = '---Wikimate-' . md5(microtime());
		$body = '';
		foreach ($array as $fieldName => $fieldData) {
			$body .= "--{$boundary}\r\n";
			$body .= 'Content-Disposition: form-data; name="' . $fieldName . '"';
			// Process the (binary) file
			if ($fieldName == 'file') {
				$body .= '; filename="' . $array['filename'] . '"' . "\r\n";
				$body .= "Content-Type: application/octet-stream; charset=UTF-8\r\n";
				$body .= "Content-Transfer-Encoding: binary\r\n";
			// Process text parameters
			} else {
				$body .= "\r\n";
				$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
				$body .= "Content-Transfer-Encoding: 8bit\r\n";
			}
			$body .= "\r\n{$fieldData}\r\n";
		}
		$body .= "--{$boundary}--\r\n";

		// Construct multipart headers
		$headers = array(
			'Content-Type' => "multipart/form-data; boundary={$boundary}",
			'Content-Length' => strlen($body),
		);

		$apiResult = $this->request($body, $headers, true);

		if ($this->debugMode) {
			echo "upload POST response:\n";
			print_r(json_decode($apiResult->body, true));
		}
		return json_decode($apiResult->body, true);
	}

	/**
	 * Returns the latest error if there is one.
	 *
	 * @return  mixed  The error array, or null if no error
	 */
	public function getError()
	{
		return $this->error;
	}
}


/**
 * Models a wiki article page that can have its text altered and retrieved.
 *
 * @author  Robert McLeod & Frans P. de Vries
 * @since   0.2  December 2010
 */
class WikiPage
{
	/**
	 * Use section indexes as keys in return array of {@see WikiPage::getAllSections()}
	 *
	 * @var integer
	 */
	const SECTIONLIST_BY_INDEX = 1;

	/**
	 * Use section names as keys in return array of {@see WikiPage::getAllSections()}
	 *
	 * @var integer
	 */
	const SECTIONLIST_BY_NAME = 2;

	/**
	 * The title of the page
	 *
	 * @var string|null
	 */
	protected $title = null;

	/**
	 * Wikimate object for API requests
	 *
	 * @var Wikimate|null
	 */
	protected $wikimate = null;

	/**
	 * Whether the page exists
	 *
	 * @var boolean
	 */
	protected $exists = false;

	/**
	 * Whether the page is invalid
	 *
	 * @var boolean
	 */
	protected $invalid = false;

	/**
	 * Error array with API and WikiPage errors
	 *
	 * @var array|null
	 */
	protected $error = null;

	/**
	 * Stores the timestamp for detection of edit conflicts
	 *
	 * @var integer|null
	 */
	protected $starttimestamp = null;

	/**
	 * The complete text of the page
	 *
	 * @var string|null
	 */
	protected $text = null;

	/**
	 * The sections object for the page
	 *
	 * @var stdClass|null
	 */
	protected $sections = null;

	/*
	 *
	 * Magic methods
	 *
	 */

	/**
	 * Constructs a WikiPage object from the title given
	 * and associate with the passed Wikimate object.
	 *
	 * @param  string    $title     Name of the wiki article
	 * @param  Wikimate  $wikimate  Wikimate object
	 */
	public function __construct($title, $wikimate)
	{
		$this->wikimate = $wikimate;
		$this->title    = $title;
		$this->text     = $this->getText(true);

		if ($this->invalid) {
			$this->error['page'] = 'Invalid page title - cannot create WikiPage';
		}
	}

	/**
	 * Forgets all object properties.
	 */
	public function __destruct()
	{
		$this->title          = null;
		$this->wikimate       = null;
		$this->exists         = false;
		$this->invalid        = false;
		$this->error          = null;
		$this->starttimestamp = null;
		$this->text           = null;
		$this->sections       = null;
	}

	/**
	 * Returns the wikicode of the page.
	 *
	 * @return  string  String of wikicode
	 */
	public function __toString()
	{
		return $this->text;
	}

	/**
	 * Returns an array sections with the section name as the key
	 * and the text as the element, e.g.
	 *
	 * array(
	 *   'intro' => 'this text is the introduction',
	 *   'History' => 'this is text under the history section'
	 *)
	 *
	 * @return  array  Array of sections
	 */
	public function __invoke()
	{
		return $this->getAllSections(false, self::SECTIONLIST_BY_NAME);
	}

	/**
	 * Returns the page existence status.
	 *
	 * @return  boolean  True if page exists
	 */
	public function exists()
	{
		return $this->exists;
	}

	/**
	 * Alias of self::__destruct().
	 */
	public function destroy()
	{
		$this->__destruct();
	}

	/*
	 *
	 * Page meta methods
	 *
	 */

	/**
	 * Returns the latest error if there is one.
	 *
	 * @return  mixed  The error array, or null if no error
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Returns the title of this page.
	 *
	 * @return  string  The title of this page
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Returns the number of sections in this page.
	 *
	 * @return  integer  The number of sections in this page
	 */
	public function getNumSections()
	{
		return count($this->sections->byIndex);
	}

	/**
	 * Returns the sections offsets and lengths.
	 *
	 * @return  stdClass  Section class
	 */
	public function getSectionOffsets()
	{
		return $this->sections;
	}

	/*
	 *
	 * Getter methods
	 *
	 */

	/**
	 * Gets the text of the page. If refresh is true,
	 * then this method will query the wiki API again for the page details.
	 *
	 * @param   boolean  $refresh  True to query the wiki API again
	 * @return  mixed              The text of the page (string), or null if error
	 */
	public function getText($refresh = false)
	{
		if ($refresh) { // We want to query the API
			// Specify relevant page properties to retrieve
			$data = array(
				'titles' => $this->title,
				'prop' => 'info|revisions',
				'rvprop' => 'content', // Need to get page text
				'curtimestamp' => 1,
			);

			$r = $this->wikimate->query($data); // Run the query

			// Check for errors
			if (isset($r['error'])) {
				$this->error = $r['error']; // Set the error if there was one
				return null;
			} else {
				$this->error = null; // Reset the error status
			}

			// Get the page (there should only be one)
			$page = array_pop($r['query']['pages']);

			// Abort if invalid page title
			if (isset($page['invalid'])) {
				$this->invalid = true;
				return null;
			}

			$this->starttimestamp = $r['curtimestamp'];
			unset($r, $data);

			if (!isset($page['missing'])) {
				// Update the existence if the page is there
				$this->exists = true;
				// Put the content into text
				$this->text   = $page['revisions'][0]['*'];
			}
			unset($page);

			// Now we need to get the section headers, if any
			preg_match_all('/(={1,6}).*?\1 *(?:\n|$)/', $this->text, $matches);

			// Set the intro section (between title and first section)
			$this->sections->byIndex[0]['offset']      = 0;
			$this->sections->byName['intro']['offset'] = 0;

			// Check for section header matches
			if (empty($matches[0])) {
				// Define lengths for page consisting only of intro section
				$this->sections->byIndex[0]['length']      = strlen($this->text);
				$this->sections->byName['intro']['length'] = strlen($this->text);
			} else {
				// Array of section header matches
				$sections = $matches[0];

				// Set up the current section
				$currIndex = 0;
				$currName  = 'intro';

				// Collect offsets and lengths from section header matches
				foreach ($sections as $section) {
					// Get the current offset
					$currOffset = strpos($this->text, $section, $this->sections->byIndex[$currIndex]['offset']);

					// Are we still on the first section?
					if ($currIndex == 0) {
						$this->sections->byIndex[$currIndex]['length'] = $currOffset;
						$this->sections->byIndex[$currIndex]['depth']  = 0;
						$this->sections->byName[$currName]['length']   = $currOffset;
						$this->sections->byName[$currName]['depth']    = 0;
					}

					// Get the current name and index
					$currName = trim(str_replace('=', '', $section));
					$currIndex++;

					// Search for existing name and create unique one
					$cName = $currName;
					for ($seq = 2; array_key_exists($cName, $this->sections->byName); $seq++) {
						$cName = $currName . '_' . $seq;
					}
					if ($seq > 2) {
						$currName = $cName;
					}

					// Set the offset and depth (from the matched ='s) for the current section
					$this->sections->byIndex[$currIndex]['offset'] = $currOffset;
					$this->sections->byIndex[$currIndex]['depth']  = strlen($matches[1][$currIndex-1]);
					$this->sections->byName[$currName]['offset']   = $currOffset;
					$this->sections->byName[$currName]['depth']    = strlen($matches[1][$currIndex-1]);

					// If there is a section after this, set the length of this one
					if (isset($sections[$currIndex])) {
						// Get the offset of the next section
						$nextOffset = strpos($this->text, $sections[$currIndex], $currOffset);
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
	 * Returns the requested section, with its subsections, if any.
	 *
	 * Section can be the following:
	 * - section name (string, e.g. "History")
	 * - section index (int, e.g. 3)
	 *
	 * @param   mixed    $section             The section to get
	 * @param   boolean  $includeHeading      False to get section text only,
	 *                                        true to include heading too
	 * @param   boolean  $includeSubsections  False to get section text only,
	 *                                        true to include subsections too
	 * @return  string                        Wikitext of the section on the page,
	 *                                        or false if section is undefined
	 */
	public function getSection($section, $includeHeading = false, $includeSubsections = true)
	{
		// Check if we have a section name or index
		if (is_int($section)) {
			if (!isset($this->sections->byIndex[$section])) {
				return false;
			}
			$coords = $this->sections->byIndex[$section];
		} else if (is_string($section)) {
			if (!isset($this->sections->byName[$section])) {
				return false;
			}
			$coords = $this->sections->byName[$section];
		}

		// Extract the offset, depth and (initial) length
		@extract($coords);
		// Find subsections if requested, and not the intro
		if ($includeSubsections && $offset > 0) {
			$found = false;
			foreach ($this->sections->byName as $section) {
				if ($found) {
					// Include length of this subsection
					if ($depth < $section['depth']) {
						$length += $section['length'];
					// Done if not a subsection
					} else {
						break;
					}
				} else {
					// Found our section if same offset
					if ($offset == $section['offset']) {
						$found = true;
					}
				}
			}
		}
		// Extract text of section, and its subsections if requested
		$text = substr($this->text, $offset, $length);

		// Whack off the heading if requested, and not the intro
		if (!$includeHeading && $offset > 0) {
			// Chop off the first line
			$text = substr($text, strpos($text, "\n"));
		}

		return $text;
	}

	/**
	 * Returns all the sections of the page in an array - the key names can be
	 * set to name or index by using the following for the second param:
	 * - self::SECTIONLIST_BY_NAME
	 * - self::SECTIONLIST_BY_INDEX
	 *
	 * @param   boolean  $includeHeading  False to get section text only
	 * @param   integer  $keyNames        Modifier for the array key names
	 * @return  array                     Array of sections
	 * @throw   Exception                 If $keyNames is not a supported constant
	 */
	public function getAllSections($includeHeading = false, $keyNames = self::SECTIONLIST_BY_INDEX)
	{
		$sections = array();

		switch ($keyNames) {
			case self::SECTIONLIST_BY_INDEX:
				$array = array_keys($this->sections->byIndex);
				break;
			case self::SECTIONLIST_BY_NAME:
				$array = array_keys($this->sections->byName);
				break;
			default:
				throw new Exception('Unexpected parameter $keyNames given to WikiPage::getAllSections()');
				break;
		}

		foreach ($array as $key) {
			$sections[$key] = $this->getSection($key, $includeHeading);
		}

		return $sections;
	}

	/*
	 *
	 * Setter methods
	 *
	 */

	/**
	 * Sets the text in the page.  Updates the starttimestamp to the timestamp
	 * after the page edit (if the edit is successful).
	 *
	 * Section can be the following:
	 * - section name (string, e.g. "History")
	 * - section index (int, e.g. 3)
	 * - a new section (the string "new")
	 * - the whole page (null)
	 *
	 * @param   string   $text     The article text
	 * @param   string   $section  The section to edit (whole page by default)
	 * @param   boolean  $minor    True for minor edit
	 * @param   string   $summary  Summary text, and section header in case
	 *                             of new section
	 * @return  boolean            True if page was edited successfully
	 */
	public function setText($text, $section = null, $minor = false, $summary = null)
	{
		$data = array(
			'title' => $this->title,
			'text' => $text,
			'md5' => md5($text),
			'bot' => "true",
			'starttimestamp' => $this->starttimestamp,
		);

		// Set options from arguments
		if (!is_null($section)) {
			// Obtain section index in case it is a name
			$data['section'] = $this->findSection($section);
			if ($data['section'] == -1) {
				return false;
			}
		}
		if ($minor) {
			$data['minor'] = $minor;
		}
		if (!is_null($summary)) {
			$data['summary'] = $summary;
		}

		// Make sure we don't create a page by accident or overwrite another one
		if (!$this->exists) {
			$data['createonly'] = "true"; // createonly if not exists
		} else {
			$data['nocreate'] = "true"; // Don't create, it should exist
		}

		$r = $this->wikimate->edit($data); // The edit query

		// Check if it worked
		if (isset($r['edit']['result']) && $r['edit']['result'] == 'Success') {
			$this->exists = true;

			if (is_null($section)) {
				$this->text = $text;
			}

			// Get the new starttimestamp
			$data = array(
				'titles' => $this->title,
				'prop' => 'info',
				'curtimestamp' => 1,
			);

			$r = $this->wikimate->query($data);

			// Check for errors
			if (isset($r['error'])) {
				$this->error = $r['error']; // Set the error if there was one
				return null;
			} else {
				$this->error = null; // Reset the error status
			}

			$this->starttimestamp = $r['curtimestamp']; // Update the starttimestamp
			return true;
		}

		// Return error response
		if (isset($r['error'])) {
			$this->error = $r['error'];
		} else {
			$this->error = array();
			$this->error['page'] = 'Unexpected edit response: '.$r['edit']['result'];
		}
		return false;
	}

	/**
	 * Sets the text of the given section.
	 * Essentially an alias of WikiPage:setText()
	 * with the summary and minor parameters switched.
	 *
	 * Section can be the following:
	 * - section name (string, e.g. "History")
	 * - section index (int, e.g. 3)
	 * - a new section (the string "new")
	 * - the whole page (null)
	 *
	 * @param   string   $text     The text of the section
	 * @param   mixed    $section  The section to edit (intro by default)
	 * @param   string   $summary  Summary text, and section header in case
	 *                             of new section
	 * @param   boolean  $minor    True for minor edit
	 * @return  boolean            True if the section was saved
	 */
	public function setSection($text, $section = 0, $summary = null, $minor = false)
	{
		return $this->setText($text, $section, $minor, $summary);
	}

	/**
	 * Alias of WikiPage::setSection() specifically for creating new sections.
	 *
	 * @param   string   $name  The heading name for the new section
	 * @param   string   $text  The text of the new section
	 * @return  boolean         True if the section was saved
	 */
	public function newSection($name, $text)
	{
		return $this->setSection($text, $section = 'new', $summary = $name, $minor = false);
	}

	/**
	 * Deletes the page.
	 *
	 * @param   string   $reason  Reason for the deletion
	 * @return  boolean           True if page was deleted successfully
	 */
	public function delete($reason = null)
	{
		$data = array(
			'title' => $this->title,
		);

		// Set options from arguments
		if (!is_null($reason)) {
			$data['reason'] = $reason;
		}

		$r = $this->wikimate->delete($data); // The delete query

		// Check if it worked
		if (isset($r['delete'])) {
			$this->exists = false; // The page was deleted

			$this->error = null; // Reset the error status
			return true;
		}

		$this->error = $r['error']; // Return error response
		return false;
	}

	/*
	 *
	 * Private methods
	 *
	 */

	/**
	 * Finds a section's index by name.
	 * If a section index or 'new' is passed, it is returned directly.
	 *
	 * @param   mixed  $section  The section name or index to find
	 * @return  mixed            The section index, or -1 if not found
	 */
	private function findSection($section)
	{
		// Check section type
		if (is_int($section) || $section === 'new') {
			return $section;
		} else if (is_string($section)) {
			// Search section names for related index
			$sections = array_keys($this->sections->byName);
			$index = array_search($section, $sections);

			// Return index if found
			if ($index !== false) {
				return $index;
			}
		}

		// Return error message and value
		$this->error = array();
		$this->error['page'] = "Section '$section' was not found on this page";
		return -1;
	}
}


/**
 * Models a wiki file that can have its properties retrieved and
 * its contents downloaded and uploaded.
 * All properties pertain to the current revision of the file.
 *
 * @author  Robert McLeod & Frans P. de Vries
 * @since   0.12.0  October 2016
 */
class WikiFile
{
	/**
	 * The name of the file
	 *
	 * @var string|null
	 */
	protected $filename = null;

	/**
	 * Wikimate object for API requests
	 *
	 * @var Wikimate|null
	 */
	protected $wikimate = null;

	/**
	 * Whether the file exists
	 *
	 * @var boolean
	 */
	protected $exists = false;

	/**
	 * Whether the file is invalid
	 *
	 * @var boolean
	 */
	protected $invalid = false;

	/**
	 * Error array with API and WikiFile errors
	 *
	 * @var array|null
	 */
	protected $error = null;

	/**
	 * Image info for the current file revision
	 *
	 * @var array|null
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Imageinfo
	 */
	protected $info = null;

	/**
	 * Image info for all file revisions
	 *
	 * @var array|null
	 * @link https://www.mediawiki.org/wiki/Special:MyLanguage/API:Imageinfo
	 */
	protected $history = null;

	/*
	 *
	 * Magic methods
	 *
	 */

	/**
	 * Constructs a WikiFile object from the filename given
	 * and associate with the passed Wikimate object.
	 *
	 * @param  string    $filename  Name of the wiki file
	 * @param  Wikimate  $wikimate  Wikimate object
	 */
	public function __construct($filename, $wikimate)
	{
		$this->wikimate = $wikimate;
		$this->filename = $filename;
		$this->info     = $this->getInfo(true);

		if ($this->invalid) {
			$this->error['file'] = 'Invalid filename - cannot create WikiFile';
		}
	}

	/**
	 * Forgets all object properties.
	 */
	public function __destruct()
	{
		$this->filename = null;
		$this->wikimate = null;
		$this->exists   = false;
		$this->invalid  = false;
		$this->error    = null;
		$this->info     = null;
		$this->history  = null;
	}

	/**
	 * Returns the file existence status.
	 *
	 * @return  boolean  True if file exists
	 */
	public function exists()
	{
		return $this->exists;
	}

	/**
	 * Alias of self::__destruct().
	 */
	public function destroy()
	{
		$this->__destruct();
	}

	/*
	 *
	 * File meta methods
	 *
	 */

	/**
	 * Returns the latest error if there is one.
	 *
	 * @return  mixed  The error array, or null if no error
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Returns the name of this file.
	 *
	 * @return  string  The name of this file
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/*
	 *
	 * Getter methods
	 *
	 */

	/**
	 * Gets the information of the file. If refresh is true,
	 * then this method will query the wiki API again for the file details.
	 *
	 * @param   boolean  $refresh  True to query the wiki API again
	 * @param   array    $history  An optional array of revision history parameters
	 * @return  mixed              The info of the file (array), or null if error
	 */
	public function getInfo($refresh = false, $history = null)
	{
		if ($refresh) { // We want to query the API
			// Specify relevant file properties to retrieve
			$data = array(
				'titles' => 'File:' . $this->filename,
				'prop' => 'info|imageinfo',
				'iiprop' => 'bitdepth|canonicaltitle|comment|parsedcomment|'
				          . 'commonmetadata|metadata|extmetadata|mediatype|'
				          . 'mime|thumbmime|sha1|size|timestamp|url|user|userid',
			);
			// Add optional history parameters
			if (is_array($history)) {
				foreach ($history as $key => $val) {
					$data[$key] = $val;
				}
				// Retrieve archive name property as well
				$data['iiprop'] .= '|archivename';
			}

			$r = $this->wikimate->query($data); // Run the query

			// Check for errors
			if (isset($r['error'])) {
				$this->error = $r['error']; // Set the error if there was one
				return null;
			} else {
				$this->error = null; // Reset the error status
			}

			// Get the page (there should only be one)
			$page = array_pop($r['query']['pages']);
			unset($r, $data);

			// Abort if invalid file title
			if (isset($page['invalid'])) {
				$this->invalid = true;
				return null;
			}

			// Check that file is present and has info
			if (!isset($page['missing']) && isset($page['imageinfo'])) {
				// Update the existence if the file is there
				$this->exists = true;
				// Put the content into info & history
				$this->info    = $page['imageinfo'][0];
				$this->history = $page['imageinfo'];
			}
			unset($page);
		}

		return $this->info; // Return the info in any case
	}

	/**
	 * Returns the anonymous flag of this file,
	 * or of its specified revision.
	 * If true, then getUser()'s value represents an anonymous IP address.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The anonymous flag of this file (boolean),
	 *                            or null if revision not found
	 */
	public function getAnon($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			// Check for anon flag
			return isset($this->info['anon']) ? true : false;
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		// Check for anon flag
		return isset($info['anon']) ? true : false;
	}

	/**
	 * Returns the aspect ratio of this image,
	 * or of its specified revision.
	 * Returns 0 if file is not an image (and thus has no dimensions).
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  float             The aspect ratio of this image, or 0 if no dimensions,
	 *                            or -1 if revision not found
	 */
	public function getAspectRatio($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			// Check for dimensions
			if ($this->info['height'] > 0) {
				return $this->info['width'] / $this->info['height'];
			} else {
				return 0;
			}
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return -1;
		}

		// Check for dimensions
		if (isset($info['height'])) {
			return $info['width'] / $info['height'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns the bit depth of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed    $revision  The index or timestamp of the revision (optional)
	 * @return  integer             The bit depth of this file,
	 *                              or -1 if revision not found
	 */
	public function getBitDepth($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return (int)$this->info['bitdepth'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return -1;
		}

		return (int)$info['bitdepth'];
	}

	/**
	 * Returns the canonical title of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The canonical title of this file (string),
	 *                            or null if revision not found
	 */
	public function getCanonicalTitle($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['canonicaltitle'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['canonicaltitle'];
	}

	/**
	 * Returns the edit comment of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The edit comment of this file (string),
	 *                            or null if revision not found
	 */
	public function getComment($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['comment'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['comment'];
	}

	/**
	 * Returns the common metadata of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The common metadata of this file (array),
	 *                            or null if revision not found
	 */
	public function getCommonMetadata($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['commonmetadata'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['commonmetadata'];
	}

	/**
	 * Returns the description URL of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The description URL of this file (string),
	 *                            or null if revision not found
	 */
	public function getDescriptionUrl($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['descriptionurl'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['descriptionurl'];
	}

	/**
	 * Returns the extended metadata of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The extended metadata of this file (array),
	 *                            or null if revision not found
	 */
	public function getExtendedMetadata($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['extmetadata'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['extmetadata'];
	}

	/**
	 * Returns the height of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed    $revision  The index or timestamp of the revision (optional)
	 * @return  integer             The height of this file, or -1 if revision not found
	 */
	public function getHeight($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return (int)$this->info['height'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return -1;
		}

		return (int)$info['height'];
	}

	/**
	 * Returns the media type of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The media type of this file (string),
	 *                            or null if revision not found
	 */
	public function getMediaType($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['mediatype'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['mediatype'];
	}

	/**
	 * Returns the Exif metadata of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The metadata of this file (array),
	 *                            or null if revision not found
	 */
	public function getMetadata($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['metadata'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['metadata'];
	}

	/**
	 * Returns the MIME type of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The MIME type of this file (string),
	 *                            or null if revision not found
	 */
	public function getMime($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['mime'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['mime'];
	}

	/**
	 * Returns the parsed edit comment of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The parsed edit comment of this file (string),
	 *                            or null if revision not found
	 */
	public function getParsedComment($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['parsedcomment'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['parsedcomment'];
	}

	/**
	 * Returns the SHA-1 hash of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The SHA-1 hash of this file (string),
	 *                            or null if revision not found
	 */
	public function getSha1($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['sha1'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['sha1'];
	}

	/**
	 * Returns the size of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed    $revision  The index or timestamp of the revision (optional)
	 * @return  integer             The size of this file, or -1 if revision not found
	 */
	public function getSize($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return (int)$this->info['size'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return -1;
		}

		return (int)$info['size'];
	}

	/**
	 * Returns the MIME type of this file's thumbnail,
	 * or of its specified revision.
	 * Returns empty string if property not available for this file type.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The MIME type of this file's thumbnail (string),
	 *                            or '' if unavailable, or null if revision not found
	 */
	public function getThumbMime($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return (isset($this->info['thumbmime']) ? $this->info['thumbmime'] : '');
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		// Check for thumbnail MIME type
		return (isset($info['thumbmime']) ? $info['thumbmime'] : '');
	}

	/**
	 * Returns the timestamp of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The timestamp of this file (string),
	 *                            or null if revision not found
	 */
	public function getTimestamp($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['timestamp'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['timestamp'];
	}

	/**
	 * Returns the URL of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The URL of this file (string),
	 *                            or null if revision not found
	 */
	public function getUrl($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['url'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['url'];
	}

	/**
	 * Returns the user who uploaded this file,
	 * or of its specified revision.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision (optional)
	 * @return  mixed             The user of this file (string),
	 *                            or null if revision not found
	 */
	public function getUser($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return $this->info['user'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		return $info['user'];
	}

	/**
	 * Returns the ID of the user who uploaded this file,
	 * or of its specified revision.
	 *
	 * @param   mixed    $revision  The index or timestamp of the revision (optional)
	 * @return  integer             The user ID of this file,
	 *                              or -1 if revision not found
	 */
	public function getUserId($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return (int)$this->info['userid'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return -1;
		}

		return (int)$info['userid'];
	}

	/**
	 * Returns the width of this file,
	 * or of its specified revision.
	 *
	 * @param   mixed    $revision  The index or timestamp of the revision (optional)
	 * @return  integer             The width of this file, or -1 if revision not found
	 */
	public function getWidth($revision = null)
	{
		// Without revision, use current info
		if (!isset($revision)) {
			return (int)$this->info['width'];
		}

		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return -1;
		}

		return (int)$info['width'];
	}

	/*
	 *
	 * File history & deletion methods
	 *
	 */

	/**
	 * Returns the revision history of this file with all properties.
	 * The initial history at object creation contains only the
	 * current revision of the file. To obtain more revisions,
	 * set $refresh to true and also optionally set $limit and
	 * the timestamps.
	 *
	 * The maximum limit is 500 for user accounts and 5000 for bot accounts.
	 *
	 * Timestamps can be in several formats as described here:
	 * {@see https://www.mediawiki.org/w/api.php?action=help&modules=main#main.2Fdatatypes}
	 *
	 * @param   boolean  $refresh  True to query the wiki API again
	 * @param   integer  $limit    The number of file revisions to return
	 *                             (the maximum number by default)
	 * @param   string   $startts  The start timestamp of the listing (optional)
	 * @param   string   $endts    The end timestamp of the listing (optional)
	 * @return  mixed              The array of selected file revisions, or null if error
	 */
	public function getHistory($refresh = false, $limit = null, $startts = null, $endts = null)
	{
		if ($refresh) { // We want to query the API
			// Collect optional history parameters
			$history = array();
			if (!is_null($limit)) {
				$history['iilimit'] = $limit;
			} else {
				$history['iilimit'] = 'max';
			}
			if (!is_null($startts)) {
				$history['iistart'] = $startts;
			}
			if (!is_null($endts)) {
				$history['iiend'] = $endts;
			}

			// Get file revision history
			if ($this->getInfo($refresh, $history) === null) {
				return null;
			}
		}

		return $this->history;
	}

	/**
	 * Returns the properties of the specified file revision.
	 *
	 * Revision can be the following:
	 * - revision timestamp (string, e.g. "2001-01-15T14:56:00Z")
	 * - revision index (int, e.g. 3)
	 * The most recent revision has index 0,
	 * and it increments towards older revisions.
	 * A timestamp must be in ISO 8601 format.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision
	 * @return  mixed             The properties (array), or null if not found
	 */
	public function getRevision($revision)
	{
		// Select revision by index
		if (is_int($revision)) {
			if (isset($this->history[$revision])) {
				return $this->history[$revision];
			}
		// Search revision by timestamp
		} else {
			foreach ($this->history as $history) {
				if ($history['timestamp'] == $revision) {
					return $history;
				}
			}
		}

		// Return error message
		$this->error = array();
		$this->error['file'] = "Revision '$revision' was not found for this file";
		return null;
	}

	/**
	 * Returns the archive name of the specified file revision.
	 *
	 * Revision can be the following:
	 * - revision timestamp (string, e.g. "2001-01-15T14:56:00Z")
	 * - revision index (int, e.g. 3)
	 * The most recent revision has index 0,
	 * and it increments towards older revisions.
	 * A timestamp must be in ISO 8601 format.
	 *
	 * @param   mixed  $revision  The index or timestamp of the revision
	 * @return  mixed             The archive name (string), or null if not found
	 */
	public function getArchivename($revision)
	{
		// Obtain the properties of the revision
		if (($info = $this->getRevision($revision)) === null) {
			return null;
		}

		// Check for archive name
		if (!isset($info['archivename'])) {
			// Return error message
			$this->error = array();
			$this->error['file'] = 'This revision contains no archive name';
			return null;
		}

		return $info['archivename'];
	}

	/**
	 * Deletes the file, or only an older revision of it.
	 *
	 * @param   string   $reason       Reason for the deletion
	 * @param   string   $archivename  The archive name of the older revision
	 * @return  boolean                True if file (revision) was deleted successfully
	 */
	public function delete($reason = null, $archivename = null)
	{
		$data = array(
			'title' => 'File:' . $this->filename,
		);

		// Set options from arguments
		if (!is_null($reason)) {
			$data['reason'] = $reason;
		}
		if (!is_null($archivename)) {
			$data['oldimage'] = $archivename;
		}

		$r = $this->wikimate->delete($data); // The delete query

		// Check if it worked
		if (isset($r['delete'])) {
			if (is_null($archivename)) {
				$this->exists = false; // The file was deleted altogether
			}

			$this->error = null; // Reset the error status
			return true;
		}

		$this->error = $r['error']; // Return error response
		return false;
	}

	/*
	 *
	 * File contents methods
	 *
	 */

	/**
	 * Downloads and returns the current file's contents,
	 * or null if an error occurs.
	 *
	 * @return  mixed  Contents (string), or null if error
	 */
	public function downloadData()
	{
		// Download file, or handle error
		$data = $this->wikimate->download($this->getUrl());
		if ($data === null) {
			$this->error = $this->wikimate->getError(); // Copy error if there was one
		} else {
			$this->error = null; // Reset the error status
		}

		return $data;
	}

	/**
	 * Downloads the current file's contents and writes it to the given path.
	 *
	 * @param   string   $path  The file path to write to
	 * @return  boolean         True if path was written successfully
	 */
	public function downloadFile($path)
	{
		// Download contents of current file
		if (($data = $this->downloadData()) === null) {
			return false;
		}

		// Write contents to specified path
		if (@file_put_contents($path, $data) === false) {
			$this->error = array();
			$this->error['file'] = "Unable to write file '$path'";
			return false;
		}

		return true;
	}

	/**
	 * Uploads to the current file using the given parameters.
	 * $text is only used for the page contents of a new file,
	 * not an existing one (update that via WikiPage::setText()).
	 * If no $text is specified, $comment will be used as new page text.
	 *
	 * @param   array    $params     The upload parameters
	 * @param   string   $comment    Upload comment for the file
	 * @param   string   $text       The article text for the file page
	 * @param   boolean  $overwrite  True to overwrite existing file
	 * @return  boolean              True if uploading was successful
	 */
	private function uploadCommon(array $params, $comment, $text = null, $overwrite = false)
	{
		// Check whether to overwrite existing file
		if ($this->exists && !$overwrite) {
			$this->error = array();
			$this->error['file'] = 'Cannot overwrite existing file';
			return false;
		}

		// Collect upload parameters
		$params['filename']       = $this->filename;
		$params['comment']        = $comment;
		$params['ignorewarnings'] = $overwrite;
		if (!is_null($text)) {
			$params['text']   = $text;
		}

		// Upload file, or handle error
		$r = $this->wikimate->upload($params);

		if (isset($r['upload']['result']) && $r['upload']['result'] == 'Success') {
			// Update the file's properties
			$this->info = $r['upload']['imageinfo'];

			$this->error = null; // Reset the error status
			return true;
		}

		// Return error response
		if (isset($r['error'])) {
			$this->error = $r['error'];
		} else {
			$this->error = array();
			$this->error['file'] = 'Unexpected upload response: '.$r['upload']['result'];
		}
		return false;
	}

	/**
	 * Uploads the given contents to the current file.
	 * $text is only used for the page contents of a new file,
	 * not an existing one (update that via WikiPage::setText()).
	 * If no $text is specified, $comment will be used as new page text.
	 *
	 * @param   string   $data       The data to upload
	 * @param   string   $comment    Upload comment for the file
	 * @param   string   $text       The article text for the file page
	 * @param   boolean  $overwrite  True to overwrite existing file
	 * @return  boolean              True if uploading was successful
	 */
	public function uploadData($data, $comment, $text = null, $overwrite = false)
	{
		// Collect upload parameter
		$params = array(
			'file' => $data,
		);

		// Upload contents to current file
		return $this->uploadCommon($params, $comment, $text, $overwrite);
	}

	/**
	 * Reads contents from the given path and uploads it to the current file.
	 * $text is only used for the page contents of a new file,
	 * not an existing one (update that via WikiPage::setText()).
	 * If no $text is specified, $comment will be used as new page text.
	 *
	 * @param   string   $path       The file path to upload
	 * @param   string   $comment    Upload comment for the file
	 * @param   string   $text       The article text for the file page
	 * @param   boolean  $overwrite  True to overwrite existing file
	 * @return  boolean              True if uploading was successful
	 */
	public function uploadFile($path, $comment, $text = null, $overwrite = false)
	{
		// Read contents from specified path
		if (($data = @file_get_contents($path)) === false) {
			$this->error = array();
			$this->error['file'] = "Unable to read file '$path'";
			return false;
		}

		// Upload contents to current file
		return $this->uploadData($data, $comment, $text, $overwrite);
	}

	/**
	 * Uploads file contents from the given URL to the current file.
	 * $text is only used for the page contents of a new file,
	 * not an existing one (update that via WikiPage::setText()).
	 * If no $text is specified, $comment will be used as new page text.
	 *
	 * @param   string   $url        The URL from which to upload
	 * @param   string   $comment    Upload comment for the file
	 * @param   string   $text       The article text for the file page
	 * @param   boolean  $overwrite  True to overwrite existing file
	 * @return  boolean              True if uploading was successful
	 */
	public function uploadFromUrl($url, $comment, $text = null, $overwrite = false)
	{
		// Collect upload parameter
		$params = array(
			'url' => $url,
		);

		// Upload URL to current file
		return $this->uploadCommon($params, $comment, $text, $overwrite);
	}
}
