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

namespace OCA\Polls\Command\Poll;

use OCA\Polls\AppInfo\AppConstants;
use OCA\Polls\Service\PollService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class TransferOwnership extends Command {
	public function __construct(
		private IUserManager $userManager,
		private PollService $pollService
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName(AppConstants::APP_ID . ':poll:transfer-ownership')
			->setDescription('Transfer the ownership of one user\'s polls to another user.')
			->addArgument(
				'source-user',
				InputArgument::REQUIRED,
				'User id to transfer the polls from'
			)
			->addArgument(
				'target-user',
				InputArgument::REQUIRED,
				'User id to transfer the polls to'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($this->requestConfirmation($input, $output)) {
			return 1;
		}

		if (!$this->userManager->get($input->getArgument('target-user')) instanceof IUser) {
			$output->writeln('<error>  Unknown destination user ' . $input->getArgument('target-user') . '</error>');
			return 1;
		}

		$transferredPolls = $this->pollService->transferPolls($input->getArgument('source-user'), $input->getArgument('target-user'));

		if (sizeof($transferredPolls) < 1) {
			$output->writeln('<info>No polls were transferred from ' . $input->getArgument('source-user') . '</info>');
		} elseif (sizeof($transferredPolls) === 1) {
			$output->writeln('<info>One poll was transferred from ' . $input->getArgument('source-user') . ' to ' . $input->getArgument('target-user') . '</info>');
			$output->writeln('<info> * ' . $transferredPolls[0]->getId() . ' - ' . $transferredPolls[0]->getTitle() . '</info>');
		} else {
			$output->writeln('<info>' . sizeof($transferredPolls) . ' polls were transferred from ' . $input->getArgument('source-user') . ' to ' . $input->getArgument('target-user') . '</info>');
			foreach ($transferredPolls as $poll) {
				$output->writeln('<info> * ' . $poll->getId() . ' - ' . $poll->getTitle() . '</info>');
			}
		}

		return 0;
	}

	private function requestConfirmation(InputInterface $input, OutputInterface $output): int {
		if ($input->isInteractive()) {
			$helper = $this->getHelper('question');
			$output->writeln('<comment>This command will change the ownership of all polls of ' . $input->getArgument('source-user') . ' to ' . $input->getArgument('target-user') . '.</comment>');
			$output->writeln('<comment>NO notifications will be sent to the users.</comment>');
			$output->writeln('');

			$question = new ConfirmationQuestion('Continue with the transfer (y/n)? [n] ', false);
			if (!$helper->ask($input, $output, $question)) {
				return 1;
			}
		}
		return 0;
	}
}
