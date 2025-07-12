<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Share;

use OC\Core\Command\Base;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\User;
use OCP\AppFramework\Db\DoesNotExistException;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-api
 */
class Add extends Base {
	use TShareCommand;

	protected function configure(): void {
		$this
			->setName(AppConstants::APP_ID . ':share:add')
			->setDescription('Invites users to a poll')
			->addArgument(
				'id',
				InputArgument::REQUIRED,
				'ID of the poll to invite users to'
			)->addOption(
				'user',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Invites the given users to the poll'
			)->addOption(
				'group',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Invites all members of the given groups to the poll'
			)->addOption(
				'email',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Sends invitation e-mails to the given addresses to participate in the poll'
			);
	}

	/**
	 * @psalm-suppress PossiblyUnusedParam
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$pollId = $input->getArgument('id');
		$users = $input->getOption('user');
		$groups = $input->getOption('group');
		$emails = $input->getOption('email');

		try {
			$poll = $this->pollMapper->get($pollId);
		} catch (DoesNotExistException $e) {
			$output->writeln('<error>Poll not found.</error>');
			return 1;
		}

		$this->inviteUsers($poll, $users);
		$this->inviteGroups($poll, $groups);
		$this->inviteEmails($poll, $emails);

		$output->writeln('<info>Users successfully invited to poll.</info>');
		return 0;
	}

	/**
	 * @param Poll $poll
	 * @param string[] $userIds
	 * @psalm-suppress UnusedMethod
	 */
	private function inviteUsers(Poll $poll, array $userIds): void {
		foreach ($userIds as $userId) {
			try {
				$share = $this->shareService->add($poll->getId(), User::TYPE, $userId);
				$this->shareService->sendInvitation($share);
			} catch (ShareAlreadyExistsException $e) {
				// silently ignore already existing shares
			}
		}
	}

	/**
	 * @param Poll $poll
	 * @param string[] $groupIds
	 * @psalm-suppress UnusedMethod
	 */
	private function inviteGroups(Poll $poll, array $groupIds): void {
		foreach ($groupIds as $groupId) {
			try {
				$share = $this->shareService->add($poll->getId(), Group::TYPE, $groupId);
				$this->shareService->sendInvitation($share);
			} catch (ShareAlreadyExistsException $e) {
				// silently ignore already existing shares
			}
		}
	}

	/**
	 * @param Poll $poll
	 * @param string[] $emails
	 * @psalm-suppress UnusedMethod
	 */
	private function inviteEmails(Poll $poll, array $emails): void {
		foreach ($emails as $email) {
			try {
				$share = $this->shareService->add($poll->getId(), Email::TYPE, $email);
				$this->shareService->sendInvitation($share);
			} catch (ShareAlreadyExistsException $e) {
				// silently ignore already existing shares
			}
		}
	}

	/**
	 * @psalm-suppress PossiblyUnusedParam
	 */
	public function completeOptionValues($optionName, CompletionContext $context) {
		return match ($optionName) {
			'user' => $this->completeUserValues($context),
			'group' => $this->completeGroupValues($context),
			default => parent::completeOptionValues($optionName, $context),
		};
	}
}
