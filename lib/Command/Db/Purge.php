<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Command\Db;

use OCA\Polls\Command\Command;
use OCA\Polls\Db\TableManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class Purge extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:purge';
	protected string $description = 'Remove all polls related tables and records';
	protected array $operationHints = [
		'This command will remove Polls completely from your instance',
		' - delete all oc_polls_* tables, ',
		' - delete Polls\'s migration records from oc_migrations, ',
		' - delete Polls\'s app config records from oc_appconfig.',
		' ',
		'after running this command call \'occ app:remove polls \'',
	];

	public function __construct(
		private IDBConnection $connection,
		private TableManager $tableManager
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$this->tableManager->setConnection($this->connection);
		$messages = $this->tableManager->purgeTables();
		$this->printInfo($messages, ' - ');
		return 0;
	}
}
