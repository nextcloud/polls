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

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use Doctrine\DBAL\Schema\SchemaException;

class Version0107Date20201217071304 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// $schema = $schemaClosure();
		//
		// if (!$schema->hasTable('polls_share')) {
		// 	return;
		// }
		// $query = $this->connection->getQueryBuilder();
		//
		// // make sure, all public shares fit to the unique index added in schemaChange(),
		// // by copying token to user_id
		// $query->update('polls_share')
		// 	->set('user_id', 'token')
		// 	->where('type = :type')
		// 	->setParameter('type', 'public')
		// 	->execute();
		//
		// // remove duplicates from oc_polls_share
		// // preserve the first entry
		// $query = $this->connection->getQueryBuilder();
		// $query->select('id', 'type', 'poll_id', 'user_id')
		// 	->from('polls_share');
		// $foundEntries = $query->execute();
		//
		// $delete = $this->connection->getQueryBuilder();
		// $delete->delete('polls_share')->where('id = :id');
		//
		// $entries2Keep = [];
		//
		// while ($row = $foundEntries->fetch()) {
		// 	$currentRecord = [
		// 		$row['poll_id'],
		// 		$row['type'],
		// 		$row['user_id']
		// 	];
		//
		// 	if (in_array($currentRecord, $entries2Keep)) {
		// 		$delete->setParameter('id', $row['id']);
		// 		$delete->execute();
		// 	} else {
		// 		$entries2Keep[] = $currentRecord;
		// 	}
		// }
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$this->removeDuplicates($schemaClosure);

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_share')) {
			$table = $schema->getTable('polls_share');
			$table->changeColumn('user_id', [
				'notnull' => true,
				'default' => ''
			]);

			try {
				$table->addUniqueIndex(['poll_id', 'user_id'], 'UNIQ_shares');
			} catch (SchemaException $e) {
				//catch silently, index is already present
			}
		}
		return $schema;
	}

	public function removeDuplicates(\Closure $schemaClosure) {
		$schema = $schemaClosure();

		if (!$schema->hasTable('polls_share')) {
			return;
		}
		$query = $this->connection->getQueryBuilder();

		// make sure, all public shares fit to the unique index added in schemaChange(),
		// by copying token to user_id
		$query->update('polls_share')
			->set('user_id', 'token')
			->where('type = :type')
			->setParameter('type', 'public')
			->execute();

		// remove duplicates from oc_polls_share
		// preserve the first entry
		$query = $this->connection->getQueryBuilder();
		$query->select('id', 'type', 'poll_id', 'user_id')
			->from('polls_share');
		$foundEntries = $query->execute();

		$delete = $this->connection->getQueryBuilder();
		$delete->delete('polls_share')->where('id = :id');

		$entries2Keep = [];

		while ($row = $foundEntries->fetch()) {
			$currentRecord = [
				$row['poll_id'],
				$row['type'],
				$row['user_id']
			];

			if (in_array($currentRecord, $entries2Keep)) {
				$delete->setParameter('id', $row['id']);
				$delete->execute();
			} else {
				$entries2Keep[] = $currentRecord;
			}
		}
	}



}
