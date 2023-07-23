# Changelog

This page lists the changes that were done in each version of Wikimate.

Since v0.10.0 this project adheres to [Semantic Versioning](http://semver.org/)
and [Keep a Changelog](http://keepachangelog.com/).

## Upcoming version

No changes yet.

## Version 1.1.0 - 2023-07-30

### Added

- Added internal mechanism to handle version-dependent parameters of API modules ([#151])
- Support version-dependent `deletetalk` parameter in `WikiPage::delete()` and `WikiFile::delete()` ([#152])
- New method `Wikimate::undelete()` ([#153], [#155])

### Changed

- Applied code formatting rules from [PSR-12](https://www.php-fig.org/psr/psr-12/) ([#142])
- Resolved static analysis warnings reported by [PHPStan](https://phpstan.org/) &
  [PHPMD](https://phpmd.org/) ([#143])
- Clarified error response for edits denied by a CAPTCHA ([#145])
- Activated default markdownlint rules ([#146])
- Updated dependency on rmccue/requests to Version 2.x (fixes PHP 8.1 deprecation warnings) ([#147])

### Fixed

- Fixed example not using correct array key for invalid login errors ([#148])
- Prevented warning in `Wikimate::logout()` about `token` parameter that was unsupported
  before MediaWiki v1.34 ([#151])
- Improved various code comments ([#149])
- Improved more PHPDoc comments ([#150])

## Version 1.0.0 - 2021-09-05

### Added

- New exception class `WikimateException` for API communication errors ([#136])
- Usage documentation about maximum lag and retries ([#134])
- New GitHub Action to enforce updates to `CHANGELOG.md` ([#131])
- New `CONTRIBUTING.md` file with contribution guidelines ([#135])
- New GitHub Action to check markdown files ([#138])

### Changed

- Centralized API communication checks in `WikiPage::request()` ([#136])
- Centralized debug logging of API requests/responses in `WikiPage::request()` ([#139])
- Rewrote installation and invocation instructions using Composer ([#140])
- Added additional context to `README.md` ([#127])
- Added semi-linear merge recommendation to `GOVERNANCE.md` ([#130])

_The following two entries are backwards incompatible API changes
and may require changes in applications that invoke these methods:_

- Error return values for `WikiPage::getSection()` changed from `false` to `null` ([#129])
- `Wikimate::login()` error code `'login'` is now `'auth'`, also used by `logout()` ([#132])

### Fixed

- Fixed one error return value in `WikiPage::setText()` ([#129])
- Fixed exception type/message for `$keyNames` parameter to `WikiPage::getAllSections()` ([#133])

### Removed

- Method `Wikimate::debugCurlConfig()`, deprecated since v0.10.0 ([#128])
- File `globals.php`, replaced by expanded Composer instructions ([#140])

## Version 0.15.0 - 2021-08-26

### Added

- New methods `WikiFile::revert()` and `Wikimate::filerevert()` ([#123])
- New method `Wikimate::logout()` ([#124])
- Added post-release update steps to `GOVERNANCE.md` ([#125])

### Changed

- Updated `Wikimate::token()` to remember CSRF token and reduce API calls ([#122])

### Fixed

- Fixed format of user agent string ([#121])

## Version 0.14.0 - 2021-08-24

### Added

- Support for the maxlag parameter (with retries) in API requests ([#112])
- Support for getting/setting user agent for API requests ([#107])
- Added missing PHPDoc comments for properties, constants, and more ([#109])

### Changed

- Changed API requests from deprecated PHP format to JSON format ([#111])
- Grouped sections and added table of contents in `USAGE.md` ([#108])

### Fixed

- Removed null returns from destructors & fixed PHPDoc comments ([#114])
- Fixed sections object initialization warning in PHP 7.4+ ([#118])

## Version 0.13.0 - 2021-07-05

### Added

- Added more debug logging of MediaWiki requests and responses ([#101], [#106])
- New `GOVERNANCE.md` file to explicitly codify the project management principles
  and provide guidelines for maintenance tasks ([#83], [#105])

### Changed

- Modernized token handling for login and data-modifying actions.
  Requires MediaWiki v1.27 or newer. ([#100], [#106])

### Fixed

- Prevented PHP notice in `WikiFile::getInfo()` for moved or deleted file ([#85])
- Fixed capitalization of a built-in PHP class in a comment ([#106])

## Version 0.12.0 - 2017-02-03

### Added

- New class WikiFile to retrieve properties of a file, and download and upload its contents.
  All properties pertain to the current revision of the file, or a specific older revision.
  ([#69], [#71], [#78], [#80])
- WikiFile also provides the file history
  and the ability to delete a file or an older revision of it ([#76])

## Version 0.11.0 - 2016-11-16

### Added

- Support for a section name (in addition to an index)
  in `WikiPage::setText()` and `WikiPage::setSection()` ([#45])
- Support for optional domain at authentication ([#28])

### Changed

- Updated `WikiPage::getSection()` to include subsections by default;
  disabling the new `$includeSubsections` option reverts to the old behavior
  of returning only the text until the first subsection ([#55])
- Improved section processing in `WikiPage::getText()` ([#33], [#37], [#50])
- Ensured that MediaWiki API error responses appear directly in `WikiPage::$error`
  rather than a nested 'error' array.
  This may require changes in your application's error handling ([#63])
- Restructured and improved documentation ([#32], [#34], [#47], [#49], [#61])

### Fixed

- Ensured use of Wikimate user agent by _Requests_ library ([#64])
- Corrected handling an invalid page title ([#57])
- Fixed returning an empty section without header in `WikiPage::getSection()` ([#52])
- Prevented PHP Notices in several methods ([#43], [#67])
- Corrected handling an unknown section parameter in `WikiPage::getSection()` ([#41])
- Fixed passing the return value in `WikiPage::setSection()` ([#30])
- Corrected call to `Wikimate::debugRequestsConfig()` ([#30])

## Version 0.10.0 - 2014-06-24

### Changed

- Switched to using the _Requests_ library instead of Curl ([#25])

## Version 0.9 - 2014-06-13

- Bumped version for stable release

## Version 0.5 - 2011-09-09

- Removed the use of constants in favour of constructor arguments
- Added checks that throw an exception if can't write to wikimate_cookie.txt
- Throws exception if curl library not loaded
- Throws exception if can't login

## Version 0.4 - 2011-01-15

- Added `WikiPage::newSection()` and `WikiPage::setSection()` (shortcuts to `WikiPage::setText()`)
- Added the ability to get individual sections of the article with `WikiPage::getSection()`
- Added the ability to get all sections in an array with `WikiPage::getAllSections()`
- Added the ability to get an array showing section offsets and lengths in the page wikicode
  with `WikiPage::getSectionOffsets()`
- Added the ability to see how many sections are on a page with `WikiPage::getNumSections()`

## Version 0.3 - 2010-12-26

- Initial commit

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
[#105]: https://github.com/hamstar/Wikimate/pull/105
[#106]: https://github.com/hamstar/Wikimate/pull/106
[#107]: https://github.com/hamstar/Wikimate/pull/107
[#108]: https://github.com/hamstar/Wikimate/pull/108
[#109]: https://github.com/hamstar/Wikimate/pull/109
[#111]: https://github.com/hamstar/Wikimate/pull/111
[#112]: https://github.com/hamstar/Wikimate/pull/112
[#114]: https://github.com/hamstar/Wikimate/pull/114
[#118]: https://github.com/hamstar/Wikimate/pull/118
[#121]: https://github.com/hamstar/Wikimate/pull/121
[#122]: https://github.com/hamstar/Wikimate/pull/122
[#123]: https://github.com/hamstar/Wikimate/pull/123
[#124]: https://github.com/hamstar/Wikimate/pull/124
[#125]: https://github.com/hamstar/Wikimate/pull/125
[#127]: https://github.com/hamstar/Wikimate/pull/127
[#128]: https://github.com/hamstar/Wikimate/pull/128
[#129]: https://github.com/hamstar/Wikimate/pull/129
[#130]: https://github.com/hamstar/Wikimate/pull/130
[#131]: https://github.com/hamstar/Wikimate/pull/131
[#132]: https://github.com/hamstar/Wikimate/pull/132
[#133]: https://github.com/hamstar/Wikimate/pull/133
[#134]: https://github.com/hamstar/Wikimate/pull/134
[#135]: https://github.com/hamstar/Wikimate/pull/135
[#136]: https://github.com/hamstar/Wikimate/pull/136
[#138]: https://github.com/hamstar/Wikimate/pull/138
[#139]: https://github.com/hamstar/Wikimate/pull/139
[#140]: https://github.com/hamstar/Wikimate/pull/140
[#142]: https://github.com/hamstar/Wikimate/pull/142
[#143]: https://github.com/hamstar/Wikimate/pull/143
[#145]: https://github.com/hamstar/Wikimate/pull/145
[#146]: https://github.com/hamstar/Wikimate/pull/146
[#147]: https://github.com/hamstar/Wikimate/pull/147
[#148]: https://github.com/hamstar/Wikimate/pull/148
[#149]: https://github.com/hamstar/Wikimate/pull/149
[#150]: https://github.com/hamstar/Wikimate/pull/150
[#151]: https://github.com/hamstar/Wikimate/pull/151
[#152]: https://github.com/hamstar/Wikimate/pull/152
[#153]: https://github.com/hamstar/Wikimate/pull/153
[#155]: https://github.com/hamstar/Wikimate/pull/155
