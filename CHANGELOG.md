<!--
  - SPDX-FileCopyrightText: 2017 Nextcloud contributors
  - SPDX-License-Identifier: CC0-1.0
-->
# Changelog
All notable changes to this project will be documented in this file.

## [8.5.0] - 2025-10-03
### Fixed
 - Archived polls could not get entered anymore
 - Public users were not displayed correctly right after registration
 - Avoid unnecessary backend requests in public polls
 - Fixed hash calculation resulting in possible vote losses if updating from a version prior to 8.2

### Changed
 - Reduce depth of HTML DOM
 - stabilized migrations

### Misc
 - Ignore deleted calendars for calendar check
 - Changed internal usage of share label
 - internal change of

## [8.4.6] - 2025-09-07
### Fixed
 - Ordering options in text polls was broken
 - Menu showed invalid actions in text poll
 - Restore lost max width of elements on vote page
 - Limit height of large text options
 - Expand large text options on hover and allow scrolling them
 - Optimized ordering text polls with large text options

## [8.4.5] - 2025-09-04
### Fixed
 - Fixed display of one day event on days with a daylight saving change

### Changed
 - Replaced the "Maybe" Icon from a checkmark in braces to a tilde
 - Changed Icons according to new Nextcloud style guide

## [8.4.3] - 2025-09-01
### Fixed
 - Fixed nullish user settings
 - Fixed some activity bugs
 - Limit subquery results to respect oracle limit (affected orphaned votes deletion in janitor cron)

## [8.4.2] - 2025-08-31
### Fixed
 - Better handling of subscribed calendars for calendar check

## [8.4.1] - 2025-08-29
### Changed
 - Compatible to Nextcloud 31 and 32
 - Design changes from Nextcloud's vue library

## [8.3.10] - 2025-08-29
### Fixed
 - fix creation of duplicated index
 - show participants displayname on hovering over avatar

## [8.3.8] - 2025-08-27
### Changed
 - Clean table records in postMigration instead of preMigration
 - adjust order in repair command

### Fixed (v8.3.0)
 - Made description sticky again on horizontal scrolling
 - Activities in sidebar did not get loaded
 - Avoid Warnings in console because of missing keys
### Changed (v8.3.0)
 - Replaced momentjs by Luxon
 - Fixed db types and defaults to match unique share index
 - Removed FK constraint for share table
 - Added unique index for share tokens
 - Reworked poll access via shares
 - Added some indices to help complex joins and optimized the index
 - Removed creation of optional indices from all migration and repair steps and support AddMissingIndicesEvent
 - Separated the commands in more granular index and database commands
 - Adjust icon style
 - Adjust CSS Vars
 - Moved comment config to comment tab
   - Make comments available to owner and delegated poll administration if commenting is disabled
   - Add hint, if commenting is disabled

## [8.3.4] - 2025-08-25
 - Force migration into reloading classes

## [8.3.3] - 2025-08-24
 - Fix migration

## [8.3.2] - 2025-08-22
 - Fix migration from 7.x to 8.x

