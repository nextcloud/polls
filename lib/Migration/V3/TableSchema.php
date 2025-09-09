<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration\V4;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\Log;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollGroup;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\Watch;
use OCP\DB\Types;

/**
 * Database definition for installing and migrations
 * These definitions contain the base database layout
 * used for initial migration to version 3.x from all prior versions
 */

abstract class TableSchema {
	/**
	 * define all foreign key indices
	 * Parentable => [Childable => ['constraintColumn' => 'columnName']]
	 */
	public const FK_INDICES = [
		Poll::TABLE => [
			Comment::TABLE => ['constraintColumn' => 'poll_id'],
			Log::TABLE => ['constraintColumn' => 'poll_id'],
			Subscription::TABLE => ['constraintColumn' => 'poll_id'],
			Option::TABLE => ['constraintColumn' => 'poll_id'],
			Vote::TABLE => ['constraintColumn' => 'poll_id'],
			Watch::TABLE => ['constraintColumn' => 'poll_id'],
			PollGroup::RELATION_TABLE => ['constraintColumn' => 'poll_id'],
		],
		PollGroup::TABLE => [
			PollGroup::RELATION_TABLE => ['constraintColumn' => 'group_id'],
		],
	];

	/**
	 * define useful common indices, which are not unique
	 * table => ['name' => 'indexName', 'unique' => false, 'columns' => ['column1', 'column2']]
	 * @deprecated since 8.3.0, use OPTIONAL_INDICES instead
	 */
	public const COMMON_INDICES = [
		'polls_polls_owners_non_deleted' => ['table' => Poll::TABLE, 'name' => 'polls_polls_owners_non_deleted', 'unique' => false, 'columns' => ['owner', 'deleted']],
	];

	/**
	 * define useful optional indices, which are not unique
	 * tableName => [
	 * 	indexName => ['columns' => [column1, column2, ...]],
	 * ...]
	 */
	public const OPTIONAL_INDICES = [
		Poll::TABLE => [
			'polls_polls_owners_non_deleted' => ['columns' => ['owner', 'deleted']],
			'polls_polls_deleted' => ['columns' => ['deleted']],
			'polls_polls_owners' => ['columns' => ['owner']],
		],
		Option::TABLE => [
			'polls_options_non_deleted' => ['columns' => ['poll_id', 'deleted']],
			'polls_options_hash' => ['columns' => ['poll_id', 'poll_option_hash', 'deleted']],
			'polls_options_owner' => ['columns' => ['poll_id', 'owner']],
		],
		Share::TABLE => [
			'polls_shares_user' => ['columns' => ['poll_id', 'user_id', 'deleted']],
			'polls_shares_types' => ['columns' => ['poll_id', 'type', 'deleted']],
			'polls_group_shares_user' => ['columns' => ['group_id', 'user_id', 'deleted']],
		],
		Vote::TABLE => [
			'polls_votes_answers' => ['columns' => ['poll_id', 'user_id']],
			'polls_votes_user' => ['columns' => ['poll_id', 'vote_answer', 'user_id']],
			'polls_votes_hash' => ['columns' => ['poll_id', 'vote_option_hash', 'deleted']],
		],
	];

	/**
	 * define unique indices, which are not primary keys
	 * tableName => [
	 * 	indexName => ['columns' => [column1, column2, ...]],
	 * ...]
	 */
	public const UNIQUE_INDICES = [
		Option::TABLE => [
			'UNIQ_options' => ['columns' => ['poll_id', 'poll_option_hash', 'timestamp']],
		],
		Log::TABLE => [
			'UNIQ_unprocessed' => ['columns' => ['processed', 'poll_id', 'user_id', 'message_id']],
		],
		Subscription::TABLE => [
			'UNIQ_subscription' => ['columns' => ['poll_id', 'user_id']],
		],
		Share::TABLE => [
			'UNIQ_shares' => ['columns' => ['poll_id', 'group_id', 'user_id']],
			'UNIQ_token' => ['columns' => ['token']],
		],
		Vote::TABLE => [
			'UNIQ_votes' => ['columns' => ['poll_id', 'user_id', 'vote_option_hash']],
		],
		Preferences::TABLE => [
			'UNIQ_preferences' => ['columns' => ['user_id']],
		],
		Watch::TABLE => [
			'UNIQ_watch' => ['columns' => ['poll_id', 'table', 'session_id']],
		],
		PollGroup::RELATION_TABLE => [
			'UNIQ_poll_group_relation' => ['columns' => ['poll_id', 'group_id']],
		],
	];

