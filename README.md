Wikimate is a wrapper for the MediaWiki API that aims to be very easy to use.
It consists of two classes currently:

* **Wikimate** – Serves as a loader and manager for different wiki objects (e.g. pages).
* **WikiPage** – The only object made so far. Provides an interface to getting/editing pages.

## Installation

**Requirements: [PHP](http://php.net), and [Composer](http://getcomposer.org).**

Before anything else, since Wikimate is written in PHP, a server-side language,
you will need to have PHP installed to run it. Install it with your preferred
package management tool (for example, on Ubuntu Linux you can run:
`sudo apt-get install php5`)

Install Composer by following the instructions at https://getcomposer.org/doc/00-intro.md

Then, download Wikimate, and initialise it by running `composer install` (or
`composer.bat install` if you're on Windows).

To use Wikimate within another project, you can add it as a composer dependency
by adding the following to your `composer.json` file:

    "hamstar/Wikimate": "0.10.0"

## Usage

In your script file (e.g. `index.php`), include the `globals.php` file,
and create a new `Wikimate` object with the target wiki's API address.
Then provide a username and password to Wikimate's `login` method,
to login to that wiki.

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
		echo "<b>Wikimate error</b>: ".$error['login'];
	}
}
catch ( Exception $e )
{
	echo "<b>Wikimate error</b>: ".$e->getMessage();
}
```

This example uses echo statements to output any potential errors.
You should get a meaningful error message if the authentication fails.

Instead of using echo statements, you can enable/disable debugging
with the `$wiki->debugMode($boolean)` method.
Currently only output from the logon process is printed for debugging.

Assuming you were able to login, you're now ready to fully use the API.
The next sections provide example code for several common tasks.


### Getting a page object

Once logged in you can start playing around with pages.
If the title given to the WikiPage object is invalid, your `$page` object will be null.

```php
// create a new page object
$page = $wiki->getPage('Sausages');
// check if the page exists or not
if ( $page->exists() ) die();
// get the page title
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

### Version 0.10.0

* Switched to using the *Requests* library instead of Curl

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

## MIT Licence

Copyright (c) 2014 Robert McLeod

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
