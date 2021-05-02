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

namespace OCA\Polls\Tests\Integration\Command\Share;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Service\ShareService;
use OCP\IGroupManager;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

trait TShareCommandTest {
	/** @var PollMapper|MockObject */
	protected $pollMapper;

	/** @var ShareMapper|MockObject */
	protected $shareMapper;

	/** @var ShareService|MockObject */
	protected $shareService;

	/** @var IUserManager|MockObject */
	protected $userManager;

	/** @var IGroupManager|MockObject */
	protected $groupManager;

	/** @var int */
	protected $lastShareId = 0;

	protected function setUpMocks(): void {
		$this->pollMapper = $this->createMock(PollMapper::class);
		$this->shareMapper = $this->createMock(ShareMapper::class);
		$this->shareService = $this->createMock(ShareService::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
	}

	public function createPollMock(int $id): Poll {
		/** @var Poll|MockObject $poll */
		$poll = $this->createMock(
			Poll::class,
			['getId']
		);

		$poll->method('getId')
			->willReturn($id);

		return $poll;
	}

	public function createShareMock(int $pollId, string $type, string $userId): Share {
		/** @var Share|MockObject $share */
		$share = $this->createMock(
			Share::class,
			['getId', 'getPollId', 'getType', 'getEmailAddress', 'getToken'],
			['getUserId']
		);

		$id = ++$this->lastShareId;
		$token = $this->getShareToken($pollId, $type, $userId);

		$share->method('getId')
			->willReturn($id);

		$share->method('getPollId')
			->willReturn($pollId);

		$share->method('getType')
			->willReturn($type);

		$share->method('getUserId')
			->willReturn($userId);

		$share->method('getEmailAddress')
			->willReturn($userId);

		$share->method('getToken')
			->willReturn($token);

		return $share;
	}

	public function getShareToken(int $pollId, string $type, string $userId): string {
		return substr(md5($pollId . '_' . $type . '_' . $userId), 0, 16);
	}

	protected function createMock($originalClassName, array $addMethods = null, array $onlyMethods = null): MockObject {
		$mockBuilder = $this->getMockBuilder($originalClassName)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->disableAutoReturnValueGeneration();

		if ($addMethods !== null) {
			$mockBuilder->addMethods($addMethods);
		}

		if ($onlyMethods !== null) {
			$mockBuilder->onlyMethods($onlyMethods);
		}

		return $mockBuilder->getMock();
	}
}
