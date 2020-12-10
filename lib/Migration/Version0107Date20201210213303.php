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
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0107Date20201210213303 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_votes')) {

			// remove duplicates from oc_polls_votes
			// preserve the first entry
			$query = $this->connection->getQueryBuilder();
			$query->select('id', 'poll_id', 'user_id', 'vote_option_text')
				->from('polls_votes');
			$foundEntries = $query->execute();

			$delete = $this->connection->getQueryBuilder();
			$delete->delete('polls_votes')
				->where('id = :id');

			$entries2Keep = [];

			while ($row = $foundEntries->fetch()) {
				$currentRecord = [
					$row['poll_id'],
					$row['user_id'],
					$row['vote_option_text']
				];
				if (in_array($currentRecord, $foundEntries2Keep)) {
					$delete->setParameter('id', $row['id']);
					$delete->execute();
				} else {
					$foundEntries2Keep[] = $currentRecord;
				}
			}
		}
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_votes')) {
			$table = $schema->getTable('polls_votes');
			$table->changeColumn('user_id', [
				'notnull' => true,
				'default' => ''
			]);
			$table->changeColumn('vote_option_text', [
				'notnull' => true,
				'default' => ''
			]);

			try {
				$table->addUniqueIndex(['poll_id', 'user_id', 'vote_option_text'], 'UNIQ_votes');
			} catch (\Exception $e) {
				//catch silently, index is already present
			}
		}
		return $schema;
	}
}
