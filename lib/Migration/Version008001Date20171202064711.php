<?php
/**
 * @copyright Copyright (c) 2017 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Polls\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version008001Date20171202064711 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @return null|Schema
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var Schema $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('polls_events')) {
			$table = $schema->createTable('polls_events');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				// 'default' => 0, // Seems to make no sense for an AUTOINCREMENT
			]);
			$table->addColumn('hash', Type::TEXT, [
				'length' => 64,
			]);
			$table->addColumn('type', Type::INTEGER, [
				'notnull' => false,
				'length' => 20,
			]);
			
			$table->addColumn('title', Type::TEXT, [
                'notnull' => true,
                'length' => 128,
            ]);

            $table->addColumn('description', Type::TEXT, [
                'notnull' => true,
                'length' => 1024,
            ]);
            $table->addColumn('owner', Type::TEXT, [
                'notnull' => true,
                'length' => 64,
            ]);
			// Not sure about the type. Could become an INTEGER instead of DATETIME
            $table->addColumn('created', Type::DATETIME, [
            ]);
            $table->addColumn('access', Type::TEXT, [
                'notnull' => false,
                'length' => 1024,
            ]);
			// Not sure about the type. Could become an INTEGER instead of DATETIME
            $table->addColumn('expire', Type::DATETIME, [
            ]);
            $table->addColumn('is_anonymous', Type::INTEGER, [
                'default' => 0,
				'length' => 11,
            ]);
            $table->addColumn('full_anonymous', Type::INTEGER, [
                'default' => 0,
				'length' => 11,
            ]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_dts')) {
			$table = $schema->createTable('polls_dts');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);

            $table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
                'notnull' => true,
				'length' => 11,
			]);

            $table->addColumn('poll_id', Type::INTEGER, [
				'length' => 11,
			]);
			// Not sure about the type. Could become an INTEGER instead of DATETIME
            $table->addColumn('dt', Type::DATETIME, [
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_txts')) {
			$table = $schema->createTable('polls_txts');
			$table->addColumn('id', Type::INTEGER, [
				// 'default' => 0, // Seems to make no sense for an AUTOINCREMENT
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
            $table->addColumn('poll_id', Type::INTEGER, [
				'length' => 11,
			]);
            $table->addColumn('text', Type::TEXT, [
				'length' => 256,
			]);
			$table->setPrimaryKey(['id']);
		}


		if (!$schema->hasTable('polls_particip')) {
			$table = $schema->createTable('polls_particip');
			$table->addColumn('id', Type::INTEGER, [
				// 'default' => 0, // Seems to make no sense for an AUTOINCREMENT
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);

            $table->addColumn('poll_id', Type::INTEGER, [
				'length' => 11,
			]);
			// Not sure about the type. Could become an INTEGER instead of DATETIME
            $table->addColumn('dt', Type::DATETIME, [
			]);
            $table->addColumn('type', Type::INTEGER, [
				'length' => 11,
			]);
            $table->addColumn('user_id', Type::TEXT, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}


		if (!$schema->hasTable('polls_particip_TEXT')) {
			$table = $schema->createTable('polls_particip_TEXT');
			$table->addColumn('id', Type::INTEGER, [
				// 'default' => 0, // Seems to make no sense for an AUTOINCREMENT
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
            $table->addColumn('poll_id', Type::INTEGER, [
            ]);
            $table->addColumn('TEXT', Type::TEXT, [
				'length' => 256,
            ]);
            $table->addColumn('user_id', Type::TEXT, [
				'notnull' => true,
				'length' => 64,
            ]);
            $table->addColumn('type', Type::INTEGER, [
				'length' => 11,
            ]);

			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_comments')) {
			$table = $schema->createTable('polls_comments');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				// 'default' => 0, // Seems to make no sense for an AUTOINCREMENT
			]);
            $table->addColumn('poll_id', Type::INTEGER, [
				'length' => 11,
            ]);
            $table->addColumn('user_id', Type::TEXT, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('dt', Type::TEXT, [
                'notnull' => true,
                'length' => 32,
            ]);
            $table->addColumn('comment', Type::TEXT, [
                'notnull' => false,
                'length' => 1024,
            ]);

			$table->setPrimaryKey(['id']);
		}


		if (!$schema->hasTable('polls_notif')) {
			$table = $schema->createTable('polls_notif');
			$table->addColumn('id', Type::INTEGER, [
				// 'default' => 0, // Seems to make no sense for an AUTOINCREMENT
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);

            $table->addColumn('poll_id', Type::INTEGER, [
				'length' => 11,
            ]);
            $table->addColumn('user_id', Type::TEXT, [
                'notnull' => true,
                'length' => 64,
            ]);

			$table->setPrimaryKey(['id']);
		}
		return $schema;
	}
}
