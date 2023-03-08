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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RemoveIndices extends Command {
	protected string $name = self::NAME_PREFIX . 'index:remove';
	protected string $description = 'Remove all indices and foreign key constraints';

	public function __construct(
		protected OutputInterface $output,
		protected InputInterface $input,
		protected ConfirmationQuestion $question,
		private IndexManager $indexManager,
		) {
		parent::__construct($output, $input, $question);
		$this->question = new ConfirmationQuestion('Continue (y/n)? [y] ', true);
	}

	protected function configure(): void {
		$this
			->setName('polls:index:remove')
			->setDescription('Remove indices');
	}

	protected function runCommands(): int {
		// remove constraints and indices
		$this->deleteForeignKeyConstraints();
		$this->deleteGenericIndices();
		$this->deleteUniqueIndices();
		$this->indexManager->migrate();
		return 0;
	}

	protected function requestConfirmation(): int {
		if ($this->input->isInteractive()) {
			$this->helper = $this->getHelper('question');
			$this->printComment('Removes all indices and foreign key constraints.');
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
	private function deleteForeignKeyConstraints(): void {
		$this->printComment('Remove foreign key constraints and generic indices');
		$messages = $this->indexManager->removeAllForeignKeyConstraints();

		foreach ($messages as $message) {
			$this->printInfo(' ' . $message);
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteGenericIndices(): void {
		$this->printComment('Remove generic indices');
		$messages = $this->indexManager->removeAllGenericIndices();

		foreach ($messages as $message) {
			$this->printInfo(' ' . $message);
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteUniqueIndices(): void {
		$this->printComment('Remove unique indices');
		$messages = $this->indexManager->removeAllUniqueIndices();

		foreach ($messages as $message) {
			$this->printInfo(' ' . $message);
		}
	}
}
