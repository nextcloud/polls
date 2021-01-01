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
use OCA\Polls\Db\ShareMapper;

class Version0107Date20201217071304 extends SimpleMigrationStep {

	/** @var ShareMapper */
	private $shareMapper;

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection, ShareMapper $shareMapper) {
		$this->connection = $connection;
		$this->shareMapper = $shareMapper;
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$this->shareMapper->removeDuplicates();
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
}
