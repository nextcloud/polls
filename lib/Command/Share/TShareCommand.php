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

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Service\ShareService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

trait TShareCommand {
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
