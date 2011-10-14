# What is this?
Wikimate is a wrapper for the MediaWiki API that aims to be very easy to use.  It consists of two classes currently:

* **Wikimate** - Serves as a loader and manager for different wiki objects (e.g. pages)
* **WikiPage** - The only object made so far. Provides an interface to getting/editing pages

# How do I use it?

First of all make sure you download [Sean Huber's awesome curl wrapper](http://github.com/shuber/curl) and copy `curl.php` and the `lib` folder into your wikimate directory.

Also make sure that the server has access to write to the folder, or make a wikimate_cookie.txt file in the same directory as the wikimate files and give the server write access to that.  If you don't do this you won't be able to login and Wikimate will throw an exception.

## Usage

Include the `globals.php` file and create a new Wikimate object with username, password and the api address.

	include 'globals.php';
	
	$api_url = 'http://example.com/api.php';
	$username = 'bot';
	$password = 'password';
	
	try
	{
		$wiki = new Wikimate($api_url);
		if ($wiki->login($username,$password))
			echo 'user logged in .' ;
		else {
			$error = $wiki->getError();
			$echo $error['login'];
		}
	}
	catch ( Exception $e )
	{
		echo "An error occured: ".$e->getMessage();
	}

On creating a new Wikimate object it will log into the wiki api - **if it fails to authenticate** your `$wiki` object will be null.  You should get a meaningful error message telling you why it didn't authenticate.

You can also enable/disable debugging with the `$wiki->debugMode($boolean)` method - currently only output from the logon process is printed for debugging.

### Getting a page object

Once logged in you can start playing around with pages.  If the title given to the WikiPage object is invalid, your `$page` object will be null.

	$page = $wiki->getPage('Sausages'); // create a new page object
	if ( $page->exists() ) die(); // check if the page exists or not
	echo $page->getTitle(); // get the title
	echo $page->getNumSections(); // get the number of sections on the page
	echo $page->getSectionOffsets(); // gives you an array of where each section starts and its length

### Reading...

You can get the text of the page by using the `getText()` method which returns the text that was obtained when the page object was created.  If you want fresh page text from the wiki then just put boolean `true` as the first argument.

	$wikiCode = $page->getText(); // get the text of the page
	$wikiCode = $page->getText(true); // get fresh page text from the api and rebuild sections

You can get sections from the page as well, via the section index, or the section heading name

    $wikiCode = $page->getSection(0); // get the part between the title and the first section
	$wikiCode = $page->getSection('intro'); // get the part between the title and the first section
	$wikiCode = $page->getSection(4); // get the 4th section on the page
	$wikiCode = $page->getSection('History'); // get the section called History
	$wikiCode = $page->getSection(4, true); // get the 4th section on the page including the heading

You can even get an array with all the sections in it by either index or name

	$sections = $page->getAllSections(); // get all the sections (by index number)
	$sections = $page->getAllSections(true); // get all the sections (by index number) with the section heading names
	$sections = $page->getAllSections(false, WikiPage:SECTIONLIST_BY_NAME); // get all the sections (by section name)
	$sections = $page->getAllSections(false, 2); // get all the sections (by section name)

The array looks like this:
    Array
    (
        [intro] => bit between title and first section
        [Summary] => The summary goes here
        [Context] => This is the context
        [Impact] => The impact is here
        [Media Articles] => Links go here
        [References] => <references/>
    )

### Writing...

You can modify the whole article using the `setText()` method:

	$page->setText("==Testing==\n\n This is a whole page"); // returns true if the edit worked
	$page->setText("==Changed==\n\n I just changed the whole page"); // the setText() method will overwrite the entire page!

You can modify only sections of the article by adding a second parameter to the `setText()` method.  Please note you can't use section names here **you must use section indexes**.

	$page->setText("==Section 4==\n\nThis will appear in section 4", 4 ); // provide a section number to overwrite only that section
	$page->setText("==New section==\n\nStuff", 'new' ) // ...or make a new section
	$page->setText("Sausages are cylindrical packages of meat.", 0 ) // ...zero is the very first section

Minor edit switch and summary description are in the third and fourth arguments

	$page->setText( $text, $section, true, "removing spam!");

Here's some easier functions for editing sections

	$page->setSection( $text, $section, $summary, $minor );
	$page->newSection( $sectionTitle, $text );


### Other stuff

Something go wrong?  Check the error array

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

# Changelog

## Version 0.5

* Removed the use of constants in favour of constructor arguments
* Added checks that throw an exception if can't write to wikimate_cookie.txt
* Throws exception if curl library not loaded
* Throws exception if can't login

## Version 0.4

* Added `WikiPage::newSection()` and `WikiPage::setSection()` (shortcuts to `WikiPage::setText()`)
* Added the ability to get individual sections of the article with `WikiPage::getSection()`
* Added the ability to get all sections in an array with `WikiPage::getAllSections()`
* Added the ability to get array showing section offsets and lengths in the page wikicode with `WikiPage::getSectionOffsets()`
* Added the ability to see how many sections are on a page with `WikiPage::getNumSections()`

## Version 0.3

* Initial commit
