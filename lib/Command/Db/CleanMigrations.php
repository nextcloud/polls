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

use OCA\Polls\Db\TableManager;
use OCA\Polls\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CleanMigrations extends Command {
	protected string $name = self::NAME_PREFIX . 'db:clean-migrations';
	protected string $description = 'Remove old migrations entries from Nextcloud\'s migration table';

	public function __construct(
		protected OutputInterface $output,
		protected InputInterface $input,
		protected ConfirmationQuestion $question,
		protected TableManager $tableManager
	) {
		parent::__construct($output, $input, $question);
	}

	protected function runCommands(): int {
		// remove constraints and indices
		$this->printComment('Remove migration entries from migration table');
		// secure, that the schema is updated to the current status
		$this->tableManager->refreshSchema();
		$this->tableManager->removeObsoleteMigrations();
		$this->tableManager->migrate();
		
		return 0;
	}

	protected function requestConfirmation(): int {
		if ($this->input->isInteractive()) {
			$helper = $this->getHelper('question');
			$this->printComment('All polls tables will get checked against the current schema.');
			$this->printComment('NO data migration will be executed, so make sure you have a backup of your database.');
			$this->printNewLine();

			if (!$helper->ask($this->input, $this->output, $this->question)) {
				return 1;
			}
		}
		return 0;
	}
}
