<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	protected PollMapper|MockObject $pollMapper;
	protected ShareMapper|MockObject $shareMapper;
	protected ShareService|MockObject $shareService;
	protected IUserManager|MockObject $userManager;
	protected IGroupManager|MockObject $groupManager;
	protected int $lastShareId = 0;

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
			['getId', 'getPollId', 'getEmailAddress', 'getToken'],
			['getType', 'getUserId']
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
