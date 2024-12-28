<!--
  - SPDX-FileCopyrightText: 2017 Nextcloud contributors
  - SPDX-License-Identifier: CC0-1.0
-->
# Changelog
##
## [7.2.8] - 2024-12-28
### Fix
 - fix limit check

## [7.2.6] - 2024-12-24
### Fix
 - fix date picker poositioning by updating to nextcloud/vue-components@8.22.0

## [7.2.5] - 2024-11-23
### Fix
 - avoid error message due to new core check of column name length
 - fix user search did not display subnames

## [7.2.4] - 2024-09-26
### Fix
 - fix vote limit checks for public users
 - fix access to public polls email and contact shares
 - fix placeholder translations of email input of the register dialog

## [7.2.3] - 2024-09-12
### Fix
 -  fix size of creation box in navigation

## [7.2.2] - 2024-09-06
### Fix
 -  fix watcher in situations it may fail on pollId 0
 -  fix failing routes on tokens with trailing spaces
 -  Removed index removal from the pre-migration repair steps

## [7.2.1] - 2024-08-22
### Fix
 -  Fix deleted user when email share registers

## [7.2.0] - 2024-08-01
### Changes
 - Add Nextcloud 30

## [7.1.4] - 2024-07-15
### Fix
 - Fix autoreminder again
 - Fix acticities display of circles
 - remove colons from exported file names

## [7.1.3] - 2024-06-30
### Fix
 - Fix autoreminder

## [7.1.2] - 2024-06-24
### Fix
 - Fix owner detection (prevented deleting comments by poll owners)
 - Fix exporting of polls
 - Fix poll loading on some MySQL configurations
 - Fix context menu in polls list

## [7.1.1] - 2024-06-10
### Fix
 - Fix opening and closing of sidebar after changed component
 - try avoiding update error by removing class registering
## Change
 - Support Nextcloud 27

## [7.1.0] - 2024-06-09
###  !!! changed API structure, please refer to the documentation
### Fix
 - Fixed counting of orphaned votes
 - Disable registration button while registration is pending
 - Disable "resolve group share" while resolving
 - Fix showing booked up options in polls with hidden results
### Changes
 - Mainly performance improvements
 - Changed API structure for polls, please refer to the documentation
### Performance
 - Added an option to allow to add polls to the navigation (default)
 - Limited polls inside the navigation to 6 items
 - Render the polls list in chunks of 20 items

## [7.0.3] - 2024-04-05
### Fix
 - Archive, restore and delete polls in poll list was missing, braught the options back to the action menu
 - fix a situation, where votes of a non existing poll are requested
 - Fix getting group members
### New
 - Added an endpoint to the Api to be able to fetch the acl of a poll

## [7.0.2] - 2024-03-29
### Fix
 - Combo view was not usable

## [7.0.1] - 2024-03-29
### Fix
 - Fix database error with PostgreSQL
 - Fix public poll access

## [7.0.0] - 2024-03-27
### Changes
 - Support for Nextcloud 29
 - Removed PHP 8.0 Support
 - Performance optimizations
 - A lot for code maintenance and tidy

## [6.3.0] - 2024-05-06
### Fix
 - Fix preventing option suggestions
 - Fixing some performance issues
 - Fixing an error that possibly prevents users from adding suggestions
### Changes
#### Changes are partially also included in 7.1.0
 - Added an option to allow to add polls to the navigation (default)
 - Limited polls inside the navigation to 6 items
 - Render the polls list in chunks of 20 items
 - Support Nextcloud 26 to 28

## [6.2.0] - 2024-03-27
### Fix
 - Fix preventing option suggestions

## [6.1.6] - 2024-02-27
### Fix
 - Fixing vanishing votes after shifting date options or creating sequences

## [6.1.5] - 2024-02-24
### Fix
 - Fixing select error

## [6.1.4] - 2024-02-24
### Fix
 - Fixing 404 error when using public share where the poll has hidden results
 - Partially fix email shares
 - fix user name check for public participants

## [6.1.3] - 2024-02-21
### Fix
 - Fixing bug, when an internal user tries to enter a poll using a public share a second time
 - Fix error message of watchpoll, trying to access pollId 0

## [6.1.1] - 2024-02-16
### Changes
 - Consolidated migration to avoid double database validation

