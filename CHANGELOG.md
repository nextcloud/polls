# Changelog
All notable changes to this project will be documented in this file.

## [1.7.2 - beta3] - tbd
 - [fix] #1344 - prevent commenting, when entering public poll without registration  
 - [fix] #1338 - Support dark mode and dark theme

## [1.7.1 - beta2] - 2021-01-12
 - [fix] #1325 - There are no spaces in the column name  
 - [fix] #1326 - Invalid string text in the email
 - [enhancement] #739 - Limit number of participant per option
 - [enhancement] #738 - Limit numer of votes per participant (also #647, #624)
 - [dependencies] Updated dependencies
 - [refactoring] Mainly code meintenance and optimizations, bug fixes

## [1.7.0 - beta1] - 2021-01-02
 - [enhancement] #188 use notification app for invitations
 - [enhancement] #907 reload current poll every 30 seconds
 - [enhancement] #924 admin users can delete and takeover polls from other users via new admin section
 - [enhancement] #881 respect autocompletion limitations from share settings for users, group and circle searches
 - [gui] public polls - combine registration dialogs into one dialog
 - [gui] polls overview changed display of expiration timespan
 - [fix] #433, #856 avoid duplicates in different tables
 - [fix] #1252 - External user is not listed in admin's shares list
 - [fix] #1183 - Avoid concurrent long term user searches with a big user base
 - [fix] #1181 - Speed up poll overview, by avoiding unnecessary loading of polls, the user is not allowed to see

 See also https://github.com/nextcloud/polls/milestone/31?closed=1

## [1.6.2] - 2020-11-19
 - [fix] Subscription was missing for logged in users

## [1.6.1] - 2020-11-17
 - [fix] #1244 preferences write error
 - [fix] a few minor glitches and fixes

## [1.6.0 - RC1] - 2020-11-01
 - [fix] some design fixes
 - [fix] #1205 External users get internal link in notification mail
 - [enhancement] Configure calendars for calendar lookup
 - [enhancement] Change wording on hidden an public polls (#1158)
 - [enhancement] #1168 Preferences dialog (#1120)
 - [enhancement] #1156 Explicitly close poll (#1157)
 - [enhancement] #1153 Add share, if logged in user enters hidden poll via public link (#1169)
 - [enhancement] #204 Circles integration (#1128)
 - [refactor] Remove deprecated app.php (#1162)
 - [refactor] Separate assets
 - [deps] updated dependencies

 See also: https://github.com/nextcloud/polls/milestone/28?closed=1

## [1.5.7 - bugfix release] - 2020-10-25
 - [fix] #1190 #1191 explicit convert boolean values to intger to avoid db conflicts (another aproach)

## [1.5.6 - bugfix release] - 2020-10-23
 - [fix] #1190 #1191 explicit convert boolean values to intger to avoid db conflicts

## [1.5.5 - bugfix release] - 2020-10-20
 - [fix] #1137 delete comments in public polls
 - [fix] #1161 Routing after creation of poll was wrong
 - [fix] #1154 Shifting dates went wrong
 - [fix] #1163 Reordering text poll options did not work
 - [fix] #1170 A non-numeric value encountered
 - [deps] updated dependencies

## [1.5.4 - release] - 2020-10-02
 - adding missing translations
 - release blocking fixes

## [1.5.3 - RC3] - 2020-09-21
 - Added setting for defining default view for
 - text polls, defaults to mobile/list layout and
 - date polls, defaults to desktop/table layout #744
 - better UX for site users, which enter a poll via public link and could login #1096

## [1.5.2 - RC2] - 2020-09-15
 - fixing Translations
 - updated dependencies
 - minor fixes

## [1.5.1 - RC1] - 2020-09-08
 - Lookup calendars for conflict #1056 #747
 - convert URIs in description into clickable links #1067
 - added a poll to force poll appear under relevant polls navigation entry for all users #1072
 - move cloning of options to backend #1058
 - add user settings
 - some style fixes
 - updated dependencies
 - load app icons via url-loader

## [1.5.0 - beta1] - 2020-08-17
 - Drop support for Nextcloud 16
 - Stop immediatley sending of invitation mails after adding a share #1007 #935
 - Fix: Hide usernames in notification mail, if results in poll are hidden #990 #980
 - Adding a REST-API #966
 - Exclude disbled users from shares #1008 #997
 - Exclude mails to disabled users in group invitations #960
 - Fix with adding empty dates #961 #958
 - Changed misleading prompt for username in public polls #956 #938
 - Raised minute step to 5 minutes in datepicker #963 #957
 - Changed some icons #862
 - Added the ability to confirm options #939 #136
 - A lot of refactoring
 - Don't invite disabled users #997
 - add time zone info to date polls #1076


## [1.4.3] - 2020-05-03
 - fix #909
 - fix #910
 - add description to invitation mail #895
 - fixed safari bugs #770
 - added configuraton to hide the poll result #265
 - poll title to window title #318
 - updated timepicker and changed layout #889, #826
 - NC 19 compatibility
 - Delete a poll completly #801
 - Alignment in poll list #828
 - Anonymous poll not saved #829
 - wrong z-index on datepicker #830
 - Missing translations for Shift all date options #809
 - Pinned navigationItem "Deleted polls" #843
 - changed vote icons #844
 - Autofocus and cursor pointer #827
 - Button style #848
 - Remove Participants from vote #736
 - Add login link in public votes #857
 - fix date sorting #877
 - overwork navigation filters #865
 - added filter for expired polls #878
 - share poll via email #822
 - fix notification mail contains user names in anonymous polls #871
 - fix double loading of poll list #870
 - allow creating of option sequences for date polls #649
 - permanently delete polls #823
 - fix some design / UX improvements #841, #848, #884,

## [1.3.0] - 2020-02-16

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
