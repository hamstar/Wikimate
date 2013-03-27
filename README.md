Wikimate is a wrapper for the MediaWiki API that aims to be very easy to use.
It consists of two classes currently:

* **Wikimate** – Serves as a loader and manager for different wiki objects (e.g. pages).
* **WikiPage** – The only object made so far. Provides an interface to getting/editing pages.

## Installation

*Note:* The commands below apply to Ubuntu. You might need to adjust them for other systems.

Before anything else, since Wikimate is written in PHP, a server-side language,
you will need a web server such as Apache to run it (and of course, PHP).

    sudo apt-get install apache2 php5

You will also need cURL; Install it if you don't have it yet.

    sudo apt-get install curl php5-curl

Then, download Wikimate.
To make sure the [curl wrapper submodule](http://github.com/shuber/curl)
is also downloaded, use git's --recursive option:

    git clone --recursive git@github.com:hamstar/Wikimate.git

Now you need to allow the server to write to the cookie file.
Create a `wikimate_cookie.txt` file in the same directory as the wikimate files
and give the server write access to that.
If you don't do this you won't be able to login and Wikimate will throw an exception.

    cd Wikimate
    touch wikimate_cookie.txt
    sudo chown www-data wikimate_cookie.txt


## Usage

In your script file (e.g. `index.php`), include the `globals.php` file
and create a new `Wikimate` object with username, password and the API address.

```php
include 'globals.php';
	
$api_url = 'http://example.com/api.php';
$username = 'bot';
$password = 'password';
	
try
{
	$wiki = new Wikimate($api_url);
	if ($wiki->login($username,$password))
		echo 'Success: user logged in.' ;
	else {
		$error = $wiki->getError();
		echo $error['login'];
	}
}
catch ( Exception $e )
{
	echo "An error occured: ".$e->getMessage();
}
```

On creating a new Wikimate object it will log into the wiki api.
**If it fails to authenticate, your `$wiki` object will be null**.
You should get a meaningful error message telling you why it didn't authenticate.

You can also enable/disable debugging with the `$wiki->debugMode($boolean)` method.
Currently only output from the logon process is printed for debugging.

### Getting a page object

Once logged in you can start playing around with pages.
If the title given to the WikiPage object is invalid, your `$page` object will be null.

```php
// create a new page object
$page = $wiki->getPage('Sausages');
// check if the page exists or not
if ( $page->exists() ) die();
// get the title
echo $page->getTitle();
// get the number of sections on the page
echo $page->getNumSections();
// get an array of where each section starts and its length
echo $page->getSectionOffsets();
```

### Reading...

You can get the text of the page by using the `getText()` method
which returns the text that was obtained when the page object was created.
If you want fresh page text from the wiki
then just put boolean `true` as the first argument.

```php
// get the text of the page
$wikiCode = $page->getText();
// get fresh page text from the api and rebuild sections
$wikiCode = $page->getText(true);
```

You can get sections from the page as well, via the section index, or the section heading name

```php
// get the part between the title and the first section
$wikiCode = $page->getSection(0);
// get the part between the title and the first section
$wikiCode = $page->getSection('intro');
// get the 4th section on the page
$wikiCode = $page->getSection(4);
// get the section called History
$wikiCode = $page->getSection('History');
// get the 4th section on the page including the heading
$wikiCode = $page->getSection(4, true);
```

You can even get an array with all the sections in it by either index or name

```php
// get all the sections (by index number)
$sections = $page->getAllSections();
// get all the sections (by index number) with the section heading names
$sections = $page->getAllSections(true);
// get all the sections (by section name)
$sections = $page->getAllSections(false, WikiPage:SECTIONLIST_BY_NAME);
// get all the sections (by section name)
$sections = $page->getAllSections(false, 2);
```

The array looks like this:

```
Array
(
	[intro] => bit between title and first section
	[Summary] => The summary goes here
	[Context] => This is the context
	[Impact] => The impact is here
	[Media Articles] => Links go here
	[References] => <references/>
)
```

### Writing...

You can modify the whole article using the `setText()` method:

```php
// returns true if the edit worked
$page->setText("==Testing==\n\n This is a whole page");
// the setText() method will overwrite the entire page!
$page->setText("==Changed==\n\n I just changed the whole page");
```

You can modify only sections of the article
by adding a second parameter to the `setText()` method.
Please note you can't use section names here; **you must use section indexes**.

```php
// provide a section number to overwrite only that section
$page->setText("==Section 4==\n\nThis will appear in section 4", 4 );
// ...or make a new section
$page->setText("==New section==\n\nStuff", 'new' )
// ...zero is the very first section
$page->setText("Sausages are cylindrical packages of meat.", 0 )
```

The minor edit switch and the summary description are the third and fourth arguments:

```php
$page->setText( $text, $section, true, "removing spam!");
```

Here are some easier functions for editing sections:

```php
$page->setSection( $text, $section, $summary, $minor );
$page->newSection( $sectionTitle, $text );
```

### Deleting...

If the account you're using has delete permissions,
you can delete entire pages with `delete()`:

```php
// returns true if the delete was successful
$page->delete("The page was created accidentally in the first place.");
```

If you pass in a message argument, it will be recorded as a reason for the deletion.

### Other stuff

Did something go wrong?  Check the error array:

```php
print_r( $this->getError() );
```

Wanna run your own queries?
You can use the edit and query commands in Wikimate:

```php
$data = array(
	'prop' => 'info|revisions',
	'intoken' => 'edit',
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
```

Both methods return an array of the MediaWiki API result.

## Changelog

### Version 0.5

* Removed the use of constants in favour of constructor arguments
* Added checks that throw an exception if can't write to wikimate_cookie.txt
* Throws exception if curl library not loaded
* Throws exception if can't login

### Version 0.4

* Added `WikiPage::newSection()` and `WikiPage::setSection()` (shortcuts to `WikiPage::setText()`)
* Added the ability to get individual sections of the article with `WikiPage::getSection()`
* Added the ability to get all sections in an array with `WikiPage::getAllSections()`
* Added the ability to get array showing section offsets and lengths in the page wikicode with `WikiPage::getSectionOffsets()`
* Added the ability to see how many sections are on a page with `WikiPage::getNumSections()`

### Version 0.3

* Initial commit