## [6.1.0] - 2024-02-16

#### This minor version contains a huge change in the internal user and Access management.
This is a step further to a public poll creation. The next major version will be a long time necessary technical migration to the current Vue 3 framework.

So this 6.x branch will get only bug fixes and compatibility updates. Any other featues are scheduled after the migration.

### Changes
 - Only Nextcloud 28 and up
 - Moved action buttons to action menu in sidebar lists of options and shares
 - rewritten internal user base
 - optimized access management
 - optimized privacy and anonymity in anonymous  and public polls
 ### New
 - Removed deletion timer of shares, options and comments with better undelete
 ### Fixes
 - Fix locked users, when registering with nextcloud user to a public poll
 - Fixed typo which caused unnecessary error logging
 - Fixed export of html files
 - Fixed non available action buttons of options in mobile view
 - Fixed calendar check
 - Fixed some minor activity issues
 - Fixed autoreminder could not be set
 - Fixed migration error which could cause data loss (when comming from 3.x)

## [6.0.1] - 2023-12-10
### Fixes
 - Some minor fixes regarding user apperances

## [6.0.0] - 2023-12-09
### Changes
 - Only Nextcloud 28 and up
### Fixes
 - Anonymize poll proposal owner in case of hidden results

## [5.4.2] - 2023-11-11
### Fixes
 - Fixed table definition

## [5.4.1] - 2023-10-31
### Fixes
 - Fixed 7 ERROR: column reference "poll_id" is ambiguous

## [5.4.0] - 2023-10-28
### Fixes
 - Fixed granting admin rights to shares
 - Fixed a bug which  prevented poll exports
 - Fixed a visually bug when using Nextcloud's Dark Mode
 - Fixed result reporting about sent and failed confirmation mails
### New
 - Reveal hidden voters if hidden in case of performance concerns
 - Support better readability of vote page
 - Added locking of shares
 - Shares can now be locked which works as a read only share mechanism. Locked shares can still enter the poll, but every interaction (voting and commenting) is disabled.
 - Deletion of locked shares deletes the users votes as well
 - Moved request for option proposals to a card on top of the vote page
 - Moved CTA for confirmation mails to card on top of the vote page
 - Added a card with a more prominent hint for closed polls
 - Changed user flow on public registration. When entering a public poll, the registration dialog does not pop up automatically anymore. A CTA has been added to open the registration
 - Slighly changed vote colors by adopting the cores color scheme
### Changes
 - Improved username check for public polls with a large number of groups in the backend

## [5.3.2] - 2023-09-11
### Fixes
 - Fix migration error ("poll_option_hash" is NotNull)

## [5.3.1] - 2023-09-09
### Fixes
 - Fix creating public shares

## [5.3.0] - 2023-09-06
### New
 - Add label to public shares
 - Send all unsent invitations to a poll with one click (resolves contact groups and circles too)
### Fixes
 - Fix API calls
 - Deleting comments in public polls was broken
### Changes
 - Refactorings and code maintenance
 - dependency updates

## [5.2.0] - 2023-07-15
### Fixes
 - Fix date shifting and sequences when crossing daylight saving change
 - Bring back notifications
 - fix notification subscription
 - Eliminate preferences error logging on first time usage
### Changes
 - Set default view to table layout even for text polls, list layout is still the default layout when in mobile mode

## [5.1.0] - 2023-06-27
### Changes
 - Added user option to remove outdated polls from th "Relevant" list
 - Added alternative vote page design as beta option
### Fixes
 - Poll export broken under some circumstances
 - Fixed repair steps
 - Added a workaround and debugging logs regarding LDAP
 - Fixed conflict detection for poll options against user's calendar
 - PostgeSQL Compatibility
### Misc
- support PHP 8.2
- Replace dropdown elements (NcSelect over NcMultiSelect)
- Replace vue-richtext by NcRichText
- minor updates caused by depencies

## [5.0.5] - 2023-05-07
### Fix
 - show unprocessed share invitations
 - fix bulk adding of options
 - fixed update problems
### Changes
 - change warning design (use NcNoteCard)
 - Support NC 27

# [5.0.4] - 2023-04-25
### Fix
 - Ensure duplicate removal after migration and in repair command
 - Fix notification exception for nullish log entries (fix was not pushed to 5.0.3)

