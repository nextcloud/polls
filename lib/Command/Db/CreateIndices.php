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
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Command\Db;

use OCA\Polls\Command\Command;
use OCA\Polls\Db\IndexManager;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CreateIndices extends Command {
	protected string $name = self::NAME_PREFIX . 'index:create';
	protected string $description = 'Add all indices and foreign key constraints';

	public function __construct(private IndexManager $indexManager) {
		parent::__construct();
		$this->question = new ConfirmationQuestion('Continue (y/n)? [y] ', true);
	}

	protected function runCommands(): int {
		// create indices and constraints
		// secure, that the schema is updated to the current status
		$this->indexManager->refreshSchema();
		$this->addForeignKeyConstraints();
		$this->addIndices();
		$this->indexManager->migrate();

		return 0;
	}

	protected function requestConfirmation(): int {
		if ($this->input->isInteractive()) {
			$this->helper = $this->getHelper('question');
			$this->printComment('Adds indices and foreing key constraints.');
			$this->printComment('NO data migration will be executed, so make sure you have a backup of your database.');
			$this->printNewLine();

			if (!$this->helper->ask($this->input, $this->output, $this->question)) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function addForeignKeyConstraints(): void {
		$this->printComment('Add foreign key constraints');
		$messages = $this->indexManager->createForeignKeyConstraints();

		foreach ($messages as $message) {
			$this->printInfo(' ' . $message);
		}
	}

	/**
	 * Create index for $table
	 */
	private function addIndices(): void {
		$this->printComment('Add indices');
		$messages = $this->indexManager->createIndices();
		foreach ($messages as $message) {
			$this->printInfo(' ' . $message);
		}
	}
}
