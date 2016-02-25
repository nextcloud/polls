polls
=====
This is a poll app, similar to doodle or dudle, for owncloud written in PHP and JS/jQuery.
It is a rework of the already existing [polls app](https://github.com/raduvatav/polls) written by @raduvatav.

Features
========
- Create/Edit polls (datetimes _and_ texts)
- Set expiration date
- Restrict access (only oc members, certain groups/users, hidden and public)
- Comments

Requirements
============
- ownCloud 8.1 or later
- [This PR](https://github.com/owncloud/core/pull/22497) from core (Wrong status code in the `RedirectResponse`), otherwise you'll get a blank page after create/edit a poll or voting

Bugs
====
- Blank page after create/edit a poll or voting (see Requirements for a temporary fix)

Installation
============
Put the files under `<owncloud_dir>/apps/polls` and enable it in the ownCloud apps settings.

Misc
====
The app is also available at the [appstore](https://apps.owncloud.com/content/show.php/Polls?content=174671).

Screenshots
===========
![](https://github.com/v1r0x/polls/blob/master/screenshots/new-poll.png)
![](https://github.com/v1r0x/polls/blob/master/screenshots/overview.png)
![](https://github.com/v1r0x/polls/blob/master/screenshots/vote.png)