## [5.0.3] - 2023-04-20
### Fix
 - Fix notification exception for nullish log entries

## [5.0.2] - 2023-04-17
### Fix
 - Fix crash with shares which have nullish mail addresses

## [5.0.1] - 2023-04-13
### Fix
 - Polls cannot be edited when user has no mail address

## [5.0.0] - 2023-04-07
### New
 - Added qr code for public shares
### Changes
 - PHP 8.0 as minimum requirement
 - Shorten public tokens to 8 characters (lower and upper characters and digits)

## [4.1.8] - 2023-03-03
### Fix
 - Fix Error on poll creation `General error: 1364 The field 'description' has no default value.`

## [4.1.7] - 2023-03-02
### Fix
 - Fix invitation mails for guest users

## [4.1.6] - 2023-02-27
### Fix
 - Removed trailing comma in rebuild command## [4.1.5] - 2023-02-25
### Fix
 - Fix disappeared option add button after change in the nextcloud-vue lib
 ### Changes
 - Changed option owner column to notnull

## [4.1.4] - 2023-02-23
### Fix
 - Fix infinite updates call, if no polling type for watches were set (avoid server spamming) (v4.1.3)
 - Fix migrations and repair steps (v4.1.3)
 - Fix MySQL error 1071 Specified key was too long;
 ### changes
 - Change default of life update mechanism to manual updates instead of long polling (v4.1.3)
 - Added Nextcloud 26

## [4.1.2] - 2023-01-23
### Fix
 - Invitations are not send out if poll has no description (fix 2)

## [4.1.1] - 2023-01-19
### Fix
 - Invitations are not send out if poll has no description

## [4.1.0] - 2023-01-17
### New
- Added a dashboard widget for relevant polls
- Improved registration dialog for public polls
- Small design change to vote page according to new nextcloud design
### Fix
 - Reset own votes as a logged in user without admin rights
 - Error was thrown, when a owner of an option was null
 - Deleted shares prevented poll export and
 - avoid timestamp overflow for dates greater than 01-19-2038
 - Increase length of option texts from 256 to 1024 characters
 - fix access validation checks
 - avoid timestamp overflow with dates past 2038/01/19 (Timestamp 2147483647)
### Misc
- Refactoring of API requests to a central http API
- Refactoring and fixes to background watcher
- Accelerated installation and updates

## [4.0.0] - 2022-10-13
### New
- Support Nextcloud version 25
### Misc
- Experimental designs have been removed

## [3.8.4] - 2022-12-18
### Fix
 - Reset own votes as a logged in user without admin rights
 - Error was thrown, when a owner of an option was null

## [3.8.3] - 2022-10-24
### Fix
 - Fix poll export containing participants with deleted shares

## [3.8.2] - 2022-09-27
### Fix
 - fix a bug, which prevents voting in a public vote, when comments are disabled.
 - suppress annoying error log entries with PHP 8.1

## [3.8.0] - 2022-09-18
### New
- Support Nextcloud version 22 - 24
- Convert links in comments to clickable links
- Allow public users to logout from a poll, when logged in via cookie
- Allow public users to change their name after registration to a public poll
- Allow bulk poll ownership transfer for admins
- Added option to send mails about confirmed options

### Fix
 - Unsubscribing from a public poll was not possible
 - Use display name for avatar of the current public user instead of user id
 - Fix export, if owner did not vote in the poll
 - Fix adding option, when not admin (bulk operation)

## [3.7.0] - 2022-06-24
### New
- User setting for conflict check (hours before and after an option to search for conflicts)
- Add admin option to prevent email address exposing of internal users

### Fix
- Poll export, if the owner did not vote
- Poll export was broken, when certain characters were present in the poll title
- Handling of recurring calendar events (NC24)
- Removed error message in log triggered from user search when adding share
- Fixed calendar conflict search for recurring events (NC24)
- Personal public shares got intinite redirected

### Misc
- Switch to new calendar API (NC24)
- repaces icons with material design icons
- generate a unique user id for public users
- Less noise in the registration dialog

## [3.7.0-beta5] - 2022-06-05
### Fix
- Translations
- legal links

### Changes
- Changed apperance of registration modal
- Improvement of InputDiv component

