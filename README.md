# Wikimate

Wikimate is a PHP wrapper for the
[MediaWiki Action API](https://www.mediawiki.org/wiki/Special:MyLanguage/API:Main_page)
that aims to be very easy to use.
It currently consists of three classes:

- **Wikimate** – Serves as a loader and manager for different wiki objects (e.g. pages).
- **WikiPage** – Provides an interface to getting/editing pages or sections of them.
- **WikiFile** – Provides an interface to downloading/uploading files and getting their properties.

The [latest released version](https://github.com/hamstar/Wikimate/releases) of Wikimate
is v0.15.0, released on Aug 26, 2021.
It requires MediaWiki v1.27 or newer.
See [CHANGELOG.md](CHANGELOG.md) for the detailed version history.

## Installation

**Requirements: [PHP](https://php.net), and [Composer](https://getcomposer.org).**

Before anything else, since Wikimate is written in PHP, a server-side language,
you will need to have PHP installed to run it.
Install it with your preferred package management tool
(for example, on Ubuntu Linux you can run: `sudo apt-get install php`)

Install Composer by following the instructions [here](https://getcomposer.org/doc/00-intro.md).

Then, download Wikimate, and initialise it by running `composer install`
(or `composer.bat install` if you're on Windows).

To use Wikimate within another project, you can add it as a composer dependency
by adding the following to your `composer.json` file:

```json
"hamstar/Wikimate": "0.15.0"
```

## Usage

In your script file (e.g. `index.php`), include Wikimate's `globals.php` file,
and create a new `Wikimate` object with the target wiki's API address.
Then provide a username and password to Wikimate's `login` method,
to log in to that wiki.

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
	echo "<b>Wikimate error</b>: ".$error['auth'];
}
```

This example uses echo statements to output any potential errors.
You should get a meaningful error message if the authentication fails.

Instead of using echo statements, you can enable/disable debugging
with the `$wiki->debugMode($boolean)` method.
Currently only output from the logon process is printed for debugging.

Assuming you were able to log in, you're now ready to fully use the API.

See [USAGE.md](USAGE.md) for detailed example code to perform common tasks.

## Contributing

As an open source project, Wikimate welcomes community contributions.
Please see [CONTRIBUTING.md](CONTRIBUTING.md) for information on how to contribute.

## License

This project is licensed under the MIT license.
See [LICENSE.md](LICENSE.md) for details.
