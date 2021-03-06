# Changelog

## 1.1.0 - 2019-05-09
### Added
- Add quick post widgets support - thanks @ttempleton

### Changed
- check to see if plugin is enabled as well as installed - thanks @samuelbirch
- Convert instructions from varchar(255) to text - thanks @ttempleton

## 1.0.7 - 2019-04-03
### Fixed
- Fixed issue on multi-site Craft installations where applying a field label to a translatable field would remove the translatable icon

## 1.0.6 - 2019-04-03
### Added
- Added support for the project config rebuild functionality introduced in Craft CMS 3.1.20

### Changed
- Field Labels now requires Craft CMS 3.1.20 or later
- Changed the Field Labels instructions input to a textarea, for consistency with Craft's presentation of instructions on a field settings page

### Fixed
- Fixed issue where a Field Labels instruction could duplicate if the field instruction it replaced was multi-line

## 1.0.5 - 2019-03-29
### Added
- Added support for the Solspace Calendar plugin

## 1.0.4 - 2019-03-29
### Fixed
- Fixed error that occurred when attempting to create a new element from an element field's modal
- Fixed error that occurred if Craft Commerce was required by a Craft project but not installed

## 1.0.3 - 2019-03-28
### Fixed
- Fixed error when trying to perform a project config sync from a terminal
- Fixed potential error if a section has no entry types

## 1.0.2 - 2019-03-27
### Fixed
- Fixed issue with field labels not being applied to entry drafts

## 1.0.1 - 2019-03-26
### Fixed
- Fixed issue where `getIsGuest()` returned true even if user was signed in (thanks @aaronbushnell)
- Fixed issue with field labels not being applied to Commerce orders

## 1.0.0 - 2019-03-25
- Initial release for Craft 3
