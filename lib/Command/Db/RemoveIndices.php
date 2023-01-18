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

use OCA\Polls\Migration\RemoveIndices as IndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RemoveIndices extends Command {
	public function __construct(private IndexManager $indexManager) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('polls:index:remove')
			->setDescription('Remove indices');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($this->requestConfirmation($input, $output)) {
			return 1;
		}

		// remove constraints and indices
		$this->deleteForeignKeyConstraints($output);
		$this->deleteGenericIndices($output);
		$this->deleteUniqueIndices($output);
		$this->indexManager->migrate();
		return 0;
	}

	private function requestConfirmation(InputInterface $input, OutputInterface $output): int {
		if ($input->isInteractive()) {
			$helper = $this->getHelper('question');
			$output->writeln('<comment>Removes all indices and foreign key constraints.</comment>');
			$output->writeln('<comment>NO data migration will be executed, so make sure you have a backup of your database.</comment>');
			$output->writeln('');

			$question = new ConfirmationQuestion('Continue (y/n)? [y] ', true);
			if (!$helper->ask($input, $output, $question)) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteForeignKeyConstraints(OutputInterface $output): void {
		$output->writeln('<comment>Remove foreign key constraints and generic indices</comment>');
		$messages = $this->indexManager->removeAllForeignKeyConstraints();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteGenericIndices(OutputInterface $output): void {
		$output->writeln('<comment>Remove generic indices</comment>');
		$messages = $this->indexManager->removeAllGenericIndices();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteUniqueIndices(OutputInterface $output): void {
		$output->writeln('<comment>Remove unique indices</comment>');
		$messages = $this->indexManager->removeAllUniqueIndices();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}
}