## [8.3.0] - 2025-08-21
If you experience update problems, please refer to [this article](https://github.com/nextcloud/polls/wiki/Installation-help#update-issues)

### Fixed
 - Made description sticky again on horizontal scrolling
 - Activities in sidebar did not get loaded
 - Avoid Warnings in console because of missing keys
### Changed
 - Replaced momentjs by Luxon
 - Fixed db types and defaults to match unique share index
 - Removed FK constraint for share table
 - Added unique index for share tokens
 - Reworked poll access via shares
 - Added some indices to help complex joins and optimized the index
 - Removed creation of optional indices from all migration and repair steps and support AddMissingIndicesEvent
 - Separated the commands in more granular index and database commands
 - Adjust icon style
 - Adjust CSS Vars
 - Moved comment config to comment tab
   - Make comments available to owner and delegated poll administration if commenting is disabled
   - Add hint, if commenting is disabled

## [8.2.2] - 2025-08-03
### Added
 - Add vote indicator for locked options
### Changed
 - Make vote cells focusable
 - Make shadow of sticky items transparent
 - Changed experimental comments layout
 - Improve poll loading times again by applying only diffs after updates
 - Moved some store loading to views and rely on watchers
### Fixed
 - Force list view mode initially on mobile viewports
 - Fix some visual issues of the vote page
 - Bring back indicator for confirmed options after closing the poll
 - Watch worker was not allowed to execute, added CSP header
 - Fix loading archived polls
 - Broken access if a logged in user enters a poll by public share if he already had access to the poll
 - Fixing several performance issues
 - Fixed a bug, where the sidebar tabs were not usable anymore

## [8.1.4] - 2025-07-15
### Fixed
 - Fixed some typos
 - Removed inaccessible polls from polls overview
 - Removed link target from inaccessible polls in navigation
 - Removed clone action from inaccessible polls in navigation
 - Fixed visual bug when scrolling in list view
 - Fixed exception on notifications which may cause resending notification mails
### Changed
 - Center poll table

## [8.1.0] - 2025-07-13
### Added
 - Support Nextcloud 30
 - Added user shares to poll groups
 - Added a forbidden route and page
 - Added WatchController to OCS-API
 - Sticky option headers in vote table
 - Lazy loading of participants on scroll, if too many vote cells are rendered
### Changed
 - Optimized janitor cron
 - Optimized rebuild command
 - Optimized poll loading by migrating subqueries to join (#3692)
 - Accelerated loading performance of polls
 - Separated pollGroups from polls (Store, Service, Mapper, ...)
 - Catch CronJob runs and report as error, but avoid crash at higher thread levels
 - Changed poll loading triggers (mainly navigation affected)
 - Added some status to the watchWorker
 - Removed performance user setting in favor of lazy loading participants
 - Reduce noise by avoiding toasts for obvious changes
### Fixed
 - Fixed broken endpoint for manually calling autoReminderCron
 - Fix avatar foreground color
 - Fix window title

## [8.0.6] - 2025-07-03
### Changed (8.0.6)
 - Terminate webworker in background tabs
 - Fix cloning of date options
### Changed (8.0.0)
 - Migration to Vue 3 and Pinia
 - Change Circles to Teams
 - Change sortable component
 - Preparation for voting variants
### Added (8.0.0)
 - New dialog for creation of datetime options in date polls
 - Added option to automatically set all added options to yes
 - Locked anonymous mode
 - Restricted poll owners
 - Redesign of poll lists
 - Changed controls of numeric inputs
 - Auto delete archived polls after configurable days
 - Configuration for using the Nextcloud default footer in public polls
 - Delete polls without the need to archive them first
 - Collapsible poll description
 - Transfer polls to another owner by the current poll owner or the administration
 - Added reference provider for link previews and smart picker
 - Added confidential comments for comments only visible by the author and the current poll owner
 - Create and manage poll groups for better poll management

## [8.0.4] - 2025-06-22
### Fixed (8.0.4)
 - Fixed malformatted activity stream messages
 - Fixed some design issues
 - Limited width of vote table in list view
### Changed (8.0.0)
 - Migration to Vue 3 and Pinia
 - Change Circles to Teams
 - Change sortable component
 - Preparation for voting variants
### Added (8.0.0)
 - New dialog for creation of datetime options in date polls
 - Added option to automatically set all added options to yes
 - Locked anonymous mode
 - Restricted poll owners
 - Redesign of poll lists
 - Changed controls of numeric inputs
 - Auto delete archived polls after configurable days
 - Configuration for using the Nextcloud default footer in public polls
 - Delete polls without the need to archive them first
 - Collapsible poll description
 - Transfer polls to another owner by the current poll owner or the administration
 - Added reference provider for link previews and smart picker
 - Added confidential comments for comments only visible by the author and the current poll owner
 - Create and manage poll groups for better poll management

## [8.0.1] - 2025-06-22
### Fixed (8.0.1)
 - Fixed display of the list view mode
 - Use locale instead of language for date and time representation
 - Fixed representation of time interval when adding a date option with a duration
### Changed (8.0.0)
 - Migration to Vue 3 and Pinia
 - Change Circles to Teams
 - Change sortable component
 - Preparation for voting variants
### Added (8.0.0)
 - New dialog for creation of datetime options in date polls
 - Added option to automatically set all added options to yes
 - Locked anonymous mode
 - Restricted poll owners
 - Redesign of poll lists
 - Changed controls of numeric inputs
 - Auto delete archived polls after configurable days
 - Configuration for using the Nextcloud default footer in public polls
 - Delete polls without the need to archive them first
 - Collapsible poll description
 - Transfer polls to another owner by the current poll owner or the administration
 - Added reference provider for link previews and smart picker
 - Added confidential comments for comments only visible by the author and the current poll owner
 - Create and manage poll groups for better poll management

## [8.0.0] - 2025-06-22
### Changed
 - Migration to Vue 3 and Pinia
 - Change Circles to Teams
 - Change sortable component
 - Preparation for voting variants
### Added
 - New dialog for creation of datetime options in date polls
 - Added option to automatically set all added options to yes
 - Locked anonymous mode
 - Restricted poll owners
 - Redesign of poll lists
 - Changed controls of numeric inputs
 - Auto delete archived polls after configurable days
 - Configuration for using the Nextcloud default footer in public polls
 - Delete polls without the need to archive them first
 - Collapsible poll description
 - Transfer polls to another owner by the current poll owner or the administration
 - Added reference provider for link previews and smart picker
 - Added confidential comments for comments only visible by the author and the current poll owner
 - Create and manage poll groups for better poll management

## [7.4.4] - 2025-06-14
### Fixed
 - Bring back user feedback after sent invitations
 - Fix possible broken update on invalid records

## [7.4.3] - 2025-05-23
### Fixed
 - Email constrains of public shares where not displayed properly

## [7.4.2] - 2024-03-31
### Fixed
 - Poll export did not update recent votes of the current user
 - User could change a poll to an openly accessible polls, without having the permission to
 - Poll list was not updating after overtaking a poll

## [7.4.1] - 2024-03-07
### Added
 - Apply rules for creating shares as set in the system settings

## [7.3.2] - 2024-02-25
### Changed
 - Remove limit of usersearch (was limited to 30 results)

## [7.3.1] - 2024-02-14
### Fixed
 - Fix poll transfer via occ
 - Support Nextcloud 31

## [7.2.10] - 2024-01-21
### Same as 7.2.8 but only for PHP 8.0
### Fixed
 - Fix limit check

## [7.2.9] - 2024-01-03
### Fixed
 - Minimum php version (8.1)

## [7.2.8] - 2024-12-28
### Fixed
 - Fix limit check

## [7.2.6] - 2024-12-24
### Fixed
 - Fix date picker poositioning by updating to nextcloud/vue-components@8.22.0

## [7.2.5] - 2024-11-23
### Fixed
 - Avoid error message due to new core check of column name length
 - Fix user search did not display subnames

## [7.2.4] - 2024-09-26
### Fixed
 - Fix vote limit checks for public users
 - Fix access to public polls email and contact shares
 - Fix placeholder translations of email input of the register dialog

## [7.2.3] - 2024-09-12
### Fixed
 -  fix size of creation box in navigation

## [7.2.2] - 2024-09-06
### Fixed
 -  fix watcher in situations it may fail on pollId 0
 -  fix failing routes on tokens with trailing spaces
 -  Removed index removal from the pre-migration repair steps

## [7.2.1] - 2024-08-22
### Fixed
 -  Fix deleted user when email share registers

## [7.2.0] - 2024-08-01
### Changed
 - Add Nextcloud 30

## [7.1.4] - 2024-07-15
### Fixed
 - Fix autoreminder again
 - Fix acticities display of circles
 - Remove colons from exported file names

## [7.1.3] - 2024-06-30
### Fixed
 - Fix autoreminder

## [7.1.2] - 2024-06-24
### Fixed
 - Fix owner detection (prevented deleting comments by poll owners)
 - Fix exporting of polls
 - Fix poll loading on some MySQL configurations
 - Fix context menu in polls list

## [7.1.1] - 2024-06-10
### Fixed
 - Fix opening and closing of sidebar after changed component
 - Try avoiding update error by removing class registering
### Change
 - Support Nextcloud 27

## [7.1.0] - 2024-06-09
**!!! changed API structure, please refer to the documentation**
### Fixed
 - Fixed counting of orphaned votes
 - Disable registration button while registration is pending
 - Disable "resolve group share" while resolving
 - Fix showing booked up options in polls with hidden results
### Changed
 - Mainly performance improvements
 - Changed API structure for polls, please refer to the documentation
### Performance
 - Added an option to allow to add polls to the navigation (default)
 - Limited polls inside the navigation to 6 items
 - Render the polls list in chunks of 20 items

## [7.0.3] - 2024-04-05
### Fixed
 - Archive, restore and delete polls in poll list was missing, braught the options back to the action menu
 - Fix a situation, where votes of a non existing poll are requested
 - Fix getting group members
### Added
 - Added an endpoint to the Api to be able to fetch the acl of a poll

## [7.0.2] - 2024-03-29
### Fixed
 - Combo view was not usable

## [7.0.1] - 2024-03-29
### Fixed
 - Fix database error with PostgreSQL
 - Fix public poll access

## [7.0.0] - 2024-03-27
### Changed
 - Support for Nextcloud 29
 - Removed PHP 8.0 Support
 - Performance optimizations
 - A lot for code maintenance and tidy

## [6.3.0] - 2024-05-06
### Fixed
 - Fix preventing option suggestions
 - Fixing some performance issues
 - Fixing an error that possibly prevents users from adding suggestions
### Changed
**Changed are partially also included in 7.1.0**
 - Added an option to allow to add polls to the navigation (default)
 - Limited polls inside the navigation to 6 items
 - Render the polls list in chunks of 20 items
 - Support Nextcloud 26 to 28

## [6.2.0] - 2024-03-27
### Fixed
 - Fix preventing option suggestions

## [6.1.6] - 2024-02-27
### Fixed
 - Fixing vanishing votes after shifting date options or creating sequences

## [6.1.5] - 2024-02-24
### Fixed
 - Fixing select error

## [6.1.4] - 2024-02-24
### Fixed
 - Fixing 404 error when using public share where the poll has hidden results
 - Partially fix email shares
 - Fix user name check for public participants

## [6.1.3] - 2024-02-21
### Fixed
 - Fixing bug, when an internal user tries to enter a poll using a public share a second time
 - Fix error message of watchpoll, trying to access pollId 0

## [6.1.1] - 2024-02-16
### Changed
 - Consolidated migration to avoid double database validation

## [6.1.0] - 2024-02-16

#### This minor version contains a huge change in the internal user and Access management.
This is a step further to a public poll creation. The next major version will be a long time necessary technical migration to the current Vue 3 framework.

So this 6.x branch will get only bug fixes and compatibility updates. Any other featues are scheduled after the migration.
### Changed
 - Only Nextcloud 28 and up
 - Moved action buttons to action menu in sidebar lists of options and shares
 - Rewritten internal user base
 - Optimized access management
 - Optimized privacy and anonymity in anonymous  and public polls

### Added
 - Removed deletion timer of shares, options and comments with better undelete

### Fixed
 - Fix locked users, when registering with nextcloud user to a public poll
 - Fixed typo which caused unnecessary error logging
 - Fixed export of html files
 - Fixed non available action buttons of options in mobile view
 - Fixed calendar check
 - Fixed some minor activity issues
 - Fixed autoreminder could not be set
 - Fixed migration error which could cause data loss (when comming from 3.x)

## [6.0.1] - 2023-12-10
### Fixed
 - Some minor fixes regarding user apperances

## [6.0.0] - 2023-12-09
### Changed
 - Only Nextcloud 28 and up
### Fixed
 - Anonymize poll proposal owner in case of hidden results

## [5.4.2] - 2023-11-11
### Fixed
 - Fixed table definition

## [5.4.1] - 2023-10-31
### Fixed
 - Fixed 7 ERROR: column reference "poll_id" is ambiguous

## [5.4.0] - 2023-10-28
### Fixed
 - Fixed granting admin rights to shares
 - Fixed a bug which  prevented poll exports
 - Fixed a visually bug when using Nextcloud's Dark Mode
 - Fixed result reporting about sent and failed confirmation mails
### Added
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
### Changed
 - Improved username check for public polls with a large number of groups in the backend

## [5.3.2] - 2023-09-11
### Fixed
 - Fix migration error ("poll_option_hash" is NotNull)

## [5.3.1] - 2023-09-09
### Fixed
 - Fix creating public shares

## [5.3.0] - 2023-09-06
### Added
 - Add label to public shares
 - Send all unsent invitations to a poll with one click (resolves contact groups and circles too
### Fixed
 - Fix API calls
 - Deleting comments in public polls was broken
### Changed
 - Refactorings and code maintenance
 - Dependency updates

## [5.2.0] - 2023-07-15
### Fixed
 - Fix date shifting and sequences when crossing daylight saving change
 - Bring back notifications
 - Fix notification subscription
 - Eliminate preferences error logging on first time usage
### Changed
 - Set default view to table layout even for text polls, list layout is still the default layout when in mobile mode

## [5.1.0] - 2023-06-27
### Changed
 - Added user option to remove outdated polls from th "Relevant" list
 - Added alternative vote page design as beta option
### Fixed
 - Poll export broken under some circumstances
 - Fixed repair steps
 - Added a workaround and debugging logs regarding LDAP
 - Fixed conflict detection for poll options against user's calendar
 - PostgeSQL Compatibility
### Misc
 - Support PHP 8.2
 - Replace dropdown elements (NcSelect over NcMultiSelect)
 - Replace vue-richtext by NcRichText
 - Minor updates caused by depencies

## [5.0.5] - 2023-05-07
### Fixed
 - Show unprocessed share invitations
 - Fix bulk adding of options
 - Fixed update problems
### Changed
 - Change warning design (use NcNoteCard)
 - Support NC 27

## [5.0.4] - 2023-04-25
### Fixed
 - Ensure duplicate removal after migration and in repair command
 - Fix notification exception for nullish log entries (fix was not pushed to 5.0.3)

## [5.0.3] - 2023-04-20
### Fixed
 - Fix notification exception for nullish log entries

## [5.0.2] - 2023-04-17
### Fixed
 - Fix crash with shares which have nullish mail addresses

## [5.0.1] - 2023-04-13
### Fixed
 - Polls cannot be edited when user has no mail address

## [5.0.0] - 2023-04-07
### Added
 - Added qr code for public shares
### Changed
 - PHP 8.0 as minimum requirement
 - Shorten public tokens to 8 characters (lower and upper characters and digits)

## [4.1.8] - 2023-03-03
### Fixed
 - Fix Error on poll creation `General error: 1364 The field 'description' has no default value.`

## [4.1.7] - 2023-03-02
### Fixed
 - Fix invitation mails for guest users

## [4.1.6] - 2023-02-27
### Fixed
 - Removed trailing comma in rebuild command## [4.1.5] - 2023-02-25
### Fixed
 - Fix disappeared option add button after change in the nextcloud-vue lib
### Changed
 - Changed option owner column to notnull

## [4.1.4] - 2023-02-23
### Fixed
 - Fix infinite updates call, if no polling type for watches were set (avoid server spamming) (v4.1.3)
 - Fix migrations and repair steps (v4.1.3)
 - Fix MySQL error 1071 Specified key was too long;
### changed
 - Change default of life update mechanism to manual updates instead of long polling (v4.1.3)
 - Added Nextcloud 26

## [4.1.2] - 2023-01-23
### Fixed
 - Invitations are not send out if poll has no description (fix 2)

## [4.1.1] - 2023-01-19
### Fixed
 - Invitations are not send out if poll has no description

## [4.1.0] - 2023-01-17
### Added
 - Added a dashboard widget for relevant polls
 - Improved registration dialog for public polls
 - Small design change to vote page according to new nextcloud design
### Fixed
 - Reset own votes as a logged in user without admin rights
 - Error was thrown, when a owner of an option was null
 - Deleted shares prevented poll export and
 - Avoid timestamp overflow for dates greater than 01-19-2038
 - Increase length of option texts from 256 to 1024 characters
 - Fix access validation checks
 - Avoid timestamp overflow with dates past 2038/01/19 (Timestamp 2147483647
### Misc
 - Refactoring of API requests to a central http API
 - Refactoring and fixes to background watcher
 - Accelerated installation and updates

## [4.0.0] - 2022-10-13
### Added
 - Support Nextcloud version 25
### Misc
 - Experimental designs have been removed

## [3.8.4] - 2022-12-18
### Fixed
 - Reset own votes as a logged in user without admin rights
 - Error was thrown, when a owner of an option was null

## [3.8.3] - 2022-10-24
### Fixed
 - Fix poll export containing participants with deleted shares

## [3.8.2] - 2022-09-27
### Fixed
 - Fix a bug, which prevents voting in a public vote, when comments are disabled.
 - Suppress annoying error log entries with PHP 8.1

## [3.8.0] - 2022-09-18
### Added
 - Support Nextcloud version 22 - 24
 - Convert links in comments to clickable links
 - Allow public users to logout from a poll, when logged in via cookie
 - Allow public users to change their name after registration to a public poll
 - Allow bulk poll ownership transfer for admins
 - Added option to send mails about confirmed options
### Fixed
 - Unsubscribing from a public poll was not possible
 - Use display name for avatar of the current public user instead of user id
 - Fix export, if owner did not vote in the poll
 - Fix adding option, when not admin (bulk operation)

## [3.7.0] - 2022-06-24
### Added
 - User setting for conflict check (hours before and after an option to search for conflicts)
 - Add admin option to prevent email address exposing of internal users
### Fixed
 - Poll export, if the owner did not vote
 - Poll export was broken, when certain characters were present in the poll title
 - Handling of recurring calendar events (NC24)
 - Removed error message in log triggered from user search when adding share
 - Fixed calendar conflict search for recurring events (NC24)
 - Personal public shares got intinite redirected
### Misc
 - Switch to new calendar API (NC24)
 - Repaces icons with material design icons
 - Generate a unique user id for public users
 - Less noise in the registration dialog

## [3.7.0-beta5] - 2022-06-05
### Fixed
 - Translations
 - Legal links
### Changed
 - Changed apperance of registration modal
 - Improvement of InputDiv component

## [3.7.0-beta4] - 2022-05-29
### Fixed
 - Poll export was broken, when certain characters were present in the poll title
 - Removed error message in log triggered from user search when adding share
### Misc
 - Replaced icons with material design icons
 - Generate a unique user id for public users

## [3.7.0-beta3] - 2022-05-06
### Added
 - User setting for conflict check (set hours before and after an option to search for conflicts
### Fixed
 - Poll export, if the owner did not vote
 - Calendar conflict check (NC24)
 - Handling of recurring calendar events (NC24)
### Misc
 - Switch to new calendar API (NC24)

## [3.7.0-beta2] - 2022-04-27
### Fixed
 - Fixed syntax error in class AppSettings

## [3.7.0-beta1] - 2022-04-27
### Added
 - Add admin option to prevent email address exposing of internal users

## [3.6.1] - 2022-04-23
### Added
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
 - Some more design changes
### Fixed
 - Poll export to spreadsheeds was fixed if Poll title is longer than 31 characters
 - Fix LDAP user search
 - Poll list in admin page should not link to a poll
 - Remove markup in text only emails

## [3.6.0-rc1] - 2022-04-16
### Added
 - Allow email share adding using common email formats with name (#2375
### Changed
 - Changed transitions on vote vlicks and add hover state

## [3.6.0-beta2] - 2022-04-13
### Added
 - Add icon symbol for locked vote options
 - Store username in a public poll to cookie
### Fixed
 - Avoid unnecessary error logs in activities
 - Fix missing icons after dep update
 - Fix styling bugs
 - Fixed different translation errors

## [3.6.0-beta1] - 2022-04-02
### Changed
 - Rename "hidden" polls to "private" polls, "public" to "open" (#2289)
 - Migrate access strings to 'private' and 'open' (instead of 'hidden' and 'public')
### Added
 - Added the option to add links to terms and private policy to public registration dialog
 - Added an option to add legal terms and a disclaimer to emails
 - Add email addresses to poll export (#2327)
### Fixed
 - Fix LDAP search (#2323)
 - Fixed poll export (#2286, #2287)
 - Fixed heights of modals after update of @nextcloud/vue@5
 - HTML Tags in plain Poll invitation (#2346)
 - Links in admin page could lead to non accessible poll (#2326)
### Misc
 - Added support for inputmode
 - Added support for material design icons to some components (#2329)
 - Replace deprecated String.prototype.substr()
 - Styling inpuDiv

## [3.5.4] - 2022-02-17
### Fixed
 - Deletion of NC users was broken through polls (#2279)
 - Translation error

## [3.5.3] - 2022-02-15
### Changed
 - Add email address if valid search parameter (#2268)
### Fixed
 - Fixed user search (#2267)
 - Fixed poll export due to changed module export of xlsx
### Misc
 - Late translations delivery

## [3.5.2] - 2022-02-11
### Fixed
 - Adding options in text poll is not possible

## [3.5.1] - 2022-02-11
### Fixed
 - Updated php minimum version in info.xml

## [3.5.0] - 2022-02-09
### Added
 - Following new features are disabled by default per admin switch
    - Export polls (.xlsx, odt, .csv, .html)
    - Track activities
    - Combine multiple polls in one view (read only)
 - Add polls to collections
 - Linkify URLs and email addresses in text options
 - New command `occ polls:db:recreate` for validating and fixing db structure
### Fixed
 - It was possible to add option proposals, when not registered in public polls
 - A deleted poll could cause repeating error logs on notifications
 - Fixed a migration error, when updating from rather old version

## [3.5.0-beta3] - 2022-02-01
### Fixed
 - Code optimization and refactoring
 - Migration error

## [3.5.0-beta2] - 2022-01-23
### Added
 - Allow join project / collection
 - Add `occ polls:db:recreate` for validating and fixing db structure

## [3.5.0-beta1] - 2022-01-18
### Added
 - Export poll
 - Use activities
 - Combined view for date polls
 - Linkify options
### Fixed
 - Adding proposals is possible without registering
 - Notifications error with deleted polls

## [3.4.2] - 2021-12-13
### Added
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
 - Fix error when adding option #2126 (v3.4.1)
 - Fix missing anonymization of proposal owners in anonymous polls #2136 (v3.4.2)
 - Fix testing of email address constraints for public poll registration #2137 (v3.4.2)

## [3.4.0-beta1] - 2021-11-26
### Changed
 Compatible with Nextcloud 23
 Share tab redesign

## [3.4.0-alpha1] - 2021-11-02
### Added
 - Added participation indicator in effective shares list
 - Add autoreminder job
### Changed
 - Validate token in router and reroute before entering public page
 - Configure update polling
### Fixed
 - Do not archive polls without expiration automatically

## [3.3.0] - 2021-10-10
### Added
 - Added email addresses to external shares in the shares tab for the owner
 - Adopt dashboard design in personal app settings and improved individual styling (still experimental)
### Fixed
 - Fixed calculation of full day events, which could break the display on daylight changing days

## [3.3.0-rc1] - 2021-10-03
### Added
 - Show email address in share list external users
 - Adopt dashboard design (still experimental)
### Fixed
 - Changed calculation of full day events

## [3.2.0] - 2021-09-19
### Changed
 - Poll administration can now be delegated to invited users
 - New admin section for polls (/settings/admin/polls-admin)
  - Disable login option in registration screen of public polls
  - Auto archive closed polls after configured days
  - Restrict poll creation to groups
  - Restrict public poll creation to groups
  - Restrict creation of polls with all users access to groups

## [3.2.0-rc2] - 2021-09-14
### Added
 -  Delegate poll administration to invitees
### Fixed
 - Fix DB setting for oracle
 - App failed, if app config was not set

## [3.2.0-rc1] - 2021-09-12
### Added
 - Configure email registration in public polls
 - Allow users to reset their votes
 - Admin section for polls
 - Admin - disable login option in public polls
 - Admin - auto archive closed polls
 - Admin - Restrict poll creation to groups
 - Admin - Restrict public poll creation to groups
 - Admin - Restrict creation of polls for all users to groups
### Changed
 - Remove three character validation for public user names
### Fixed
 - User search broke, when a user has no mail address configured

### Added admin section

## [3.1.0] - 2021-08-21
 - GUI optimizations
 - Hide internal user IDs in public polls
 - Fixed migration error
 - Fixed registration dialog on mobiles
 - Fixed width of share icons
 - Some minor fixes

## [3.1.0-rc1] - 2021-08-16
**Only available for Nextcloud 21/22**
### Changed
 - Visual fixes to polls list
 - Updated vote view
 - Migration error when updating from version prior to 1.8 (#1867)
 - Scrolling in registration dialog on mobiles (#1860)
 - Share items could be too wide, with long user names (#1859)
 - Hide internal user ids in public polls
 - Some more minor fixes, optimizations and refactoring

All changes: https://github.com/nextcloud/polls/issues?q=is%3Aclosed+milestone%3A3.1

## [3.0.0] - 2021-07-11
**This mainly a compatibility update to Nextcloud 22 and 21**
### Added
 - Reduced undelete time from 7 to 4 seconds
 - Deleted polls are now archived polls
 - Optimizations to the date picker
 - Change checkboxes to a switch style
 - Added some infos to the information button
 - Added a configurable threshold to hide other users' votes:
   If too many voting cells are generated, the js performance can break down and lead to a long js runtime. The per user threshold defaults to 1000 cells to display. This needs further optimization for a good UX.
### Changed
 - Using more server side events
 - Removing orphaned assets
 - New migration offset
 - Compatibility to Circles 22
 - Load some components asynchronously
 - Load navigation and sidebar asynchronously via router
 - Allow larger usernames and displaynames
 - Remove DBAL dependency for Nextcloud 22
 - Remove group shares, if group is deleted from Nextcloud
### Fixed
 - Avoid sending mails to disabled users

## [3.0.0-rc.3] - 2021-07-08
**Only available for Nextcloud 21/22**
### Fixed
 - Keep DBAL Exceptions for NC21 compatibility
 - Fix comments' timestamp info

## [3.0.0-rc.2] - 2021-07-05
**Only available for Nextcloud 21/22**
### Fixed
 - Wrong version schema used (2.0.4 was offered as update)
 - Delete invalid database column
 - Fixed notifier

## [3.0.0-rc.1] - 2021-07-02
**Only available for Nextcloud 21/22**
### Added
 - Remove deleted groups from shares via event
 - Raise field length for user ids and usernames
 - Optimizations in date-picker
 - Compatibility to new Circles implementation in NC22
### Changed
 - Show Result count also in list view
 - Remove ordinal suffix/prefix from date display
 - Rename "Deleted polls" to "Archive"
 - Compatible to Nextcloud 22
 - Change checkboxes to switch layout with new @nextcloud/vue
 - Replace Doctrine\DBAL\ with OCP\DB
 - Control table changes via events
 - Pack migrations
 - Remove unused images
 - Load components asynchronously, if not always used
### Fixed
 - Hide vote table, if too many cells are predicted
 - Do not preselect 1.Jan 1970 on range selection in date-picker
 - Do not send mails to disabled users

## [2.0.6] - 2021-07-06
**Only available for Nextcloud 20/21**
### Fixed
 - Fix repair step at NC20

## [2.0.5] - 2021-07-01
**Only available for Nextcloud 20/21**
### Fixed
 - PHP 7.2 compatibility
 - Skip repair steps on initial install
 - Check for existence of duration column before vote fix

## [2.0.4] - 2021-06-22
**Only available for Nextcloud 20/21**
### Fixed
 - Silently ignore UniqueConstraintViolationException while migrating voteOptionTexts

## [2.0.3] - 2021-06-21
**Only available for Nextcloud 20/21**
### Fixed
 - Poll answers are not shown anymore after upgrade to 2.0 (#1762)
 - Options with a time 00:00 are displayed without time information

## [2.0.2] - 2021-06-11
**Only available for Nextcloud 20/21**
**Bugfix release in order to fix the problems, which came from the update to version 2.0**
### Fixed
 - Prevent to run in migration error upon server update

### [1.9.7] - 2021-06-11
**Only available for Nextcloud 19**
**Bugfix release in order to fix the problems, which came from the update from version 1.8 to 1.9**
### Fixed
 - Prevent to run in migration error upon server update

## [1.9.4] - 2021-06-04
### Added
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
### Fixed
 - Error saving username on public polls when mail sending failed
 - First day of week was wrong in datepicker
 - Adding parameters to API

… and more minor fixes and optimizations

## [1.9.3-beta4] - 2021-06-02
### Fixed
 - Fixing a print issue, when printing in list layout

## [1.9.2-beta3] - 2021-05-31
### Fixed
 - First day of week is wrong in date picker
 - Disallow proposals on closed polls
### Changed
 - Allow URL-Parameters for username and email address in public share
 - Avoid caching of get requests upon some server configuration
 - Add vote and option statistics to poll information

## [1.9.1-beta2] - 2021-05-28
### Fixed
 - Error saving username on public polls
 - Migration error (Option owner 'disallow') [Affects only beta1]
### Changed
 - Show sharee's name and email address after invitation sent (error/success)
 - Added poll information details

## [1.9.0-beta1] - 2021-05-22
### Fixed
 - Order in experimental settings
### Changed and fixes
 - Added possibility to allow participants proposing more options
 - Delete all user information, if user is removed from Nextcloud
 - Render description from markup in invitation mails
 - Add option for deleting votes if switched to 'no'
 - Added janitor job to tidy database tables
 - Added CLI commands for share management
 - Optimization of CSS for printing poll
 - Add visual feedback, when vote is saved
 - Date picker optimizations
 - Deletion of users, options, comments and shares can be aborted
 - Adding toast notification after successful vote
 - Internal structure of store and components

## [1.8.3] - 2021-04-12
### Fixed
 - Fixed display of end day in options sidebar on options with day span

## [1.8.2] - 2021-04-10
### Fixed
 - Performance optimizations for username check (#1532)

## [1.8.1] - 2021-03-20
### Added
 - Date options now have a duration (from/to)
 - Date options can be chosen as whole day (no time)
 - Added markdown support for poll description
 - Poll option to hide booked up options from participants, when option limit is reached
 - The poll owner can now delete all comments
 - Watch for poll changes (realtime changes)
### Changed and fixes
 - Subscription to current poll moved to user menu
 - Public users can now change, add and remove their email addresses via user menu
 - For poll owner: Copy participants email addresses has moved to new user menu
 - Changed icons for Table and list view
 - Move poll informations to icon bar (info icon)
 - Change registration dialog layout and optimizations on mobiles
### Fixed
 - Wording: use list and table layout instead of desktop and mobile
 - Fix dark mode issue with confirmed options
 - Fix uniqueContraintsException when shifting dates
### Changed since 1.8.0-beta1
 - Changed error handling in watchPolls
 - Some code maintenance
 - Prevent html in description (follow up to #1443)

## [1.8.0-beta1] - 2021-03-07
### Changed
 - Wording: use list and table layout instead of desktop and mobile
 - Move poll informations to icon bar (info icon)
 - Allow changing emailaddress in public polls
 - Change registration dialog layout
 - Optimizations for registration dialog on mobiles
 - Added markdown support for poll description
 - Added option to hide booked up options (related to option limits)
 - Allow date option with timespan
 - Allow date options without time
 - Allow poll owner to delete comments
 - Immediately adopt changes from other users to the current poll
 - Changed migrations
 ### Fixed
 - Dark mode issue with confirmed options
 - Fix uniqueContraintsException when shifting dates
 - And some more fixes and refactoring

## [1.7.5] - 2021-02-01
### Fixed
  - Fix uniqueContraintsException when shifting dates (backport)
  - Remove invalid shares before migration (backport)

## [1.7.4] - 2021-01-30
### Added
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
### Fixed
 - Linebreaks in description were ignored
 - Avoid concurrent long term user searches with a big user base
 - Speed up poll overview, by avoiding unnecessary loading of polls, the user is not allowed to see
 - Avoid duplicates in different tables
 - Invalid string text in the email

## [1.7.3-RC1] - 2021-01-27
### Fixed
 - Fix migration
 - Detect conflicts after vote click, if limits are set and more than one user is voting
 - Menu in poll list was not clickable
 - Copy participants was broken
 - Fix calendar popover (@nextcloud/vue@3.5.4)
 ### Added
 - Show closed polls in the relevant list until four days after closing date
 - Add warning class to hints in the configuration

## [1.7.2-beta3] - 2021-01-17
### Added
 - Support dark mode and dark theme
### Fixed
 - User search broken
 - Prevent commenting, when entering public poll without registration

## [1.7.1-beta2] - 2021-01-12
### Added
 - Limit number of participants per option
 - Limit number of votes per participant (also #647, #624)
### Fixed
 - There are no spaces in the column name
 - Invalid string text in the email
### Changes
 - Updated dependencies
 - Mainly code maintenance and optimizations, bug fixes

## [1.7.0-beta1] - 2021-01-02
### Added
 - Use notification app for invitations
 - Reload current poll every 30 seconds
 - Admin users can delete and takeover polls from other users via new admin section
 - Respect autocompletion limitations from share settings for users, group and circle searches
### Fixed
 - Avoid duplicates in different tables
 - External user is not listed in admin's shares list
 - Avoid concurrent long term user searches with a big user base
 - Speed up poll overview, by avoiding unnecessary loading of polls, the user is not allowed to see
### Changed
 - Public polls - combine registration dialogs into one dialog
 - Polls overview changed display of expiration timespan

## [1.6.3] - 2020-11-23
### Fixed
  - External user is not listed in admin's shares list

## [1.6.2] - 2020-11-19
### Fixed
 - Subscription was missing for logged in users

## [1.6.1] - 2020-11-17
### Fixed
 - Preferences write error
 - A few minor glitches and fixes

## [1.6.0-RC1] - 2020-11-01
### Fixed
 - Some design fixes
 - External users get internal link in notification mail
### Added
 - Configure calendars for calendar lookup
 - Change wording on hidden an public polls
 - Preferences dialog
 - Explicitly close poll
 - Add share, if logged in user enters hidden poll via public link
 - Circles integration
### Changed
 - Remove deprecated app.php
 - Separate assets
 - Updated dependencies

## [1.5.7] - 2020-10-25
### Fixed
 - Explicit convert boolean values to intger to avoid db conflicts (another aproach)

## [1.5.6] - 2020-10-23
### Fixed
 - Explicit convert boolean values to intger to avoid db conflicts

## [1.5.5] - 2020-10-20
### Fixed
 - Delete comments in public polls
 - Routing after creation of poll was wrong
 - Shifting dates went wrong
 - Reordering text poll options did not work
 - A non-numeric value encountered

## [1.5.4] - 2020-10-02
### Fixed
 - Adding missing translations
 - Release blocking fixes

## [1.5.3-RC3] - 2020-09-21
 - Added setting for defining default view for
 - Text polls, defaults to mobile/list layout and
 - Date polls, defaults to desktop/table layout #744
 - Better UX for site users, which enter a poll via public link and could login #1096

## [1.5.2-RC2] - 2020-09-15
 - Fixing Translations
 - Updated dependencies
 - Minor fixes

## [1.5.1-RC1] - 2020-09-08
 - Lookup calendars for conflict #1056 #747
 - Convert URIs in description into clickable links #1067
 - Added a poll to force poll appear under relevant polls navigation entry for all users #1072
 - Move cloning of options to backend #1058
 - Add user settings
 - Some style fixes
 - Updated dependencies
 - Load app icons via url-loader

## [1.5.0-beta1] - 2020-08-17
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
 - Add time zone info to date polls #1076

## [1.4.3] - 2020-05-03
 - Fix #909
 - Fix #910
 - Add description to invitation mail #895
 - Fixed safari bugs #770
 - Added configuraton to hide the poll result #265
 - Poll title to window title #318
 - Updated timepicker and changed layout #889, #826
 - NC 19 compatibility
 - Delete a poll completly #801
 - Alignment in poll list #828
 - Anonymous poll not saved #829
 - Wrong z-index on datepicker #830
 - Missing translations for Shift all date options #809
 - Pinned navigationItem "Deleted polls" #843
 - Changed vote icons #844
 - Autofocus and cursor pointer #827
 - Button style #848
 - Remove Participants from vote #736
 - Add login link in public votes #857
 - Fix date sorting #877
 - Overwork navigation filters #865
 - Added filter for expired polls #878
 - Share poll via email #822
 - Fix notification mail contains user names in anonymous polls #871
 - Fix double loading of poll list #870
 - Allow creating of option sequences for date polls #649
 - Permanently delete polls #823
 - Fix some design / UX improvements #841, #848, #884,

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
 - Delete comments (#193)
 - Enter user name in public shared polls in a modal
 - Fixed routing error in NC16 (#787)
 - Load subscription on route change (#788)
 - Show displayNames instead of userID (#715)
 - Reorder vote options in text polls (#529)

## [1.1.6] - 2020-01-26

 - AutoFocus poll title in creation dialog (#757)
 - Only count current user, if he actually voted (#759)
 - Redirect old public polls routes to new route (#761)
 - Avoid error on creating share (#763)
 - Changing popovermenu to Actions (#594 and #754 )
 - Updated design according to file lists
 - Sorting polls in poll list, default creation date desc (#559 and #717)
 - Updated dependencies
 - 12-hour clock bug bug (#780)

## [1.0.0] - 2020-01-20

 - Huge update of polls
 - Completely rewritten as a vue app
 - Vote, edit poll in one page
 - Instant persisting of votes and configuration
 - Changed sharing

## [0.10.4] - 2019-11-27

  - Nextcloud 17

## [0.10.2] - 2019-03-13
  - Cannot share poll (only share option)

## [0.10.1] - 2019-03-02
### Fixed
  - Pull down on three-dot menu hidden for first participant

## [0.10.0] - 2019-02-24
### Added
  - Main list page
    - rewrite as a vue app
    - Improved UI
  - Ability to clone any poll and shift date options (#323, #245)
  - Design updates to vote page
  - Some more UI enhancements
  - Maybe option for a poll is configurable
### Fixed
  - "user_" / "group_" prefix
  - User name is prefixed with user_, + incorrect translation
  - Polls with expire date could not be created/edited
  - Send comment bug
  - Not possible to vote for none of the options
  - "Create Poll" button disabled after failed validation
  - Fix query params in eventmapper
  - No difference between hidden and open poll

## [0.9.5] - 2018-12-22
### Fixed
  - Update to 0.9.4 failed for postgres database
  - Update to 0.9.3 failed for postgresql database

## [0.9.4] - 2018-12-18
### Fixed
  - Polls upgrade leads to NotNullConstraintViolationException
  - Update to 0.9.3 failed for postgresql database
  - Fix color variable name in list.scss

## [0.9.3] - 2018-12-18
### Fixed
  - Fix minor problem with migration

## [0.9.1] - 2018-12-11
### Added
  - Create/edit page
    - rewrite as a vue app
    - Improved UI
	- Introduced new NC date time picker from vue-nextcloud
	- Introduced multiselect from vue-nextcloud
	- added option to allow "maybe" vote

  - Vote page
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
 - Display user's display name instead of user name (#402)
 - Support for asynchronus operations (#371)
 - ... a lot more minor bugs

See https://github.com/nextcloud/polls/milestone/9?closed=1 for all changes and additions.

## [0.8.3] - 2018-08-30
### Fixed
 - Display own participation in polls in list view

## [0.8.2] - 2018-08-25
### Added
 - Compatibility to NC 14 #360
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
 - Removing header elements for public polls
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