## [3.7.0-beta4] - 2022-05-29
### Fix
- Poll export was broken, when certain characters were present in the poll title
- Removed error message in log triggered from user search when adding share

### Misc
- Replaced icons with material design icons
- Generate a unique user id for public users

## [3.7.0-beta3] - 2022-05-06
### New
- User setting for conflict check (set hours before and after an option to search for conflicts)
### Fix
- Poll export, if the owner did not vote
- Calendar conflict check (NC24)
- Handling of recurring calendar events (NC24)

### Misc
- Switch to new calendar API (NC24)

## [3.7.0-beta2] - 2022-04-27
### Fix
- Fixed syntax error in class AppSettings

## [3.7.0-beta1] - 2022-04-27
### New
- #2392 - Add admin option to prevent email address exposing of internal users

## [3.6.1] - 2022-04-23
### New
- Compatibility to Nextcloud 24
- Renaming: **hidden polls** are now **private polls** and **public polls** are called **open polls** to distinguish them from **real public polls** via public links
- Added configuration options for admins to add legal terms to the public registration dialog and emails
- Added the possibility for admins to add a disclaimer text to generated emails
- Added email addresses for owner's poll export
- Allow email share adding using common email formats with name
- Input fields now support matching keyboards on mobiles
- Grouping comments for less noise
- Bulk import for text polls
- Save username of a public poll (using cookie)
- Changed icon set
- some more design changes

### Fixed
- Poll export to spreadsheeds was fixed if Poll title is longer than 31 characters
- Fix LDAP user search
- Poll list in admin page should not link to a poll
- Remove markup in text only emails

## [3.6.0-rc1] - 2022-04-16
### New
- #2367 - Allow email share adding using common email formats with name (#2375)
### Changes
- #2377 - Changed transitions on vote vlicks and add hover state
## [3.6.0-beta2] - 2022-04-13
### New
- #2373 - Add icon symbol for locked vote options
- #2351 - Store username in a public poll to cookie

### Fixed
- #2374 - Avoid unnecessary error logs in activities
- #2369 - Fix missing icons after dep update
- #2357 - Fix styling bugs
- #misc - Fixed different translation errors

## [3.6.0-beta1] - 2022-04-02
### Changes
- #2255 - Rename "hidden" polls to "private" polls, "public" to "open" (#2289)
- #2328 - Migrate access strings to 'private' and 'open' (instead of 'hidden' and 'public')

### New
- #2261 - Added the option to add links to terms and private policy to public registration dialog
- #2260 - Added an option to add legal terms and a disclaimer to emails
- #2177 - Add email addresses to poll export (#2327)

### Fixed
- #1310 - Fix LDAP search (#2323)
- #2285 - Fixed poll export (#2286, #2287)
- #2312 - Fixed heights of modals after update of @nextcloud/vue@5
- #2306 - HTML Tags in plain Poll invitation (#2346)
- #2254 - Links in admin page could lead to non accessible poll (#2326)

### Misc
- #2283 - Added support for inputmode
- #2311 - Added support for material design icons to some components (#2329)
- #2332 - Replace deprecated String.prototype.substr()
- #2329 - Styling inpuDiv

## [3.5.4] - 2022-02-17
### Fixed
- #2276 - Deletion of NC users was broken through polls (#2279)
- #2270 - Translation error

## [3.5.3] - 2022-02-15
### Changed
- #2264 - add email address if valid search parameter (#2268)

### Fixed
- #2263 - Fixed user search (#2267)
- #2272 - Fixed poll export due to changed module export of xlsx

### Misc
- late translations delivery

## [3.5.2] - 2022-02-11
### Fixed
- #2248 - Adding options in text poll is not possible

## [3.5.1] - 2022-02-11
### Fixed
- #2246 - updated php minimum version in info.xml

## [3.5.0] - 2022-02-09
### New
- following new features are disabled by default per admin switch
    - Export polls (.xlsx, odt, .csv, .html)
    - Track activities
    - Combine multiple polls in one view (read only)
- Add polls to collections
- Linkify URLs and email addresses in text options
- New command `occ polls:db:recreate` for validating and fixing db structure

### Fixed
- It was possible to add option proposals, when not registered in public polls
- A deleted poll could cause repeating error logs on notifications
- fixed a migration error, when updating from rather old version

