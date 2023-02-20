<?php
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
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Polls\Migration\RepairSteps;

use OCA\Polls\Db\TableManager;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

class CreateTables implements IRepairStep {
	/** @var TableManager */
	private $tableManager;

	public function __construct(
		TableManager $tableManager
	) {
		$this->tableManager = $tableManager;
	}

	public function getName() {
		return 'Polls - Create missing tables and columns';
	}

	public function run(IOutput $output): void {
		// drop watch table to get it recreated
		$messages = $this->tableManager->removeWatch();
		foreach ($messages as $message) {
			$output->info($message);
		}
		// secure, that the schema is updated to the current status
		$this->tableManager->refreshSchema();
		$messages = $this->tableManager->createTables();
		$this->tableManager->migrate();
		
		foreach ($messages as $message) {
			$output->info($message);
		}
	}
}
