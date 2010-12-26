# What is this?
Wikimate is a wrapper for the MediaWiki API that aims to be very easy to use.  It consists of two classes currently:
* Wikimate - serves as a loader and manager for different wiki objects (e.g. pages)
* WikiPage - the only object made so far provides an interface to getting/editing pages

# How do I use it?

## Configuration

Need to make sure you have some configuration constants before hand so edit the config.php

define('WIKI_USERNAME','testbot'); // bot name
define('WIKI_PASSWORD','bottest'); // bot password
define('WIKI_API','http://example.com/api.php'); // api url

## Usage

Include the globals.php file and create a new Wikimate object.

	include 'globals.php';
	$wiki = new Wikimate;

On creating a new Wikimate object it will log into the wiki api - if it fails to authenticate your $wiki object will be null.

### Getting a page object

Once logged in you can start playing around with pages.  If the title given to the WikiPage object is invalid, your WikiPage object will be null.

	$page = $wiki->getPage('Sausages'); // create a new page object
	if ( $page->exists() ) die(); // check if the page exists or not
	echo $page->getTitle(); // get the title

### Reading...

You can get the text of the page by using the getText() method which returns the text that was obtained when the page object was created.  If you 
want fresh page text from the wiki then just put boolean true as the first argument.

	$wikiCode = $page->getText(); // get the text of the page
	$wikiCode = $page->getText(true); // get fresh page text from the api

### Writing...

You can modify the whole article using the setText() method:

	$this->setText("==Testing==\n\n This is a whole page"); // returns true if the edit worked
	$this->setText("==Changed==\n\n I just changed the whole page"); // the setText() method will overwrite the entire page!

You can modify only sections of the article by adding a second parameter to the setText() method:

	$this->setText("==Section 4==\n\nThis will appear in section 4", 4 ); // provide a section number to overwrite only that section
	$this->setText("==New section==\n\nStuff", 'new' ) // ...or make a new section
	$this->setText("Sausages are cylindrical packages of meat.", 0 ) // ...zero is the very first section

### Other stuff

Something go wrong?  Check the error array"

	print_r( $this->getError() );

Wanna run your own queries?  You can use the edit and query commands in Wikimate:

	$data = array(
		'prop' => 'info|revisions',
		'intoken' = 'edit',
		'titles' => 'this|that|other'
	);
	
	// Send data as a query
	$array_result = $wiki->query( $data );
	
	$data = array(
		'title' => 'this',
		'token' => '+\', // this is urlencoded automatically
		'etc' => 'stuff'
	);
	
	// Send as an edit query with content-type of application/x-www-form-urlencoded
	$array_result = $wiki->edit( $data );

Both methods return an array of the MediaWiki API result.

# Requires?
* Mediawiki API
* Sean Hubers [awesome curl wrapper](http://github.com/shuber/curl)
