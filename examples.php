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

echo "Attempting to log in . . . ";
if ($wiki->login($username, $password)) {
	echo "Success.\n";
} else {
	$error = $wiki->getError();
	echo "\nWikimate error: ".$error['auth']."\n";
	exit(1);
}

echo "Fetching 'Sausages'...\n";
$page = $wiki->getPage('Sausages');

// check if the page exists or not
if (!$page->exists() ) {
	echo "'Sausages' doesn't exist. Creating...\n";
	// compile initial page text
	$pagetext = "Intro about '''sausages'''.\n";
	$pagetext .= "\n== Meat ==\n";
	$pagetext .= "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n";
	$pagetext .= "\n== Veggie ==\n";
	$pagetext .= "Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n";
	// store page and check for error
	if ($page->setText($pagetext, null, false, 'Create initial page')) {
		echo "\n'Sausages' created.\n";
	} else {
		$error = $page->getError();
		echo "\nError: " . print_r($error, true) . "\n";
	}
} else {
	// get the page title
	echo "Title: ".$page->getTitle()."\n";
	// get the number of sections on the page
	echo "Number of sections: ".$page->getNumSections()."\n";
	// get an array of where each section starts and its length
	echo "Section offsets: ".print_r($page->getSectionOffsets(), true)."\n";
	// get and update intro section
	$introtext = $page->getSection(0);
	$introtext .= "\nMore about sausage variants.\n";
	// store intro and check for error
	if ($page->setSection($introtext, 0, 'Update intro section', true)) {
		echo "\n'Sausages' intro updated.\n";
	} else {
		$error = $page->getError();
		echo "\nError: " . print_r($error, true) . "\n";
	}
}
