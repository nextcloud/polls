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

class Version0107Date20201210204702 extends SimpleMigrationStep {

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
		// if (!$schema->hasTable('polls_options')) {
		// 	return;
		// }
		//
		// // remove duplicates from oc_polls_options
		// // preserve the first entry
		// $query = $this->connection->getQueryBuilder();
		// $query->select('id', 'poll_id', 'poll_option_text', 'timestamp')
		// 	->from('polls_options');
		// $foundEntries = $query->execute();
		//
		// $delete = $this->connection->getQueryBuilder();
		// $delete->delete('polls_options')
		// 	->where('id = :id');
		//
		// $entries2Keep = [];
		//
		// while ($row = $foundEntries->fetch()) {
		// 	$currentRecord = [
		// 		$row['poll_id'],
		// 		$row['poll_option_text'],
		// 		$row['timestamp']
		// 	];
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

		if ($schema->hasTable('polls_options')) {
			$table = $schema->getTable('polls_options');
			$table->changeColumn('poll_option_text', [
				'notnull' => true,
				'default' => ''
			]);
			$table->changeColumn('timestamp', [
				'notnull' => true,
				'default' => 0
			]);

			try {
				$table->addUniqueIndex(['poll_id', 'poll_option_text', 'timestamp'], 'UNIQ_options');
			} catch (SchemaException $e) {
				//catch silently, index is already present
			}
		}
		return $schema;
	}

	public function removeDuplicates(\Closure $schemaClosure) {
		$schema = $schemaClosure();

		if (!$schema->hasTable('polls_options')) {
			return;
		}

		// remove duplicates from oc_polls_options
		// preserve the first entry
		$query = $this->connection->getQueryBuilder();
		$query->select('id', 'poll_id', 'poll_option_text', 'timestamp')
			->from('polls_options');
		$foundEntries = $query->execute();

		$delete = $this->connection->getQueryBuilder();
		$delete->delete('polls_options')
			->where('id = :id');

		$entries2Keep = [];

		while ($row = $foundEntries->fetch()) {
			$currentRecord = [
				$row['poll_id'],
				$row['poll_option_text'],
				$row['timestamp']
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