	/**
	 * obsolete migration entries, which can be deleted
	 */
	public const GONE_MIGRATIONS = [
		'0001Date20000101120000',
		'0001Date20000101120001',
		'0009Date20181125051900',
		'0009Date20181125061900',
		'0009Date20181125062101',
		'0010Date20191227063812',
		'0010Date20200119101800',
		'0101Date20200122194300',
		'0103Date20200130171244',
		'0104Date20200205104800',
		'0104Date20200314074611',
		'0105Date20200508211943',
		'0105Date20200523142076',
		'0105Date20200704084037',
		'0105Date20200903172733',
		'0106Date20201031080745',
		'0106Date20201031080946',
		'0107Date20201210160301',
		'0107Date20201210204702',
		'0107Date20201210213303',
		'0107Date20201217071304',
		'0107Date20210101161105',
		'0107Date20210104135506',
		'0107Date20210121220707',
		'0108Date20210117010101',
		'0108Date20210127135802',
		'0108Date20210207134703',
		'0108Date20210307130001',
		'0108Date20210307130003',
		'0108Date20210307130009',
		'0109Date20210323120002',
		'030000Date20210611120000',
		'030000Date20210704120000',
		'030200Date20210912120000',
		'030400Date20211125120000',
		'040100Date20221030070000',
		'041000Date20221221070000',
		'040101Date20230119080000',
		'040102Date20230123072601',
		'050005Date20230506203301',
		'050400Date20231011211203',
		'050100Date20230515083001',
		'080000Date20250413195001',
		'080000Date20250604195002',
	];

	/**
	 * define obsolete tables to drop
	 */
	public const GONE_TABLES = [
		'polls_events', // dropped in 1.0
		'polls_dts', // dropped in 0.9
		'polls_txts', // dropped in 0.9
		'polls_particip', // dropped in 0.9
		'polls_particip_text', // dropped in 0.9
		'polls_test', // invalid table, accidentally introduced in an old beta version
	];

	/**
	 * define obsolete columns to drop
	 */
	public const GONE_COLUMNS = [
		Poll::TABLE => [
			'full_anonymous', // dropped in 3.0, orphaned
			'options', // dropped in 3.0, orphaned
			'settings', // dropped in 3.0, orphaned
			'important', // dropped in 6.1, not used anymore
		],
		Comment::TABLE => [
			'dt', // dropped in 3.0, orphaned
		],
		Share::TABLE => [
			'user', // dropped in 1.01
			'user_email', // dropped in 1.06 and migrated to email_address
			'revoked', // introduced in 5.4.0-beta3 and replaced with column 'locked' in 5.4.0-beta5, no migration
		],
		Log::TABLE => [
			'message', // dropped in 1.07, orphaned
			// 'processed', // dropped in 8.1, orphaned
		],
		Option::TABLE => [
			'poll_option_hash_bin', // used and dropped in dev branch (8.3.x), leave here for security
		],
		Vote::TABLE => [
			'vote_option_hash_bin', // used and dropped in dev branch (8.3.x), leave here for security
		],
	];

	/**
	 * define table structure
	 *
	 * IMPORTANT: After adding or deletion check queries in ShareMapper
	 */
	public const TABLES = [
		PollGroup::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'created' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'title' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 128]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
			'description' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => null, 'length' => 65535]],
			'title_ext' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 128]],
		],
		PollGroup::RELATION_TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'group_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Poll::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'type' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'datePoll', 'length' => 64]],
			'title' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 128]],
			'description' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => null, 'length' => 65535]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 256]],
			'created' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'expire' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'access' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'private', 'length' => 1024]],
			'anonymous' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'allow_maybe' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'allow_proposals' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'disallow', 'length' => 64]],
			'proposals_expire' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'vote_limit' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'option_limit' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'show_results' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'always', 'length' => 64]],
			'admin_access' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'allow_comment' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'hide_booked_up' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'use_no' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'last_interaction' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'misc_settings' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => null, 'length' => 65535]],
			'voting_variant' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'simple', 'length' => 64]],
		],
		Option::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'poll_option_text' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 1024]],
			'poll_option_hash' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 32]],
			'timestamp' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'duration' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'order' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'confirmed' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
			'released' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Vote::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
			'vote_option_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'vote_option_text' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 1024]],
			'vote_option_hash' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 32]],
			'vote_answer' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 64]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Comment::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
			'comment' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 1024]],
			'timestamp' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'confidential' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'recipient' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 256]],
		],
		Share::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'group_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'token' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 64]],
			'type' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 64]],
			'label' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
			'display_name' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 256]],
			'email_address' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 256]],
			'invitation_sent' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'reminder_sent' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'locked' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'misc_settings' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => null, 'length' => 65535]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Subscription::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
		],
		Log::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 256]],
			'display_name' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 256]],
			'message_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null, 'length' => 64]],
			'created' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'processed' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Watch::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'table' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 64]],
			'updated' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'session_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null]],
		],
		Preferences::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 256]],
			'timestamp' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'preferences' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => null, 'length' => 65535]],
		],
	];
}
