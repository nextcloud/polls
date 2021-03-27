<?php
/**
 * @copyright Copyright (c) 2021 Daniel Rudolf <nextcloud.com@daniel-rudolf.de>
 *
 * @author Daniel Rudolf <nextcloud.com@daniel-rudolf.de>
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

namespace OCA\Polls\Command\Share;

use OC\Core\Command\Base;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Model\Email;
use OCA\Polls\Model\Group;
use OCA\Polls\Model\User;
use OCA\Polls\Service\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Base {
	/** @var PollMapper */
	private $pollMapper;

	/** @var ShareService */
	private $shareService;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct(PollMapper $pollMapper,
								ShareService $shareService,
								IUserManager $userManager,
								IGroupManager $groupManager) {
		parent::__construct();

		$this->pollMapper = $pollMapper;
		$this->shareService = $shareService;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	protected function configure(): void {
		$this
			->setName('polls:share:add')
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

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$pollId = $input->getArgument('id');
		$users = $input->getOption('user');
		$groups = $input->getOption('group');
		$emails = $input->getOption('email');

		try {
			$poll = $this->pollMapper->find($pollId);
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
	 * @param Poll     $poll
	 * @param string[] $userIds
	 */
	private function inviteUsers(Poll $poll, array $userIds): void {
		foreach ($userIds as $userId) {
			try {
				$share = $this->shareService->add($poll->getId(), User::TYPE, $userId);
				$this->shareService->sendInvitation($share->getToken());
			} catch (ShareAlreadyExistsException $e) {
				// silently ignore already existing shares
			}
		}
	}

	/**
	 * @param Poll     $poll
	 * @param string[] $groupIds
	 */
	private function inviteGroups(Poll $poll, array $groupIds): void {
		foreach ($groupIds as $groupId) {
			try {
				$share = $this->shareService->add($poll->getId(), Group::TYPE, $groupId);
				$this->shareService->sendInvitation($share->getToken());
			} catch (ShareAlreadyExistsException $e) {
				// silently ignore already existing shares
			}
		}
	}

	/**
	 * @param Poll     $poll
	 * @param string[] $emails
	 */
	private function inviteEmails(Poll $poll, array $emails): void {
		foreach ($emails as $email) {
			try {
				$share = $this->shareService->add($poll->getId(), Email::TYPE, $email);
				$this->shareService->sendInvitation($share->getToken());
			} catch (ShareAlreadyExistsException $e) {
				// silently ignore already existing shares
			}
		}
	}

	public function completeOptionValues($optionName, CompletionContext $context) {
		switch ($optionName) {
			case 'user':
				return $this->completeUserValues($context);

			case 'group':
				return $this->completeGroupValues($context);
		}

		return parent::completeOptionValues($optionName, $context);
	}

	private function completeUserValues(CompletionContext $context): array {
		return array_map(function (IUser $user) {
			return $user->getUID();
		}, $this->userManager->search($context->getCurrentWord()));
	}

	private function completeGroupValues(CompletionContext $context): array {
		return array_map(function (IGroup $group) {
			return $group->getGID();
		}, $this->groupManager->search($context->getCurrentWord()));
	}
}
