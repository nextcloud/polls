<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Poll;

use OCA\Polls\AppConstants;
use OCA\Polls\Service\PollService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @psalm-api
 */
class TransferOwnership extends Command {
	public function __construct(
		private IUserManager $userManager,
		private PollService $pollService,
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
			/** @var QuestionHelper */
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
