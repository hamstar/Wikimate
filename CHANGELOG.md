## Changelog

### Version 0.12.0

* Supports a section name (in addition to an index) in `WikiPage::setText()` and `WikiPage::setSection()` (#45)
* Improved section processing in `WikiPage::getText()` (#33, #35)
* Supports optional domain at authentication (#28)
* Restructured and improved documentation (#32, #34, #47)
* Bug fix: prevent PHP Notices in several methods (#43)
* Bug fix: handle unknown section parameter correctly in `WikiPage::getSection()` (#41)
* Bug fix: pass return value in `WikiPage::setSection()` (#30)
* Bug fix: correct call to `Wikimate::debugRequestsConfig()` (#30)

### Version 0.10.0

* Switched to using the *Requests* library instead of Curl (#25)

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