## [3.5.0-beta3] - 2022-02-01
- Code optimization and refactoring
- #2201 - Migration error (#2199, #2222)

### [3.5.0-beta2] - 2022-01-23
- [new] #950 - Allow join project / collection (#2194)
- [new] #2204 - Add `occ polls:db:recreate` for validating and fixing db structure

### [3.5.0-beta1] - 2022-01-18
- [new] #366 - Export poll (#1942, #2169)
- [new] #804 - Use activities (#2154)
- [new] #1986 - Combined view for date polls (#2175)
- [new] #2102 - Linkify options (#1709, #2190)
- [fix] #2147 - Adding proposals is possible without registering (#2163)
- [fix] #2133 - Notifications error with deleted polls(#2178)

## [3.4.2] - 2021-12-13
### New
- Added an indicator for shares, which indicates, if a share already voted or not
- Added an autoreminder
- Added a hint, if no one except the poll owner can access the poll
- Added an admin setting to change the updates polling behavior (Disabled, periodically or instant via long-polling)

### Changed
- Compatible with Nextcloud 21 - 23
- Share tab redesign
  - Moved the 'all users' access setting as switch to the shares list
  - Polls with access for all users are now automatically relevant for all users
  - Removed the settings to 'access all admins' edit rights (in favor for granting individual edit rights, introduced in Polls 3.2).
    An existing setting will still be valid and can be removed
  - Combine all shares into one list
  - Registration options for public polls are now configurable per public share.
    An existing setting from the poll configuration will be used as default

### Fixed
- Fixed auto archiver, to prevent to archive polls without an expiration date
- fix error when adding option #2126 (v3.4.1)
- fix missing anonymization of proposal owners in anonymous polls #2136 (v3.4.2)
- fix testing of email address constraints for public poll registration #2137 (v3.4.2)

## [3.4.0-beta1] - 2021-11-26
- [compatibility] - Compatible with Nextcloud 23
- [change] #2076 - Share tab redesign

## [3.4.0-alpha1] - 2021-11-02
- [new] #1305 - Added participation indicator in effective shares list (#2037)
- [new] #656 - Add autoreminder job (#2039)
- [change] #2038 - validate token in router and reroute before entering public page
- [fix] #2055 - do not archive polls without expiration automatically
- [change] #2029 - Configure update polling (#2060)

## [3.3.0] - 2021-10-10
- Added email addresses to external shares in the shares tab for the owner
- Adopt dashboard design in personal app settings and improved individual styling (still experimental)
- Fixed calculation of full day events, which could break the display on daylight changing days

## [3.3.0-rc1] - 2021-10-03
- [new] #1943 - Show email address in share list external users (#2001)
- [fix] #1981 - Changed calculation of full day events (#2004)
- [new] #1985 - Adopt dashboard design (still experimental)
- dependency updates, refactoring and code maintenance

## [3.2.0] - 2021-09-19
- Poll administration can now be delegated to invited users
- New admin section for polls (/settings/admin/polls-admin)
  - Disable login option in registration screen of public polls
  - Auto archive closed polls after configured days
  - Restrict poll creation to groups
  - Restrict public poll creation to groups
  - Restrict creation of polls with all users access to groups

## [3.2.0-rc2] - 2021-09-14
- [fix] #1958 - Fix DB setting for oracle
- [fix] #1958 - App failed, if app config was not set
- [new] #1960 - Delegate poll administration to invitees (#1095)

## [3.2.0-rc1] - 2021-09-12
- [new] #1948 - configure email registration in public polls (#1419, #1728)
- [new] #1950 - allow users to reset their votes (#1578)
- [fix] #1937 - User search broke, when a user has no mail address configured
- [change] #1953 - remove three character validation for public user names (#1952)

### New admin section
- [new] #1919 - Admin section for polls
- [new] #1936 - [Admin] disable login option in public polls (#1518)
- [new] #1938 - [Admin] auto archive closed polls (#526)
- [new] #1106 - [Admin] Restrict poll creation to groups
- [new] #481  - [Admin] Restrict public poll creation to groups
- [new] #658  - [Admin] Restrict creation of polls for all users to groups

