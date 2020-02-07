# Changelog
All notable changes to this project will be documented in this file.

## [1.3.0] - tbd

 - internal optimizations
 - in public poll ask for username in a modal
 - Allow site user to vote in hidden poll via public link (#779)
 - New option: Allow admins to edit poll
 - Prevent deleted poll from beeing called via public link (#773)
 - Present error page, when poll cannot be accessed (#772)
 - Allow site user to access hidden poll, when called via public link (#779)
 - Give permant access to votes, the user voted in
 - New filter: participated - Polls, where the user participated in
 - delete comments (#193)
 - Enter user name in public shared polls in a modal
 - fixed routing error in NC16 (#787)
 - load subscription on route change (#788)
 - show displayNames instead of userID (#715)
 - reorder vote options in text polls (#529)

## [1.1.6] - 2020-01-26

 - autoFocus poll title in creation dialog (#757)
 - only count current user, if he actually voted (#759)
 - redirect old public polls routes to new route (#761)
 - Avoid error on creating share (#763)
 - changing popovermenu to Actions (#594 and #754 )
 - updated design according to file lists
 - Sorting polls in poll list, default creation date desc (#559 and #717)
 - updated dependencies
 - 12-hour clock bug bug (#780)

## [1.0.0] - 2020-01-20

 - huge update of polls
 - completely rewritten as a vue app
 - vote, edit poll in one page
 - instant persisting of votes and configuration
 - changed sharing

## [0.10.4] - 2019-11-27

  - Nextcloud 17

## [0.10.2] - 2019-03-13

  - #532 - cannot share poll (only share option)

## [0.10.1] - 2019-03-02

### Fixed

  - #528 - pull down on three-dot menu hidden for first participant

## [0.10.0] - 2019-02-24

### Added

  - main list page
    - rewrite as a vue app
    - improved UI
  - ability to clone any poll and shift date options (#323, #245)
  - design updates to vote page
  - some more UI enhancements
  - Maybe option for a poll is configurable

### Fixed

  - #82  - "user_" / "group_" prefix
  - #206 - User name is prefixed with user_, + incorrect translation
  - #461 - Polls with expire date could not be created/edited
  - #478 - Send comment bug
  - #479 - Not possible to vote for none of the options
  - #498 - "Create Poll" button disabled after failed validation
  - #507 - Fix query params in eventmapper
  - #511 - No difference between hidden and open poll

## [0.9.5] - 2018-12-22

### Fixed

  - #457 - update to 0.9.4 failed for postgres database
  - #454 - Update to 0.9.3 failed for postgresql database

## [0.9.4] - 2018-12-18

### Fixed

  - #453 - Polls upgrade leads to NotNullConstraintViolationException
  - #454 - Update to 0.9.3 failed for postgresql database
  - #455 - Fix color variable name in list.scss

## [0.9.3] - 2018-12-18

### Fixed
  - Fix minor problem with migration

## [0.9.1] - 2018-12-11

### Added
  - create/edit page
    - rewrite as a vue app
    - improved UI
	- introduced new NC date time picker from vue-nextcloud
	- introduced multiselect from vue-nextcloud
	- added option to allow "maybe" vote

  - vote page
	- made polls table scrollable
	- show new vote options after voting
    - open sidebar by default on wide screens
  - Users in the admin group should be able to edit polls (#386)

### Changed
  - Compatibility to NC 14 and 15
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
