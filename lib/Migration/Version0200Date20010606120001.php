<?php
/**
 * @copyright Copyright (c) 2017 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Migration;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\TextType;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0200Date20010606120001 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	/** @var bool */
	protected $migrateOrder;

	/** @var bool */
	protected $migrateInvitationSent;

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
		$this->migrateOrder = false;
		$this->migrateInvitationSent = false;
	}

	/**
	 * Make an initial migration from schemas before polls 2.0
	 * =======================================================
	 * This migration can be safely removed, if the initial migration is
	 * still present, but migrations from version before 2.0 will get lost
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/**
		 * Drop old tables, if they still exist. Make sure no unused tables
		 * prior to polls 2.0 are present
		 */

		// Version0010Date20200119101800 -> drop table 'polls_events'
		if ($schema->hasTable('polls_events')) {
			$schema->dropTable('polls_events');
		}

		// Version0009Date20181125062101 -> drop table 'polls_dts'
		if ($schema->hasTable('polls_dts')) {
			$schema->dropTable('polls_dts');
		}

		// Version0009Date20181125062101 -> drop table 'polls_txts'
		if ($schema->hasTable('polls_txts')) {
			$schema->dropTable('polls_txts');
		}

		// Version0009Date20181125062101 -> drop table 'polls_particip'
		if ($schema->hasTable('polls_particip')) {
			$schema->dropTable('polls_particip');
		}

		// Version0009Date20181125062101 -> drop table 'polls_particip_text'
		if ($schema->hasTable('polls_particip_text')) {
			$schema->dropTable('polls_particip_text');
		}

		/**
		 * Apply changes to the polls_polls table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_polls')) {
			$table = $schema->getTable('polls_polls');

			/** Add missing columns */

			// Version0105Date20200903172733 -> introduce column 'important'
			if (!$table->hasColumn('important')) {
				$table->addColumn('important', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			// Version0107Date20210104135506 -> introduce column 'proposals_expire'
			if (!$table->hasColumn('option_limit')) {
				$table->addColumn('option_limit', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			// Version0108Date20210307130003 -> introduce column 'allow_comment'
			if (!$table->hasColumn('allow_comment')) {
				$table->addColumn('allow_comment', 'integer', [
					'notnull' => true,
					'default' => 1,
					'length' => 11,
				]);
			}

			// Version0108Date20210307130003 -> introduce column 'hide_booked_up'
			if (!$table->hasColumn('hide_booked_up')) {
				$table->addColumn('hide_booked_up', 'integer', [
					'notnull' => true,
					'default' => 1,
					'length' => 11,
				]);
			}

			// Version0109Date20210323120002 -> introduce column 'allow_proposals'
			if (!$table->hasColumn('allow_proposals')) {
				$table->addColumn('allow_proposals', 'string', [
					'notnull' => true,
					'default' => 'disallow',
					'length' => 64,
				]);
			}

			// Version0109Date20210323120002 -> introduce column 'use_no'
			if (!$table->hasColumn('use_no')) {
				$table->addColumn('use_no', 'integer', [
					'notnull' => true,
					'default' => 1,
					'length' => 11,
				]);
			}

			// Version0109Date20210323120002 -> introduce column 'proposals_expire'
			if (!$table->hasColumn('proposals_expire')) {
				$table->addColumn('proposals_expire', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			/** Change attributes of columns */

			// Version0104Date20200314074611 -> column 'description' type changed
			if ($table->hasColumn('description')) {
				$column = $table->getColumn('description');
				if (!($column->getType() instanceof TextType)) {
					$table->changeColumn('description', [
						'type' => Type::getType('text'),
						'notnull' => false,
						'default' => '',
					]);
				}
			}
			// Version0200Date20010606120000 -> column 'allow_maybe' length changed
			$table->changeColumn('allow_maybe', [
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'title' default changed
			$table->changeColumn('title', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'description' default changed
			$table->changeColumn('description', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'anonymous' length changed
			$table->changeColumn('anonymous', [
				'length' => 11,
			]);

			/** Drop obsolete columns */

			// Version0200Date20010606120000 -> drop 'full_anonymous'
			if ($table->hasColumn('full_anonymous')) {
				$table->dropColumn('full_anonymous');
			}

			// Version0200Date20010606120000 -> drop 'options'
			if ($table->hasColumn('options')) {
				$table->dropColumn('options');
			}

			// Version0200Date20010606120000 -> drop 'settings'
			if ($table->hasColumn('settings')) {
				$table->dropColumn('settings');
			}
		}

		/**
		 * Apply changes to the polls_options table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_options')) {
			$table = $schema->getTable('polls_options');

			/** Add missing columns */

			// Version0103Date20200130171244 -> introduce column 'order'
			if (!$table->hasColumn('order')) {
				// migrate in postSchemaChange
				$this->migrateOrder = true;
				$table->addColumn('order', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			// Version0105Date20200508211943 -> introduce column 'confirmed'
			if (!$table->hasColumn('confirmed')) {
				$table->addColumn('confirmed', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			// Version0108Date20210307130003 -> introduce column 'duration'
			if (!$table->hasColumn('duration')) {
				$table->addColumn('duration', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			// Version0109Date20210323120002 -> introduce column 'owner'
			if (!$table->hasColumn('owner')) {
				$table->addColumn('owner', 'string', [
					'notnull' => false,
					'default' => '',
					'length' => 64,
				]);
			}

			// Version0109Date20210323120002 -> introduce column 'released'
			if (!$table->hasColumn('released')) {
				$table->addColumn('released', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			/** Change attributes of columns */

			// Version0107Date20201210204702 -> column 'poll_option_text' notnull, default changed
			$table->changeColumn('poll_option_text', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0107Date20201210204702 -> column 'timestamp' notnull, default changed
			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('timestamp', [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'poll_id' notnull, default changed
			$table->changeColumn('poll_id', [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('id', [
				'length' => 11,
			]);
		}

		/**
		 * Apply changes to the polls_votes table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_votes')) {
			$table = $schema->getTable('polls_votes');

			/** Change attributes of columns */

			// Version0107Date20201210213303 -> column 'user_id' notnull, default changed
			$table->changeColumn('user_id', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0107Date20201210213303 -> column 'vote_option_text' notnull, default changed
			$table->changeColumn('vote_option_text', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('id', [
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'poll_id' notnull, default, length changed
			$table->changeColumn('poll_id', [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'vote_answer' notnull, default changed
			$table->changeColumn('vote_answer', [
				'notnull' => false,
				'default' => '',
			]);
		}

		/**
		 * Apply changes to the polls_comments table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_comments')) {
			$table = $schema->getTable('polls_comments');

			/** Add missing columns */

			// Version0010Date20191227063812 -> added 'timestamp'
			if (!$table->hasColumn('timestamp')) {
				$table->addColumn('timestamp', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			/** Change attributes of columns */

			// Version0200Date20010606120000 -> column 'poll_id' notnull, default, length changed
			$table->changeColumn('poll_id', [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'user_id' default changed
			$table->changeColumn('user_id', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'comments' default changed
			$table->changeColumn('comment', [
				'notnull' => false,
				'default' => '',
			]);

			/** Drop obsolete columns */
			// Version0200Date20010606120000 -> drop 'settings'
			if ($table->hasColumn('dt')) {
				$table->dropColumn('dt');
			}
		}

		/**
		 * Apply changes to the polls_notif table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_share')) {
			$table = $schema->getTable('polls_share');

			/** Change attributes of columns */

			// Version0200Date20010606120000 -> column 'poll_id' notnull, default, length changed
			$table->changeColumn('poll_id', [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'user_id' default changed
			$table->changeColumn('user_id', [
				'notnull' => false,
				'default' => '',
			]);
		}

		/**
		 * Apply changes to the polls_share table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_share')) {
			$table = $schema->getTable('polls_share');

			/** Add missing columns */
			// Version0105Date20200704084037 -> introduce column 'invitation_sent'
			if (!$table->hasColumn('invitation_sent')) {
				// migrate in postSchemaChange
				$this->migrateInvitationSent = true;
				$table->addColumn('invitation_sent', 'integer', [
					'notnull' => true,
					'default' => 0,
					'length' => 11,
				]);
			}

			// Version0106Date20201031080745 -> introduce column 'display_name'
			if (!$table->hasColumn('display_name')) {
				$table->addColumn('display_name', 'string', [
					'notnull' => false,
					'default' => '',
					'length' => 64,
				]);
			}

			// Version0106Date20201031080745 -> introduce column 'email_address'
			// ignore migration from user_email to email_address
			if (!$table->hasColumn('email_address')) {
				$table->addColumn('email_address', 'string', [
					'notnull' => false,
					'default' => '',
					'length' => 254,
				]);
			}

			/** Change attributes of columns */

			// Version0107Date20201217071304 -> column 'user_id' notnull, default changed
			$table->changeColumn('user_id', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('id', [
				'length' => 11,
			]);

			// Version0200Date20010606120000 -> column 'token' default changed
			$table->changeColumn('token', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'type' default changed
			$table->changeColumn('type', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'poll_id' default, length changed
			$table->changeColumn('poll_id', [
				'default' => 0,
				'length' => 11,
			]);


			/** Drop obsolete columns */

			// Version0101Date20200122194300 -> drop 'user'
			if ($table->hasColumn('user')) {
				$table->dropColumn('user');
			}

			// Version0106Date20201031080946 -> drop 'user_email'
			// ignore migration from user_email to email_address
			if ($table->hasColumn('user_email')) {
				$table->dropColumn('user_email');
			}
		}

		/**
		 * Apply changes to the polls_log table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_log')) {
			$table = $schema->getTable('polls_log');

			/** Change attributes of columns */

			// Version0107Date20210121220707 -> column 'poll_id' default changed
			// Version0200Date20010606120000 -> column 'poll_id' length changed
			$table->changeColumn('poll_id', [
				'default' => 0,
				'length' => 11,
			]);

			// Version0107Date20210121220707 -> column 'user_id' length, notnull, default changed
			$table->changeColumn('user_id', [
				'notnull' => false,
				'default' => '',
				'length' => 64,
			]);

			// Version0107Date20210121220707 -> column 'message_id' notnull, default changed
			$table->changeColumn('message_id', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('id', [
				'length' => 11,
			]);

			/** Drop obsolete columns */

			// Version0107Date20210121220707 -> drop column 'message'
			if ($table->hasColumn('message')) {
				$table->dropColumn('message');
			}
		}

		/**
		 * Apply changes to the polls_preferences table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_preferences')) {
			$table = $schema->getTable('polls_preferences');

			/** Change attributes of columns */

			// Version0106Date20201031080745 -> column 'user_id' default changed
			$table->changeColumn('user_id', [
				'notnull' => false,
				'default' => '',
			]);

			// Version0106Date20201031080745 -> column 'preferences' notnull changed
			$table->changeColumn('preferences', [
				'notnull' => false,
			]);

			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('id', [
				'length' => 11,
			]);
		}

		/**
		 * Apply changes to the polls_watch table, which resided in migrations
		 * prior to polls 2.0
		 */
		if ($schema->hasTable('polls_watch')) {
			$table = $schema->getTable('polls_watch');

			/** Change attributes of columns */

			// Version0200Date20010606120000 -> column 'id' length changed
			$table->changeColumn('id', [
				'length' => 11,
			]);
		}

		return $schema;
	}

	/**
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$query = $this->connection->getQueryBuilder();

		// Version0103Date20200130171244 -> introduce column 'order'
		// possibly not needed any more, because in the entity the timestamp is
		// returned as order, if timestamp is set.
		if ($this->migrateOrder) {
			$query->update('polls_options')
				->set('order', 'timestamp');
		}

		// Version0105Date20200704084037 -> introduce column 'invitation_sent'
		if ($this->migrateInvitationSent) {
			$query->update('polls_share')
				->set('invitation_sent', 'id');
		}

		$query->execute();
	}
}
