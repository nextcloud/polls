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
use OCA\Polls\Db\Share;
use OCA\Polls\Model\Contact;
use OCA\Polls\Model\Email;
use OCA\Polls\Model\GenericUser;
use OCA\Polls\Model\Group;
use OCA\Polls\Model\User;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends Base {
	use TShareCommand;

	protected function configure(): void {
		$this
			->setName('polls:share:remove')
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

		$this->removeUsers($poll, $users);
		$this->removeGroups($poll, $groups);
		$this->removeEmails($poll, $emails);

		$output->writeln('<info>Poll invitations successfully revoked.</info>');
		return 0;
	}

	/**
	 * @param Poll     $poll
	 * @param string[] $userIds
	 */
	private function removeUsers(Poll $poll, array $userIds): void {
		foreach ($this->getUserShares($poll) as $share) {
			if (in_array($share->getUserId(), $userIds, true)) {
				$this->shareService->delete($share->getToken());
			}
		}
	}

	/**
	 * @param Poll     $poll
	 * @param string[] $groupIds
	 */
	private function removeGroups(Poll $poll, array $groupIds): void {
		foreach ($this->getGroupShares($poll) as $share) {
			if (in_array($share->getUserId(), $groupIds, true)) {
				$this->shareService->delete($share->getToken());
			}
		}
	}

	/**
	 * @param Poll     $poll
	 * @param string[] $emails
	 */
	private function removeEmails(Poll $poll, array $emails): void {
		foreach ($this->getEmailShares($poll) as $share) {
			if (in_array($share->getEmailAddress(), $emails, true)) {
				$this->shareService->delete($share->getToken());
			}
		}
	}

	/**
	 * @param Poll $poll
	 * @return Share[]
	 */
	private function getUserShares(Poll $poll): array
	{
		$shares = $this->shareMapper->findByPoll($poll->getId());
		return array_values(array_filter($shares, static function (Share $share): bool {
			return ($share->getType() === User::TYPE);
		}));
	}

	/**
	 * @param Poll $poll
	 * @return Share[]
	 */
	private function getGroupShares(Poll $poll): array
	{
		$shares = $this->shareMapper->findByPoll($poll->getId());
		return array_values(array_filter($shares, static function (Share $share): bool {
			return ($share->getType() === Group::TYPE);
		}));
	}

	/**
	 * @param Poll $poll
	 * @return Share[]
	 */
	private function getEmailShares(Poll $poll): array
	{
		$shares = $this->shareMapper->findByPoll($poll->getId());
		return array_values(array_filter($shares, static function (Share $share): bool {
			if (($share->getType() === GenericUser::TYPE) && $share->getEmailAddress()) {
				return true;
			}

			return (($share->getType() === Email::TYPE) || ($share->getType() === Contact::TYPE));
		}));
	}
}
