# Wikimate

Wikimate is a PHP wrapper for the
[MediaWiki Action API](https://www.mediawiki.org/wiki/Special:MyLanguage/API:Main_page)
that aims to be very easy to use.
It currently consists of three classes:

- **Wikimate** – Serves as a loader and manager for different wiki objects (e.g. pages).
- **WikiPage** – Provides an interface to getting/editing pages or sections of them.
- **WikiFile** – Provides an interface to downloading/uploading files and getting their properties.

The [latest released version](https://github.com/hamstar/Wikimate/releases) of Wikimate
is v1.1.0, released on July 30, 2023.
It requires PHP v5.3 or newer and MediaWiki v1.27 or newer.
See [CHANGELOG.md](CHANGELOG.md) for the detailed version history.

## Installation

**Requirements: [PHP](https://php.net), and [Composer](https://getcomposer.org).**

Before anything else, since Wikimate is written in PHP, a server-side language,
you will need to have PHP installed to run it.
Install it with your preferred package management tool
(for example, on Ubuntu Linux you can run: `sudo apt-get install php`)

The recommended way to install this library is with Composer.
Composer is a dependency management tool for PHP
that allows you to declare the dependencies your project needs
and installs them into your project.

Install Composer by following the instructions [here](https://getcomposer.org/doc/00-intro.md).

Then, run the following command in your project's folder
to download Wikimate and initialise it:

```sh
composer require hamstar/Wikimate
```

(or `composer.bat require hamstar/Wikimate` if you're on Windows).

To use Wikimate within another project, you can add it as a Composer dependency
by adding the following to your existing `composer.json` file:

```json
{
    "require": {
        "hamstar/Wikimate": "^1.1"
    }
}
```

You can find out more on how to install Composer,
configure autoloading, and other best-practices for defining dependencies
at [getcomposer.org](https://getcomposer.org).

## Usage

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

See [USAGE.md](USAGE.md) for detailed example code to perform common tasks.

## Contributing

As an open source project, Wikimate welcomes community contributions.
Please see [CONTRIBUTING.md](CONTRIBUTING.md) for information on how to contribute.

## License

This project is licensed under the MIT license.
See [LICENSE.md](LICENSE.md) for details.
