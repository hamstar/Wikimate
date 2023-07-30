# Usage

- [Introduction](#introduction)
- [Getting a page object](#getting-a-page-object)
  - [Reading...](#reading)
  - [Writing...](#writing)
  - [Deleting...](#deleting)
- [Getting a file object](#getting-a-file-object)
  - [Downloading...](#downloading)
  - [Uploading...](#uploading)
  - [Accessing revisions...](#accessing-revisions)
  - [Deleting...](#deleting-1)
- [Other stuff](#other-stuff)
  - [Running custom queries](#running-custom-queries)
  - [Restoring a deleted page or file](#restoring-a-deleted-page-or-file)
  - [Customizing the user agent](#customizing-the-user-agent)
  - [Maximum lag and retries](#maximum-lag-and-retries)
  - [Handling errors and exceptions](#handling-errors-and-exceptions)
  - [Debug logging](#debug-logging)

## Introduction

In your script file (e.g. `index.php`), include the project's `autoload.php` file,
and create a new `Wikimate` object with the target wiki's API address.
Then provide a username and password to Wikimate's `login` method,
to log in to that wiki.

```php
require __DIR__.'/vendor/autoload.php';

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
	echo "<b>Wikimate error</b>: ".$error['auth'];
}
```

This example uses echo statements to output any potential errors.
You should get a meaningful error message if the authentication fails.

Assuming you were able to log in, you're now ready to fully use the API.
The next sections provide example code for several common tasks.

## Getting a page object

Once logged in you can start playing around with pages.
If the title given to the WikiPage object is invalid,
your `$page` object will be null.

```php
// create a new page object
$page = $wiki->getPage('Sausages');
// check if the page exists or not
if (!$page->exists()) die('Page not found');
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

You can get sections from the page as well,
via the section index or the section heading:

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

```text
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

An `UnexpectedValueException` is thrown
if an unsupported value is supplied for the `$keyNames` parameter.

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
$page->setText("==Section 4==\n\nThis will appear in section 4", 4);
// ... or overwrite a section by name
$page->setText("==History==\n\nThis will appear in the history section", 'History');
// ...or make a new section
$page->setText("==New section==\n\nStuff", 'new')
// ...zero is the very first section
$page->setText("Sausages are cylindrical packages of meat.", 0)
```

The minor edit switch and the edit summary description are the third and fourth arguments:

```php
$page->setText($text, $section, true, "removing spam!");
```

Here are some easier methods for editing sections:

```php
$page->setSection($text, $section, $summary, $minor);
$page->newSection($sectionTitle, $text);
```

For the latter method, the $sectionTitle is also used as part of the edit summary description.

### Deleting...

If the account you're using has delete permissions,
you can delete entire pages with `delete()`:

```php
// returns true if the delete was successful
$page->delete('The page was created accidentally in the first place');
```

If you pass in a message argument,
it will be recorded as the reason for the deletion.

## Getting a file object

Once connected you can also start playing around with files.

```php
// create a new file object
$file = $wiki->getFile('Site-logo.png');
// check if the file exists or not
if (!$file->exists()) die('File not found');
// get the file name
echo $file->getFilename();
```

All available properties of the file are accessible via `get` methods.

```php
// get the file size, timestamp, and hash
echo $file->getSize();
echo $file->getTimestamp();
echo $file->getSha1();
// get dimensions and MIME type for an image
echo $file->getHeight();
echo $file->getWidth();
echo $file->getMime();
// get aspect ratio of an image
// this is a convenience method rather than a direct property
echo $file->getAspectRatio();
```

### Downloading...

You can obtain the data of the file by using the `downloadData()` method and use it in your script,
or write it directly to a local file via the `downloadFile()` method.

```php
$data = $file->downloadData();
// process image $data of Site-logo.png
$result = $file->downloadFile('/path/to/sitelogo.png');
```

### Uploading...

You can upload data from your script to the file by using the `uploadData()` method,
or read it directly from a local file via the `uploadFile()` method.
Additionally, uploading from a URL is possible via the `uploadFromUrl()` method.

A comment for the file's history must be supplied,
and for a new file the text for its associated description page can be provided as well.
If no such text is passed, the comment will be used instead.

All upload methods guard against uploading data to an existing file,
but allow this when the overwrite flag is set.

```php
// construct image $data for Site-logo.png
$result = uploadData($data, 'Upload new site logo', 'New site logo to reflect the new brand', true);
$result = uploadFile('/path/to/newlogo.png', 'Upload new site logo', 'New site logo to reflect the new brand', true);

// add a new button to the site
$file = $wiki->getFile('New-button.png');
if ($file->exists()) die('New button already exists');
$result = uploadFile('/path/to/newbutton.png', 'Upload new button', 'New button to match the new logo');

// add an example image from a remote URL
$file = $wiki->getFile('Wiki-example.jpg');
if ($file->exists()) die('Example image already exists');
$result = uploadFromUrl('https://upload.wikimedia.org/wikipedia/en/a/a9/Example.jpg', 'Adopt Wiki example image');
```

### Accessing revisions...

The revision history of a file can be obtained as an array with a properties array per revision:

```php
$file = $wiki->getFile('Frequently-changed-file.zip');
// get only the current revision
$history = $file->getHistory();
// get the maximum number of revisions (500 for user accounts, 5000 for bot accounts)
$history = $file->getHistory(true);
// get the latest 10 revisions during 2015 (can use all MediaWiki timestamp formats)
$history = $file->getHistory(true, 10, '2015-01-01 00:00:00', '2015-12-31 23:59:59');
// iterate over revisions and print properties
foreach ($history as $revision => $properties) {
  echo "Revision $revision properties:\n";
  print_r($properties);
}
```

A specific revision can be requested by revision sequence number or by exact timestamp,
as can its archive name.
Invoking `getHistory(true[, ...])` is required before any older revisions can be requested.

```php
// get the latest 50 revisions
$history = $file->getHistory(true, 50);
// get all properties of the penultimate revision
$revision = $file->getRevision(1);
// get the archive name of the specific revision (must be ISO 8601 format)
$archivename = $file->getArchivename('2016-11-22T33:44:55Z');
```

All standard file properties can also be obtained for one specific revision:

```php
// get the file size of the current revision
echo $file->getSize(0);
// get the hash of the penultimate revision
echo $file->getSha1(1);
// get the aspect ratio of the antepenultimate revision
echo $file->getAspectRatio(2);
// get the URL of the specific revision (must be ISO 8601 format)
echo $file->getUrl('2016-11-22T33:44:55Z');
```

### Deleting...

If the account you're using has delete permissions, you can delete files as well:

```php
$file = $wiki->getFile('Old-button.png');
// returns true if the delete was successful
$file->delete('The button was superseded by a new one');
```

To delete or revert to a specific older revision of the file,
the archive name is needed:

```php
$file = $wiki->getFile('Often-changed-file.zip');
$history = $file->getHistory(true);
$archivename = $file->getArchivename(3);
$file->delete('This was an inadvertent release', $archivename);
$archivename = $file->getArchivename(1);
$file->revert($archivename, 'Revert to the previous release');
```

## Other stuff

### Running custom queries

Wanna run your own queries?
You can use the edit and query commands in Wikimate:

```php
$data = array(
	'prop' => 'info|revisions',
	'titles' => 'this|that|other'
);

// Send data as a query
$array_result = $wiki->query($data);

$data = array(
	'title' => 'this',
	'etc' => 'stuff'
);

// Send as an edit query with content-type of application/x-www-form-urlencoded
$array_result = $wiki->edit($data);
```

Both methods return an array of the MediaWiki API result.

### Restoring a deleted page or file

A previously deleted page or file can be undeleted via its original path,
including namespace if applicable:

```php
$wiki->undelete('Sausages');
$wiki->undelete('File:Old-button.png');
```

### Customizing the user agent

API requests are made over HTTP with a user agent string to identify
the client to the server. By default the user agent is formatted as:

`Wikimate/<VERSION> (https://github.com/hamstar/Wikimate)`

The string can be retrieved and customized via:

```php
$useragent = $wiki->getUserAgent();
$wiki->setUserAgent('Custom Prefix - ' . $useragent);
$wiki->setUserAgent('Custom User Agent');
```

In order to use a custom user agent for all requests in the session,
call this method before invoking `Wikimate::login()`.

### Maximum lag and retries

API requests include the [maxlag parameter](https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Maxlag_parameter)
so they time out when the server's time to respond exceeds the specified lag.
The default lag is 5 seconds, which can be obtained via `$wiki->getMaxlag()`
and changed via `$wiki->setMaxlag()`.
Upon a lag error response,
the request is [paused](https://www.php.net/manual/en/function.sleep)
for the number of seconds recommended by the server, and then retried.
Retries continue indefinitely, unless limited via `$wiki->setMaxretries()`.
If a limited number of retries runs out, `WikimateException` is thrown.

### Handling errors and exceptions

Did something go wrong?  Check the error array:

```php
print_r($page->getError());
print_r($file->getError());
```

For MediaWiki API errors, the array contains the 'code' and 'info' key/value pairs
[defined by the API](https://www.mediawiki.org/wiki/Special:MyLanguage/API:Errors_and_warnings#Errors).
For other errors, the following key/value pairs are returned:

- 'auth' for Wikimate authentication (login & logout) problems
- 'token' for Wikimate token problems
- 'page' for WikiPage errors
- 'file' for WikiFile errors

In case of an unexpected error while communicating with the API,
i.e. receiving an HTML response or an invalid JSON response,
or running out of maxlag retries, `WikimateException` is thrown.

### Debug logging

In addition to checking the error array,
you can enable/disable debugging with the `$wiki->debugMode($boolean)` method.
Debug logging is printed to [standard output](https://en.wikipedia.org/wiki/Standard_output)
and includes API requests/responses as well as retry messages for [lag errors](#maximum-lag-and-retries).

Only requests for file uploads are not logged,
because of the (potential) volume of the associated data.
