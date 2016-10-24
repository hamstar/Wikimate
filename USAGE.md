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

$wiki = new Wikimate($api_url);

// You can also pass the domain name:
// $wiki->login($username, $password, $domainName)
if ($wiki->login($username, $password))
	echo 'Success: user logged in.' ;
else {
	$error = $wiki->getError();
	echo "<b>Wikimate error</b>: ".$error['login'];
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

You can get sections from the page as well, via the section index, or the section heading name:

```php
// get the part between the title and the first section
$wikiCode = $page->getSection(0);
// get the part between the title and the first section
$wikiCode = $page->getSection('intro');
// get the section called History and any subsections, but no heading
$wikiCode = $page->getSection('History');
// get the 4th section on the page and any subsections, but no heading
$wikiCode = $page->getSection(4);
// get the section called History including the heading, and any subsections
$wikiCode = $page->getSection('History', true);
// get the 4th section on the page including the heading, without subsections
$wikiCode = $page->getSection(4, true, false);
```

You can even get an array with all the sections in it by either index or name:

```php
// get all the sections (by index number)
$sections = $page->getAllSections();
// get all the sections (by index number) with the section heading names
$sections = $page->getAllSections(true);
// get all the sections (by section name)
$sections = $page->getAllSections(false, WikiPage::SECTIONLIST_BY_NAME);
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

An Exception is thrown if an unsupported value is supplied for the $keyNames parameter.

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
You can use both section names and section indexes here.

```php
// provide a section number to overwrite only that section
$page->setText("==Section 4==\n\nThis will appear in section 4", 4 );
// ... or overwrite a section by name
$page->setText("==History==\n\nThis will appear in the history section", 'History' );
// ...or make a new section
$page->setText("==New section==\n\nStuff", 'new' )
// ...zero is the very first section
$page->setText("Sausages are cylindrical packages of meat.", 0 )
```

The minor edit switch and the edit summary description are the third and fourth arguments:

```php
$page->setText( $text, $section, true, "removing spam!");
```

Here are some easier methods for editing sections:

```php
$page->setSection( $text, $section, $summary, $minor );
$page->newSection( $sectionTitle, $text );
```

For the latter method, the $sectionTitle is also used as part of the edit summary description.

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
	'token' => '+\\', // this is urlencoded automatically
	'etc' => 'stuff'
);

// Send as an edit query with content-type of application/x-www-form-urlencoded
$array_result = $wiki->edit( $data );
```

Both methods return an array of the MediaWiki API result.