## [3.1.0] - 2021-08-21
- GUI optimizations
- Hide internal user IDs in public polls
- Fixed migration error
- Fixed registration dialog on mobiles
- Fixed width of share icons
- some minor fixes

## [3.1.0-rc1] - 2021-08-16
## Only available for Nextcloud 21/22
- [ui] #1831 - visual fixes to polls list
- [ui] #1891 - Updated vote view
- [fix] #1858 - migration error when updating from version prior to 1.8 (#1867)
- [fix] #1855 - scrolling in registration dialog on mobiles (#1860)
- [fix] #1854 - share items could be too wide, with long user names (#1859)
- [change] #1828 - hide internal user ids in public polls
- some more minor fixes, optimizations and refactoring

All changes: https://github.com/nextcloud/polls/issues?q=is%3Aclosed+milestone%3A3.1


## [3.0.0] - 2021-07-11
## This mainly a compatibility update to Nextcloud 22 and 21
### new Features/changes
- reduced undelete time from 7 to 4 seconds
- Deleted polls are now archived polls
- Optimizations to the date picker
- Change checkboxes to a switch style
- added some infos to the information button
- Added a configurable threshold to hide other users' votes:
  If too many voting cells are generated, the js performance can break down and lead to a long js runtime. The per user threshold defaults to 1000 cells to display. This needs further optimization for a good UX.

### A lot of optimizations under the hood
- Using more server side events
- removing orphaned assets
- new migration offset
- compatibility to Circles 22
- load some components asynchronously
- load navigation and sidebar asynchronously via router
- Allow larger usernames and displaynames
- remove DBAL dependency for Nextcloud 22
- remove group shares, if group is deleted from Nextcloud

### Fixes
- Avoid sending mails to disabled users

All changes: https://github.com/nextcloud/polls/issues?q=milestone%3A3.0+

## [3.0.0-rc.3] - 2021-07-08
## Only available for Nextcloud 21/22
- [fix] #1815 - Keep DBAL Exceptions for NC21 compatibility
- [fix] #1814 - fix comments' timestamp info

## [3.0.0-rc.2] - 2021-07-05
## Only available for Nextcloud 21/22
- [fix] #1807 - Wrong version schema used (2.0.4 was offered as update)
- [fix] #1808 - delete invalid database column
- [fix] #1808 - fixed notifier

## [3.0.0-rc.1] - 2021-07-02
## Only available for Nextcloud 21/22
- [compatibility] Compatible to Nextcloud 22
- [fix]  #1690 - Hide vote table, if too many cells are predicted
- [fix] #1707 - Do not preselect 1.Jan 1970 on range selection in date-picker
- [fix] #1724 - Do not send mails to disabled users (#1751)
- [fix] #1789 - Compatibility to new Circles implementation in NC22
- [ux] #1489 - Show Result count also in list view
- [ux] #1711 - remove ordinal suffix/prefix from date display (#1748)
- [ux] #1757 - rename "Deleted polls" to "Archive"
- [design] #1776 - Change checkboxes to switch layout with new @nextcloud/vue
- [enhancement] #1637 - Remove deleted groups from shares via event
- [enhancement] #1788 - Raise field length for user ids and usernames (#1797)
- [enhancement] #1691 - Optimizations in date-picker
- [refactoring] #1637 - replace Doctrine\DBAL\ with OCP\DB
- [refactoring] #1644 - control table changes via events
- [refactoring] #1698 - Pack migrations
- [refactoring] #1745 - Remove unused images
- [refactoring] #1791 - Load components asynchronously, if not always used

## [2.0.6 - release] - 2021-07-06
## Only available for Nextcloud 20/21
- [fix] #1811 - fix repair step at NC20

## [2.0.5 - release] - 2021-07-01
## Only available for Nextcloud 20/21
- [fix] #1774 - PHP 7.2 compatibility
- [fix] #1781 - Skip repair steps on initial install
- [fix] #1792 - check for existence of duration column before vote fix

## [2.0.4 - release] - 2021-06-22
## Only available for Nextcloud 20/21
- [fix] #1770 - Silently ignore UniqueConstraintViolationException while migrating voteOptionTexts

## [2.0.3 - release] - 2021-06-21
## Only available for Nextcloud 20/21
- [fix] #1749 - Poll answers are not shown anymore after upgrade to 2.0 (#1762)
- [fix] #1762 - Options with a time 00:00 are displayed without time information

