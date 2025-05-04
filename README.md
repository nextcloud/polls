<!--
  - SPDX-FileCopyrightText: 2016 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Note: This is the vue3 branch (v8.x), which still is in alpha state and replaced the [master branch](https://github.com/nextcloud/polls/tree/master-7) (v7.x) as main branch
## For the current release branch (v7.x) please switch to the [master branch](https://github.com/nextcloud/polls/tree/master-7)

# Polls - an app, similar to doodle or DuD-Poll, for Nextcloud written in PHP and JS/Vue.
![psalm](https://github.com/nextcloud/polls/actions/workflows/static-analysis.yml/badge.svg)
![tests](https://github.com/nextcloud/polls/actions/workflows/phpunit.yml/badge.svg)
![puild](https://github.com/nextcloud/polls/actions/workflows/nodejs.yml/badge.svg)
![lint](https://github.com/nextcloud/polls/actions/workflows/lint.yml/badge.svg)
[![Dependabot status](https://img.shields.io/badge/Dependabot-enabled-brightgreen.svg?longCache=true&style=flat-square&logo=dependabot)](https://dependabot.com)
[![Software License](https://img.shields.io/badge/license-AGPL-brightgreen.svg?style=flat-square)](COPYING)
[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/polls)](https://api.reuse.software/info/github.com/nextcloud/polls)

# Free meeting schedule tool
- :next_track_button: Easy poll creation
- :hammer_and_wrench: Highly customizable
    - :envelope: Make your poll confidential by hiding the results until you want them to be discovered
    - :dark_sunglasses: Obfuscate participants' names from other participants
    - :timer_clock: Set an automatic expiry date
    - :heavy_plus_sign: Allow participants to add more options
    - :white_check_mark: Limit votes per option or user
    - ... :currency_exchange: and a lot more
- :mailbox_with_mail: Invite everyone you want
- :rocket: Export your poll to different spreadsheet formats or HTML
- :red_envelope: Let Polls automatically remind your invited users
- :speech_balloon: Comments
- :ballot_box_with_check: Confirm options after poll closing
- :loudspeaker: Subscribe to notifications per poll
- :date: Get hints about possible conflicting entries in your calendar around the date option
- :toolbox: Usable via REST-API
- Supports the following nextcloud apps
    - Circles
    - Contacts
    - Activity

## Installation / Update
This app is supposed to work on Nextcloud version 21+.

### Install latest release
You can download and install the latest release from the [Nextcloud app store](https://apps.nextcloud.com/apps/polls).

## Available occ commands
| Command | Description |
| - | - |
| `polls:db:clean-migrations`                                                  | Remove obsolete migrations, which are no more needed         |
| `polls:db:purge`                                                             | Drop Polls' tables and remove migration and settings records |
| `polls:db:rebuild`                                                           | Rebuild Polls' database including indices                    |
| `polls:index:create`                                                         | Create all necessary indices and foreign key constraints     |
| `polls:index:remove`                                                         | Remove all indices                                           |
| `polls:poll:transfer-ownership  <source-user> <target-user>`                 | Transfer poll ownership from  <source-user> to <target-user> |
| `polls:share:add [--user USER] [--group GROUP] [--email EMAIL] [--] <id>`    | Add user/group/email with <id> to shares                     |
| `polls:share:remove [--user USER] [--group GROUP] [--email EMAIL] [--] <id>` | Remove user/group/email with <id> from shares                |
## Support
- Report a bug or request a feature:  https://github.com/nextcloud/polls/issues
- Community support: https://help.nextcloud.com/c/apps/polls/

## Screenshots
#### Manage your polls and create new ones
![Manage Polls](screenshots/overview.png)

#### Many configuration options
![Vote](screenshots/edit-poll.png)

#### Share your poll with other people
![Edit poll](screenshots/share.png)

#### Vote on mobile
![Share poll](screenshots/vote.png)

### Install from git
If you want to run the latest development version from git source, you need to clone the repo to your apps folder:

```
git clone https://github.com/nextcloud/polls.git
```

* Install dev environment with ```make setup-dev``` or
* install runtime environment with ```make setup-build```
* Compile javascript with ```npm run build```
* Run a complete build with ```make appstore``` (Find the output in the build directory)
* call `occ app:enable polls` to enable Polls

### Installation variants

### First time install
Nextcloud executes
* unexecuted `migration classes` (not listed in the `*_migrations` table) and the
* `install` repair step.

### After a version update (changed version attribute in appinfo/info.xml)
Nextcloud executes
* `pre-migration` repair steps,
* unexecuted `migration classes` (not listed in the `*_migrations` table) and the
* `post-migration` repair steps

### Enabling already installed but disabled app without version change
Nextcloud executes
* `pre-migration` repair steps,
* unexecuted `migration classes` (not listed in the `*_migrations` table) and the
* `post-migration` repair steps and the
* `install` repair step

❗ As a compromise at the moment we allow the index creation to be ran twice when enabling the app via app store or `occ`, to ensure all indexes are created properly for every install/update/enabling path.

## Removing Polls from instance
Call `occ polls:db:purge` to remove Polls completely.
* removes all Polls related tables
* removes all Polls related migration records
* removes all Polls related app config records (this also disables Polls)

This does not remove Polls' files (call `occ app:remove polls` to remove it complete afterwards) but it resets Polls into an 'uninstalled' state. Enabling the app is then equivalent to a first time install and calls the migration and the install repair step (see above).

## Contribution Guidelines
Please read the [Code of Conduct](https://nextcloud.com/community/code-of-conduct/). This document offers some guidance to ensure Nextcloud participants can cooperate effectively in a positive and inspiring atmosphere, and to explain how together we can strengthen and support each other.

For more information please review the [guidelines for contributing](https://github.com/nextcloud/server/blob/master/.github/CONTRIBUTING.md) to this repository.
