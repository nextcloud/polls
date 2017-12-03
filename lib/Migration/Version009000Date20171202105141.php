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
use OC\DB\SchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Installation class for the polls app.
 */
class Version009000Date20171202105141 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `SchemaWrapper`
	 * @param array $options
	 * @return null|SchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var SchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('polls_events')) {
			$table = $schema->createTable('polls_events');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('hash', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('type', Type::BIGINT, [
				'notnull' => false,
				'length' => 16,
			]);
			$table->addColumn('title', Type::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('description', Type::STRING, [
				'notnull' => true,
				'length' => 1024,
			]);
			$table->addColumn('owner', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('access', Type::STRING, [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->addColumn('expire', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('is_anonymous', Type::INTEGER, [
				'notnull' => false,
				'default' => 0,
			]);
			$table->addColumn('full_anonymous', Type::INTEGER, [
				'notnull' => false,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_dts')) {
			$table = $schema->createTable('polls_dts');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('dt', Type::DATETIME, [
				'notnull' => false,
				'length' => 32,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_txts')) {
			$table = $schema->createTable('polls_txts');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('text', Type::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_particip')) {
			$table = $schema->createTable('polls_particip');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('dt', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('type', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_particip_text')) {
			$table = $schema->createTable('polls_particip_text');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('text', Type::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('type', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_comments')) {
			$table = $schema->createTable('polls_comments');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('dt', Type::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('comment', Type::STRING, [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_notif')) {
			$table = $schema->createTable('polls_notif');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}
		return $schema;
	}

}
