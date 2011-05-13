<?php
 /**
  * Utility functions
  *
  * @author Robert McLeod
  * @version 0.1
  */
 class WikiUtil {
	
	public static function printDebug( $title, $objects=array() ) {

		if ( !WIKI_DEBUG ) return;
		
		echo "<h1>$title</h1>";
		
		foreach ( $objects as $name => $object ) {
		
			echo "$name:\n";
			echo '<pre>',print_r( $object, 1 ), '</pre>';
		}

	}
	
 }
