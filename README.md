# What is this

This is a rewrite of the Wikimate class to be more object oriented.  People like and are using the non-oop class so I thought I would leave it as it is.

This class also utilizes Exceptions.

## It is not yet working

I'm working on it... the login system is not yet done

## How to use

Basic usage is almost like before:

    $wikimate = new WikiMate();
    $page = $wikimate->get_page("Toyota Hilux");
    
    if ( $page->exists() )
      echo $page->get_text(); // notice this has no categories in the print out
      
    print_r( $page->get_categories() );
    
    $page->add_category("Indestructible Vehicles")->save(); // adds the category and saves the page
    $page->set_text( "blah" )->refresh(); // this text won't be saved
  
## Tests

Tests to be done:

* Logging in with good details
* Logging in with bad details
* Getting text from uncreated page
* Creating new page
* Getting text from created page
* Setting text and getting text
* Setting categories and getting categories
* Adding categories and getting categories