## [2.0.2 - release] - 2021-06-11
## Only available for Nextcloud 20/21
### Bugfix release in order to fix the problems, which came from the update to version 2.0
- [fix] #1723 - prevent to run in migration error upon server update

### [1.9.7] - 2021-06-11
## Only available for Nextcloud 19
### Bugfix release in order to fix the problems, which came from the update from version 1.8 to 1.9
- [fix] #1723 - prevent to run in migration error upon server update

## [1.9.4 - release] - 2021-06-04
### new Features
- Fixed print layout
- Date picker optimizations in date polls (adding date option)
- One click deleting items now has a delay, which can be aborted (users, options, comments and shares)
- Added feedback notification, when vote is saved
- Allow participants adding option proposals
- Remove polls of deleted users and remove all of their user informations
- Render markup description in invitation mail
- Added option to delete vote instead "no" vote
- Added a janitor job to delete obsolete database entries (affects log and watch tables)
- Added CLI commands for adding shares to poll via command line
- Added statistical information to the poll information

### Fixes
- Error saving username on public polls when mail sending failed
- First day of week was wrong in datepicker
- adding parameters to API

… and more minor fixes and optimizations

## [1.9.3 - beta4] - 2021-06-02
### Changes and fixes
- [fix] #1686 - Fixing a print issue, when printing in list layout

## [1.9.2 - beta3] - 2021-05-31
### Changes and fixes
- [fix] #1560 - First day of week is wrong in date picker (#1674)
- [fix] #1661 - Disallow proposals on closed polls
- [enhancement] #587 - Allow URL-Parameters for username and email address in public share (#1673)
- [enhancement] #1625 - Avoid caching of get requests upon some server configuration (#1663)
- [enhancement] #1676 - Add vote and option statistics to poll information

## [1.9.1 - beta2] - 2021-05-28
### Changes and fixes
- [fix] #1652 - Error saving username on public polls (#1567)
- [fix] #1658 - Migration error (Option owner 'disallow') (#1659) [Affects only beta1]
- [enhancement] #1653 - Show sharee's name and email address after invitation sent (error/success) (#1657)
- [enhancement] #1650 - Added poll information details

## [1.9.0 - beta1] - 2021-05-22
### Changes and fixes
- [enhancement] #496 - Added possibility to allow participants proposing more options (#1570, #1127, #1495, #1554)
- [enhancement] #1490 - Delete all user information, if user is removed from Nextcloud
- [enhancement] #1632 - Render description from markup in invitation mails
- [enhancement] #1627 - Add option for deleting votes if switched to 'no'
- [enhancement] #1587 - Added janitor job to tidy database tables
- [enhancement] #1516 - Added CLI commands for share management
- [enhancement] #365  - Optimization of CSS for printing poll (#1567)
- [fix] #1572 - Order in experimental settings (#1621)
- [UX] #1519 - Add visual feedback, when vote is saved
- [UX] #1506 - Date picker optimizations (#1543)
- [UX] #1620 - Deletion of users, options, comments and shares can be aborted
- [UX] #1556 - Adding toast notification after successful vote
- [refactor] #1499 - Internal structure of store and components

See also https://github.com/nextcloud/polls/milestone/34?closed=1

## [1.8.3] - 2021-04-12
### Changes and fixes
- [bug] #1544 - Fixed display of end day in options sidebar on options with day span

## [1.8.2] - 2021-04-10
### Changes and fixes
- [performance] #1517 - Performance optimizations for username check (#1532)

## [1.8.1] - 2021-03-20
### new Features
- Date options now have a duration (from/to)
- Date options can be chosen as whole day (no time)
- Added markdown support for poll description
- Poll option to hide booked up options from participants, when option limit is reached
- The poll owner can now delete all comments
- Watch for poll changes (realtime changes)

### Changes and fixes
- Subscription to current poll moved to user menu
- Public users can now change, add and remove their email addresses via user menu
- For poll owner: Copy participants email addresses has moved to new user menu
- Wording: use list and table layout instead of desktop and mobile
- Changed icons for Table and list view
- Move poll informations to icon bar (info icon)
- Change registration dialog layout and optimizations on mobiles
- Fix dark mode issue with confirmed options
- Fix uniqueContraintsException when shifting dates

