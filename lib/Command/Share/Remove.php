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
use OCA\Polls\Db\Share;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\GenericUser;
use OCA\Polls\Model\User\User;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-api
 */
class Remove extends Base {
	use TShareCommand;

	protected function configure(): void {
		$this
			->setName(AppConstants::APP_ID . ':share:remove')
			->setDescription('Remove user invitations from a poll')
			->addArgument(
				'id',
				InputArgument::REQUIRED,
				'ID of the poll to remove invitations from'
			)->addOption(
				'user',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Removes invitation of the given users from the poll'
			)->addOption(
				'group',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Removes invitations for all members of the given groups from the poll'
			)->addOption(
				'email',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Removes invitations for all users with the given e-mail addresses from the poll'
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

		$this->removeUsers($poll, $users);
		$this->removeGroups($poll, $groups);
		$this->removeEmails($poll, $emails);

		$output->writeln('<info>Poll invitations successfully revoked.</info>');
		return 0;
	}

	/**
	 * @param Poll $poll
	 * @param string[] $userIds
	 * @psalm-suppress UnusedMethod
	 */
	private function removeUsers(Poll $poll, array $userIds): void {
		foreach ($this->getUserShares($poll) as $share) {
			if (in_array($share->getUserId(), $userIds, true)) {
				$this->shareService->delete($share);
			}
		}
	}

	/**
	 * @param Poll $poll
	 * @param string[] $groupIds
	 * @psalm-suppress UnusedMethod
	 */
	private function removeGroups(Poll $poll, array $groupIds): void {
		foreach ($this->getGroupShares($poll) as $share) {
			if (in_array($share->getUserId(), $groupIds, true)) {
				$this->shareService->delete($share);
			}
		}
	}

	/**
	 * @param Poll $poll
	 * @param string[] $emails
	 * @psalm-suppress UnusedMethod
	 */
	private function removeEmails(Poll $poll, array $emails): void {
		foreach ($this->getEmailShares($poll) as $share) {
			if (in_array($share->getEmailAddress(), $emails, true)) {
				$this->shareService->delete($share);
			}
		}
	}

	/**
	 * @param Poll $poll
	 * @return Share[]
	 * @psalm-suppress UnusedMethod
	 */
	private function getUserShares(Poll $poll): array {
		$shares = $this->shareMapper->findByPoll($poll->getId());
		return array_values(array_filter($shares, static function (Share $share): bool {
			return ($share->getType() === User::TYPE);
		}));
	}

	/**
	 * @param Poll $poll
	 * @return Share[]
	 * @psalm-suppress UnusedMethod
	 */
	private function getGroupShares(Poll $poll): array {
		$shares = $this->shareMapper->findByPoll($poll->getId());
		return array_values(array_filter($shares, static function (Share $share): bool {
			return ($share->getType() === Group::TYPE);
		}));
	}

	/**
	 * @param Poll $poll
	 * @return Share[]
	 * @psalm-suppress UnusedMethod
	 */
	private function getEmailShares(Poll $poll): array {
		$shares = $this->shareMapper->findByPoll($poll->getId());
		return array_values(array_filter($shares, static function (Share $share): bool {
			if (($share->getType() === GenericUser::TYPE) && $share->getEmailAddress()) {
				return true;
			}

			return (($share->getType() === Email::TYPE) || ($share->getType() === Contact::TYPE));
		}));
	}
}
