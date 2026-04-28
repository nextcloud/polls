# Available occ Commands
Note: In any case make sure you have a backup of your database and only use these commands if you know what you are doing. Although no data loss is reported until now caused from the usage of these commands (especially these from the `polls:db` namespace).

All commands require polls to be enabled and are not available after uninstall or while in maintenance mode.

## Overview
|Command|Description|
|-|-|
|[`db:add-missing-indices`](#create-optional-indices)                                                     | Create optional indices (NC core command)    |
|[`maintenance:repair`](#run-repair-steps)                                                                | Run Polls post-migration repair steps (NC core command) |
|[`polls:index:remove:foreign-key-constraints`](#remove-foreign-key-constraints)                         | Remove foreign key constraints              |
|[`polls:index:remove:unique-indices`](#remove-unique-indices)                                            | Remove unique indices                        |
|[`polls:index:remove:optional`](#remove-optional-indices)                                                | Remove optional indices                      |
|[`polls:index:create`](#create-indices)                                                                  | Add unique indices and foreign key constraints |
|[`polls:db:clean-migrations`](#remove-migration-entries)                                                 | Remove migration entries                     |
|[`polls:db:fix`](#fix-table-structure)                                                                   | Fix table structure                          |
|[`polls:db:reset-unique-indices`](#reset-unique-indices)                                                 | Reset unique indices to named UNIQ_ form     |
|[`polls:db:reset-watch`](#refresh-watch-table)                                                           | Refresh watch table                          |
|[`polls:db:rebuild`](#rebuild-tables)                                                                    | Rebuild tables                               |
|[`polls:db:purge`](#purge-polls)                                                                         | Purge Polls                                  |
|[`polls:poll:transfer-ownership  <source-user> <target-user>`](#transfer-ownership)                      | Transfer ownership                           |
|[`polls:share:add [--user USER] [--group GROUP] [--email EMAIL] [--] <id>`](#invite-people)              | Invite people                                |
|[`polls:share:remove [--user USER] [--group GROUP] [--email EMAIL] [--] <id>`](#remove-share)            | Remove share                                 |


## Nextcloud Core Commands
These are Nextcloud core commands that trigger Polls-specific behavior.

### Create Optional Indices
**Command:** `occ db:add-missing-indices`

Creates missing optional indices for all apps, including Polls. Refer to the 'Administration settings' of your instance — missing indices are reported in the 'Security & warnings' section.

Keep in mind that the creation of optional indices can be time consuming.

### Run Repair Steps
**Command:** `occ maintenance:repair`

Triggers all registered `post-migration` repair steps for every enabled app, including Polls. The following Polls-specific repair steps are executed:

* **DropOrphanedIndices** — removes obsolete indices left over from previous versions
* **CreateUniqueIndices** — creates or updates unique indices (column-based, no unnecessary re-indexing)
* **UpdateHashes** — creates or updates hashes for votes and options
* **MigratePublicToOpen** — migrates access values from `public` to `open`

This command is useful after manual database interventions or when the repair steps did not complete successfully during an update.

## Indices
**Namespace:** `polls:index`

These commands are usually only for analysis or tests. So you should not need them under normal operation mode.

### Remove Foreign Key Constraints
**Command:** `occ polls:index:remove:foreign-key-constraints`

**Note:** This is highly **NOT RECOMMENDED**. These indices are responsible for database health and help avoiding orphaned records. The unique indices grant that by removing a poll all depending records from other tables like options and votes are removed from the database as well.
So, if you have to remove them, take care, that the indices are regenerated in time. Otherwise you may have to clean your database manually.

### Remove Unique Indices
**Command:** `occ polls:index:remove:unique-indices`

**Note:** This is highly **NOT RECOMMENDED**. These indices are responsible for database integrity. Besides the unique main key (the entity id) the indices avoid duplication of options, votes and more. Removing these indices may result in a broken application and duplicates have to be manually identified and removed, before the indices can get recreated again.

### Remove Optional Indices
**Command:** `occ polls:index:remove:optional`

Optional indices are for better database performance. Removing them does no harm to the application, but the database has to do full table scans, especially for complex joins. This can result in heavy performance issues.

### Create Indices
**Command:** `occ polls:index:create`

Recreates all foreign key constraints and unique indices. Existing indices covering the correct columns are kept regardless of their name — no unnecessary re-indexing occurs.

## Database
**Namespace:** `polls:db`

### Remove Migration Entries
**Command:** `occ polls:db:clean-migrations`

**Note:** Although this command removes only old polls related migration steps which are not used anymore, you should not use it because it may trigger old migration steps. If really necessary, this will be done while updating polls anyways.

### Fix Table Structure
**Command:** `occ polls:db:fix`

Checks all polls tables and updates their structure against the current schema definition. No indices are created, updated or removed. No data migration is executed.

### Reset Unique Indices
**Command:** `occ polls:db:reset-unique-indices`

**For testing purposes only.** Drops all unique indices from Polls tables and recreates them with explicit `UNIQ_` names. This simulates the pre-migration state to verify that the repair step handles existing named indices correctly without triggering an unnecessary re-indexing.

**Note:** This triggers a full re-indexing on all affected tables and can be time consuming on large installations.

### Refresh Watch Table
**Command:** `occ polls:db:reset-watch`

The watch table is for temporary usage and reports changes in time (especially for long polling and periodic polling). This can result in a rapidly rising amount of id numbers. This command resets the table to start again with id 0. This is done on every update of polls, so you probably should never need it.

### Rebuild Tables
**Command:** `occ polls:db:rebuild`

**Note:** This will not build a consistent database when downgrading to a prior major or minor version and new database columns have been added. No database columns get removed if not explicitly listed as orphaned and obsolete. In this case you have to remove these columns manually.

In any situation where the database seems to be corrupt, this command checks and corrects issues of the database. In detail it performs the following actions:
* Remove all foreign key constraints
* Remove all unique indices
* Remove all optional indices
* Remove orphaned tables and columns possibly left over from old installations
* Check the table schema and update all tables to the currently defined database schema
* Check hashes of the votes and options table and add missing ones (based on the unique index definitions)
  The hashes are short md5 hashes of the poll id and option texts and are essential for fast comparisons and for the unique indices
* Search for duplicates and orphaned entries and remove them (based on the unique indices including the foreign key constraints)
  Only one of the duplicates will be kept. Otherwise the index creation would fail.
* Recreate foreign key constraints and unique indices

To add the removed optional indices call `occ db:add-missing-indices` after the recreation finished without error.

### Purge Polls
**Command:** `occ polls:db:purge`

Unfortunately or luckily Nextcloud does not remove any tables from the database when uninstalling an app. If you plan to reinstall the app later and want to make sure your already persisted data can be used after the new installation, this is safe behavior. But if you just want to test polls and decide not to use it further, you have to live with orphaned tables inside your database.

This command allows you to remove all persisted data of polls and wipe it from your database. So before uninstalling polls you should call `occ polls:db:purge`. In detail this command executes the following actions:
* Purge all foreign key constraint child tables, to make sure parents can be removed as well
* Purge all foreign key constraint parent tables
* Purge any eventually left over tables of polls
* Remove all polls related migration records from `oc_migrations` to make sure a later reinstall is possible
* Remove all polls related appconfig settings from `oc_appconfig`

Then remove polls from the apps directory by calling `occ app:remove polls`

In case you already uninstalled polls, just install it (`occ app:install polls`) and follow the path above.

## Poll Actions
**Namespace:** `polls:poll`

### Transfer Ownership
**Command:** `occ polls:poll:transfer-ownership <source-user> <target-user>`

Transfers the ownership of all source user's polls from `<source-user>` to `<target-user>`

## Shares
**Namespace:** `polls:share`

### Invite People
**Command:** `occ polls:share:add [--user USER] [--group GROUP] [--email EMAIL] [--] <id>`

Invite people by username, group membership or by email address to poll `<id>`

### Remove Share
**Command:** `occ polls:share:remove [--user USER] [--group GROUP] [--email EMAIL] [--] <id>`

Remove share by username, group or email address from poll `<id>`
