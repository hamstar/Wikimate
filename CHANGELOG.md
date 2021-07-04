## Changelog

Since v0.10.0 this project adheres to [Semantic Versioning](http://semver.org/) and [Keep a Changelog](http://keepachangelog.com/).

### Upcoming version

#### Added

* Added more debug logging of MediaWiki requests and responses ([#101])
* New GOVERNANCE.md file to explicitly codify the project management principles and provide guidelines for maintenance tasks ([#83])

#### Changed

* Modernize token handling for login and data-modifying actions ([#100])

#### Fixed

* Prevented PHP notice in `WikiFile::getInfo()` for moved or deleted file ([#85])

### Version 0.12.0

#### Added

* New class WikiFile to retrieve properties of a file, and download and upload its contents.  All properties pertain to the current revision of the file, or a specific older revision. ([#69], [#71], [#78], [#80])
* WikiFile also provides the file history and the ability to delete a file or an older revision of it ([#76])

### Version 0.11.0

#### Added

* Support for a section name (in addition to an index) in `WikiPage::setText()` and `WikiPage::setSection()` ([#45])
* Support for optional domain at authentication ([#28])

#### Changed

* Updated `WikiPage::getSection()` to include subsections by default; disabling the new `$includeSubsections` option reverts to the old behavior of returning only the text until the first subsection ([#55])
* Improved section processing in `WikiPage::getText()` ([#33], [#37], [#50])
* Ensured that MediaWiki API error responses appear directly in `WikiPage::$error` rather than a nested 'error' array ([#63]) -- this may require changes in your application's error handling
* Restructured and improved documentation ([#32], [#34], [#47], [#49], [#61])

#### Fixed

* Ensured use of Wikimate user agent by Requests library ([#64])
* Corrected handling an invalid page title ([#57])
* Fixed returning an empty section without header in `WikiPage::getSection()` ([#52])
* Prevented PHP Notices in several methods ([#43], [#67])
* Corrected handling an unknown section parameter in `WikiPage::getSection()` ([#41])
* Fixed passing the return value in `WikiPage::setSection()` ([#30])
* Corrected call to `Wikimate::debugRequestsConfig()` ([#30])

### Version 0.10.0

#### Changed

* Switched to using the *Requests* library instead of Curl ([#25])

### Version 0.9 - 2014-06-13

* Bumped version for stable release

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

[#25]: https://github.com/hamstar/Wikimate/pull/25
[#28]: https://github.com/hamstar/Wikimate/pull/28
[#30]: https://github.com/hamstar/Wikimate/pull/30
[#32]: https://github.com/hamstar/Wikimate/pull/32
[#33]: https://github.com/hamstar/Wikimate/pull/33
[#34]: https://github.com/hamstar/Wikimate/pull/34
[#37]: https://github.com/hamstar/Wikimate/pull/37
[#41]: https://github.com/hamstar/Wikimate/pull/41
[#43]: https://github.com/hamstar/Wikimate/pull/43
[#45]: https://github.com/hamstar/Wikimate/pull/45
[#47]: https://github.com/hamstar/Wikimate/pull/47
[#49]: https://github.com/hamstar/Wikimate/pull/49
[#50]: https://github.com/hamstar/Wikimate/pull/50
[#52]: https://github.com/hamstar/Wikimate/pull/52
[#55]: https://github.com/hamstar/Wikimate/pull/55
[#57]: https://github.com/hamstar/Wikimate/pull/57
[#61]: https://github.com/hamstar/Wikimate/pull/61
[#63]: https://github.com/hamstar/Wikimate/pull/63
[#64]: https://github.com/hamstar/Wikimate/pull/64
[#67]: https://github.com/hamstar/Wikimate/pull/67
[#69]: https://github.com/hamstar/Wikimate/pull/69
[#71]: https://github.com/hamstar/Wikimate/pull/71
[#76]: https://github.com/hamstar/Wikimate/pull/76
[#78]: https://github.com/hamstar/Wikimate/pull/78
[#80]: https://github.com/hamstar/Wikimate/pull/80
[#83]: https://github.com/hamstar/Wikimate/pull/83
[#85]: https://github.com/hamstar/Wikimate/pull/85
[#100]: https://github.com/hamstar/Wikimate/pull/100
[#101]: https://github.com/hamstar/Wikimate/pull/101

