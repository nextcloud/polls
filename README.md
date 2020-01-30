# Polls

[![Build Status](https://img.shields.io/travis/nextcloud/polls.svg?style=flat-square)](https://travis-ci.org/nextcloud/polls)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/nextcloud/polls.svg?style=flat-square)](https://scrutinizer-ci.com/g/nextcloud/polls)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/nextcloud/polls.svg?style=flat-square)](https://scrutinizer-ci.com/g/nextcloud/polls)
[![Software License](https://img.shields.io/badge/license-AGPL-brightgreen.svg?style=flat-square)](LICENSE)

This is a poll app, similar to doodle or dudle, for Nextcloud written in PHP and JS / Vue.

**Note**: ownCloud is **no longer** supported! Last (confirmed) working version is 0.8.1 and is released in the oC marketplace.
**Note**: IE11 users will face some CSS problems (see #541). Please change to a compatible browser (Firefox, Chrome, Edge, etc.). Or better: don't even try this browser

### Features
- :bar_chart: Create / edit polls (datetimes and texts)
- :date: Set expiration date
- :lock: Restrict access (all site users or invited users only)
- :speech_balloon: Comments

### Bugs
- https://github.com/nextcloud/polls/issues

### Screenshots
Create a new poll from the navigation bar and get an overview of your polls
![Overview](screenshots/overview.png)

Vote and comment
![Vote](screenshots/vote.png)

Edit poll inside the vote page
![Edit poll](screenshots/edit-poll.png)
![Edit options](screenshots/options.png)

Add shared links to your poll
![Share poll](screenshots/shares.png)

View the vote page on mobiles (Turn phone to landscape to see th full table)
![Vote mobile portrait](screenshots/mobile-portrait.png)

Only the owner can edit the poll. Granting access to admin users will be available in the next version.

## Installation / Update
This app is supposed to work on Nextcloud version 16+.

### Install latest release
You can download and install the latest release from the [Nextcloud app store](https://apps.nextcloud.com/apps/polls).

### Install from git
If you want to run the latest development version from git source, you need to clone the repo to your apps folder:

```
git clone https://github.com/nextcloud/polls.git
```

* Install dev environment with ```make dev-setup```
* Compile polls.js with ```make build-js-production``` or ```npm run build```
* Run a complete build with ```make all``` (installs dev env, runs linter and builds the polls.js)

## Contribution Guidelines
Please read the [Code of Conduct](https://nextcloud.com/community/code-of-conduct/). This document offers some guidance
to ensure Nextcloud participants can cooperate effectively in a positive and inspiring atmosphere, and to explain how together
we can strengthen and support each other.

For more information please review the [guidelines for contributing](https://github.com/nextcloud/server/blob/master/.github/CONTRIBUTING.md) to this repository.
