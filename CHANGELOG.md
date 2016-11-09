## Changelog

Since v0.10.0 this project adheres to [Semantic Versioning](http://semver.org/) and [Keep a Changelog](http://keepachangelog.com/).

### Version 0.11.0

#### Added

* Support for a section name (in addition to an index) in `WikiPage::setText()` and `WikiPage::setSection()` ([#45])
* Support for optional domain at authentication ([#28])

#### Changed

* Updated `WikiPage::getSection()` to include subsections by default; disabling the new `$includeSubsections` option reverts to the old behavior of returning only the text until the first subsection ([#55])
* Improved section processing in `WikiPage::getText()` ([#33], [#37], [#50])
* Restructured and improved documentation ([#32], [#34], [#47], [#49], [#61])

#### Fixed

* Ensured use of Wikimate user agent by Requests library ([#64])
* Corrected handling an invalid page title ([#57])
* Fixed returning an empty section without header in `WikiPage::getSection()` ([#52])
* Prevented PHP Notices in several methods ([#43])
* Corrected handling an unknown section parameter in `WikiPage::getSection()` ([#41])
* Fixed passing the return value in `WikiPage::setSection()` ([#30])
* Corrected call to `Wikimate::debugRequestsConfig()` ([#30])

### Version 0.10.0

#### Changed

* Switched to using the *Requests* library instead of Curl ([#25])

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

