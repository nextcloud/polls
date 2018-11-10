# Changelog

All notable changes to this project will be documented in this file.

## [0.9.0] - eta Nov 2018

### Added
  - create/edit page
    - rewrite as a vue app
    - improved UI
	- introduced new NC date time picker from vue-nextcloud
	- introduced multiselect from vue-nextcloud
	- added option to forbid "maybe" vote

  - vote page
	- made polls table scrollable
	- show new vote options after voting
    - open sidebar by default on wide screens
  - Users in the admin group should be able to edit polls (#386)

### Changed
  - Compatibility to NC 14
  - Introduced vue
  - Changing database theme
  - Polls is a Nextcloud only app now. If you wish to proceed developing the ownCloud version, make a fork from the `stable-0.8` branch.

### Fixed
 - 'Edit poll' did not work from poll's details view (#294)
 - Bug which makes voting impossible after edit
 - Write escapes option texts to db (#341)
 - display user's display name instead of user name (#402)
 - support for asynchronus operations (#371)
 - ... a lot more minor bugs

See https://github.com/nextcloud/polls/milestone/9?closed=1 for all changes and additions.

## [0.8.3] - 2018-08-30

### Added

### Changed

### Fixed
 - Display own participation in polls in list view

## [0.8.2] - 2018-08-25

### Added
 - Compatibility to NC 14 #360

### Changed

### Fixed
 - 'Edit poll' did not work from poll's details view #294
 - Reload of public polls with ownCloud 10 #344 #340 #283 #96

## [0.8.1] - 2018-01-19

### Added
 - Unit tests
 - App favicon
 - More languages

### Changed
 - New vote page design (responsive)
 - New comment design
 - A lot of clean up
 - removing header elements for public polls

### Fixed
 - Linebreak bug
 - Time picker bug (update to version 2.5.14, https://github.com/xdan/datetimepicker)
 - Server error, if poll does not exist
 - Several CSS fixes for NC 11 and oC 10

## [0.8.0] - 2017-10-13

### Changed
 - Big UI overhaul
 - Removed oC branding from email strings
 - Removed unnecessary files
 - A lot of code rework

### Fixed
 - Fix date display in IE and Safari (NaN)
 - Translations

## [0.7.3] - 2017-07-16

### Added
- French translations
- Nextcloud 12 compatibility

### Changed
- Removed some deprecated methods
- Hide usernames in extended anonymous polls

## [0.7.2] - 2016-10-27

### Added
- Search for users / groups in "Select..." access type (similar to sharing dialog) (thanks @scroom)
- Bump OC version to 9.1
- Anonymous comments / polls
- Allow comments for unregistered / not logged in users

### Fixed
- Correctly store text votes (thanks @jaeger-sb @joergmschulz)
- Preselection on edit poll page
- Current selected access type is now clickable
- Remove unused share manager

## [0.7.1] - 2016-06-05

### Added
- New UI (thanks @mcorteel)
- Search for users / groups (thanks @bodo1987)

### Fixed
- Several bug fixes
- Use correct timezone for date polls
- Link to poll
- Only display users / groups the user is member of (except admin) (thanks @bodo1987)

## [0.7.0] - 2016-03-18

### Added
- Show user avatars
- Toggle all switch
- Show login screen before error

### Fixed
- Not set expire would lead to 2.1.1970 as expire date
- Invalid characters in url hash
- Empty description in edit
- Many text poll fixes
- Notification checkbox fixes
- Blank page fixes on empty votes

## [0.6.9.1] - 2016-02-21

### Fixed
- Replaced placeholder images
- Minor fixes, including external votes

## [0.6.9] - 2016-02-20

### Added
- Edit polls

### Changed
- New minimal version set to 8.1

### Fixed
- Replaced deprecated methods
- Switched from raw php to controller
- Fixed several bugs
	- Edit poll access
	- Vote page layout