### changes since 1.8.0 - beta1
- [refactor] #1487 - changed error handling in watchPolls
- [refactor] #1484 - some code maintenance
- [security] #1471 - prevent html in description (follow up to #1443)

 See also https://github.com/nextcloud/polls/milestone/33?closed=1

## [1.8.0 - beta1] - 2021-03-07
 - [ux] #1164 - Wording: use list and table layout instead of desktop and mobile (#1443)
 - [ux] #1430 - Move poll informations to icon bar (info icon) (#1443)
 - [ux] #1418 - Allow changing emailaddress in public polls (#1431)
 - [ux] #1401 - Change registration dialog layout (#1429)
 - [ux] #1400 - Optimizations for registration dialog on mobiles (#1429)
 - [enhancement] #325 - added markdown support for poll description (#1443)
 - [enhancement] #1449 - Added option to hide booked up options (related to option limits)
 - [enhancement] #201, #404, #492 - Allow date option with timespan (#1365)
 - [enhancement] #991 - Allow date options without time (#1365)
 - [enhancement] #999 - Allow poll owner to delete comments (#1399)
 - [enhancement] - #1367 - Immediately adopt changes from other users to the current poll
 - [fix] #1403 - Dark mode issue with confirmed options
 - [fix] #1374 - Fix uniqueContraintsException when shifting dates
 - [refactor] #1397 - Changed migrations
 - and some more fixes and refactoring

## [1.7.5 - bugfix release] - 2021-02-01
  - [fix] #1374 - fix uniqueContraintsException when shifting dates (backport)
  - [fix] #1380 - remove invalid shares before migration (backport)

## [1.7.4] - 2021-01-30
### new Features since 1.6.x
 - Send invitations via notification app
 - Reload current poll every 30 seconds for updates
 - Admin users can delete and takeover polls from other users via new admin section
 - Respect autocompletion limitations from share settings for users, group and circle searches
 - Limit number of participants per option
 - Limit number of votes per participant
 - Combine registration dialogs into one dialog for public polls
 - Show closed polls in the relevant list until four days after closing date
 - Changed display of expiration timespan in polls overview
 - Support dark mode and dark theme
 - Compatible with Nextcloud 21
 - Drop support for Nextcloud before 19

### Bugfixes since 1.6.x
 - Linebreaks in description were ignored
 - Avoid concurrent long term user searches with a big user base
 - Speed up poll overview, by avoiding unnecessary loading of polls, the user is not allowed to see
 - Avoid duplicates in different tables
 - Invalid string text in the email

 See also https://github.com/nextcloud/polls/milestone/31?closed=1

## [1.7.3 - RC1] - 2021-01-27
 - [enhancement] #1358 - show closed polls in the relevant list until four days after closing date
 - [enhancement] #1358 - add warning class to hints in the configuration
 - [fix] #1355 - fix migration
 - [fix] #1358 - detect conflicts after vote click, if limits are set and more than one user is voting
 - [fix] #1358 - menu in poll list was not clickable
 - [fix] #1357 - copy participants was broken
 - [dependencies] Updated dependencies
 - [dependencies] fix calendar popover (@nextcloud/vue@3.5.4)

## [1.7.2 - beta3] - 2021-01-17
 - [enhancement] #1338 - Support dark mode and dark theme
 - [fix] #1346 - user search broken
 - [fix] #1344 - prevent commenting, when entering public poll without registration

## [1.7.1 - beta2] - 2021-01-12
 - [fix] #1325 - There are no spaces in the column name
 - [fix] #1326 - Invalid string text in the email
 - [enhancement] #739 - Limit number of participants per option
 - [enhancement] #738 - Limit number of votes per participant (also #647, #624)
 - [dependencies] Updated dependencies
 - [refactoring] Mainly code maintenance and optimizations, bug fixes

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

## [1.6.3] - 2020-11-23
  - [fix] #1252 External user is not listed in admin's shares list

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

 - Internal optimizations
 - In public poll ask for username in a modal
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
 - Instant persisting of votes and configuration
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
    - Improved UI
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
    - Improved UI
	- Introduced new NC date time picker from vue-nextcloud
	- Introduced multiselect from vue-nextcloud
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
