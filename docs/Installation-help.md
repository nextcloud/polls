<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Update Issues

When Polls gets updated and needs changed classes while updating a situation may occur which lets the migration fail. This is because Polls relies on its own declarative database definitions. On updates a situation now can occur where changed classes, which are used while updating, get not loaded properly and the old version of these classes are still cached and loaded.

This can lead to two states:

## Migration leaves site in maintenance mode
In this situation Nextcloud refuses any further action and stays in maintenance mode.
To fix this follow these steps:
* call `occ app:disable polls`
* call `occ maintenance:mode --off`
* call `occ app:enable polls`
After that the site should be up again and Polls updated to the latest version. If this actions lead to no success end the site ended in maintenance mode again, keep polls disabled and turn off the maintenance mode. Please report that issue [here](https://github.com/nextcloud/polls/issues).

## Polls stays disabled
If Polls just did not get enabled and the update terminated with an error message, try to enable it again.
After that Polls should be updated to the latest version. If Polls resumed back to disabled state again, keep polls disabled and please report that issue [here](https://github.com/nextcloud/polls/issues).

**This should be no issue anymore since v8.3, because Polls uses versions for schema definitions wich force the updater to use the new/changed classes.**

# Installation variants
## Install via Git
If you want to run the latest development version from git source, you need to clone the repo to your apps folder:
```
git clone https://github.com/nextcloud/polls.git
```
* Install runtime environment with `make setup-build`
* Compile javascript with `npm run build`
* call `occ app:enable polls` to enable Polls

* With `make appstore` You can create a distribution build (Find the output in the build directory)
* Install dev environment with `make setup-dev`

## Install via Appstore
Got to your apps page (https://nc.foo.com/settings/apps) and search for Polls. Just enable or install the app with the button.

## Install via occ
Call `occ app:install polls` or `occ app:enable polls`

# Install Paths of Nextcloud
Depending on the installation variant different steps are executed. (This mainly for self documentation)

## Enabling Polls (first install or re-enable)
Every time the app is enabled — whether for the first time or after being disabled — Nextcloud executes:
* unexecuted `migration classes` (not listed in the `*_migrations` table)
* `install` repair steps — none currently registered

## Version Update
After a version update (changed version attribute in appinfo/info.xml) Nextcloud executes:
* `pre-migration` repair steps (none currently registered)
* unexecuted `migration classes` (not listed in the `*_migrations` table)
* `post-migration` repair steps:
  * **DropOrphanedIndices** — removes obsolete indices from previous versions
  * **CreateUniqueIndices** — creates or updates unique indices (column-based)
  * **UpdateHashes** — creates or updates hashes for votes and options
  * **MigratePublicToOpen** — migrates access values from `public` to `open`

Note: `post-migration` repair steps also run when executing `occ maintenance:repair`.

# Install via File Base
Download the desired version from the [releases page](https://github.com/nextcloud/polls/releases) and extract it to your app folder where common apps reside. After extraction there should be a polls folder containing the Polls app.

If updating this way, make sure the Polls folder gets removed or emptied before. Otherwise side effects can occur while migrating or on run time.

# Removing Polls
Call `occ polls:db:purge` to remove Polls completely.
* removes all Polls related tables
* removes all Polls related migration records
* removes all Polls related app config records (this also disables Polls)

This does not remove Polls' files (call `occ app:remove polls` to remove it complete afterwards) but it resets Polls into an 'uninstalled' state. Enabling the app is then equivalent to a first time install and calls the migration and the install repair step (see above).
