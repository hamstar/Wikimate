<?php

include 'globals.php';

$api_url = 'http://localhost/mediawiki/api.php';
echo "Connecting to: $api_url\n";

echo "Enter your username: ";
$username = trim(fread(STDIN, 100));

echo "Enter your password: ";
$password = trim(fread(STDIN, 100));

$wiki = new Wikimate($api_url);
#$wiki->setDebugMode(TRUE);

try {
	echo "Attempting to log in . . . ";
	if ($wiki->login($username, $password)) {
		echo "Success.\n";
	} else {
		$error = $wiki->getError();
		echo "\nWikimate error: ".$error['login']."\n";
		exit(1);
	}
} catch (Exception $e) {
	echo "\nWikimate exception: ".$e->getMessage()."\n";
	exit(1);
}

echo "Fetching 'Sausages'...\n";
$page = $wiki->getPage('Sausages');

// check if the page exists or not
if (!$page->exists() ) {
	echo "'Sausages' doesn't exist.\n";

} else {
	// get the page title
	echo "Title: ".$page->getTitle()."\n";
	// get the number of sections on the page
	echo "Number of sections: ".$page->getNumSections()."\n";
	// get an array of where each section starts and its length
	echo "Section offsets:".print_r($page->getSectionOffsets(), true)."\n";

}
