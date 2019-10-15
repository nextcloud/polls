# Polls

[![Build Status](https://img.shields.io/travis/nextcloud/polls.svg?style=flat-square)](https://travis-ci.org/nextcloud/polls)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/nextcloud/polls.svg?style=flat-square)](https://scrutinizer-ci.com/g/nextcloud/polls)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/nextcloud/polls.svg?style=flat-square)](https://scrutinizer-ci.com/g/nextcloud/polls)
[![Software License](https://img.shields.io/badge/license-AGPL-brightgreen.svg?style=flat-square)](LICENSE)

This is a poll app, similar to doodle or dudle, for Nextcloud written in PHP and JS / jQuery.
It is a rework of the already existing [polls app](https://github.com/raduvatav/polls) written by @raduvatav.

**Note**: ownCloud is **no longer** supported! Last (confirmed) working version is 0.8.1 and is released in the oC marketplace.
**Note**: IE11 users will face some CSS problems (see #541). Please change to a compatible browser (Firefox, Chrome, Edge, etc.)

### Features
- :bar_chart: Create / edit polls (datetimes _and_ texts)
- :date: Set expiration date
- :lock: Restrict access (only logged in users, certain groups / users, hidden and public)
- :speech_balloon: Comments

### Bugs
- https://github.com/nextcloud/polls/issues

### Screenshots
Overview of all polls
![Overview](https://github.com/nextcloud/polls/blob/master/screenshots/overview.png)

The vote page
![Vote](https://github.com/nextcloud/polls/blob/master/screenshots/vote.png)

Creating a new poll
![New poll](https://github.com/nextcloud/polls/blob/master/screenshots/edit-poll.png)

View the vote page on mobiles
![Vote mobile portrait](https://github.com/nextcloud/polls/blob/master/screenshots/vote-mobile-portrait.png)

Turn phone to landscape to see details
![Vote mobile landscape](https://github.com/nextcloud/polls/blob/master/screenshots/vote-mobile-landscape.png)

## Installation / Update
This app is supposed to work on Nextcloud version 13+.

### Install latest release
You can download and install the latest release from the [Nextcloud app store](https://apps.nextcloud.com/apps/polls) or a legacy version from the [ownCloud marketplace](https://marketplace.owncloud.com/apps/polls).

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
