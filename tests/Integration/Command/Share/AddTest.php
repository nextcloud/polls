<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Tests\Integration\Command\Share;

use OCA\Polls\Command\Share\Add;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Share;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\User;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class AddTest extends TestCase {
	use TShareCommandTest;

	public function setUp(): void {
		parent::setUp();

		$this->setUpMocks();
	}

	public function testMissingArguments(): void {
		$this->pollMapper
			->expects($this->never())
			->method('find');

		$this->expectException(ConsoleRuntimeException::class);
		$this->expectExceptionMessage('Not enough arguments (missing: "id").');

		$command = new Add(
			$this->pollMapper,
			$this->shareMapper,
			$this->shareService,
			$this->userManager,
			$this->groupManager
		);

		$tester = new CommandTester($command);
		$tester->execute([]);
	}

	public function testPollNotFound(): void {
		$pollId = 123;

		$this->pollMapper
			->expects($this->once())
			->method('find')
			->with($pollId)
			->willReturnCallback(function (int $id): Poll {
				throw new DoesNotExistException('');
			});

		$command = new Add(
			$this->pollMapper,
			$this->shareMapper,
			$this->shareService,
			$this->userManager,
			$this->groupManager
		);

		$tester = new CommandTester($command);
		$tester->execute(['id' => $pollId]);

		$this->assertEquals("Poll not found.\n", $tester->getDisplay());
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testValid(array $input, array $pollData): void {
		$expectedShareCount = count($pollData['expectedShares']['user'] ?? [])
			+ count($pollData['expectedShares']['group'] ?? [])
			+ count($pollData['expectedShares']['email'] ?? []);
		$expectedInvitationCount = count($pollData['expectedInvitations']['user'] ?? [])
			+ count($pollData['expectedInvitations']['group'] ?? [])
			+ count($pollData['expectedInvitations']['email'] ?? []);

		$expectedShares = [];

		foreach ($pollData['expectedInvitations'] ?? [] as $type => $shares) {
			foreach ($shares as $userId) {
				if (!in_array($userId, $pollData['initialShares'][$type] ?? [])) {
					$share = $this->createShareMock($pollData['pollId'], $type, $userId);
					$mockedShares[$type][$userId] = $share;
					$expectedShares[] = $share;
				}
			}
		}

		$this->pollMapper
			->expects($this->once())
			->method('find')
			->with($pollData['pollId'])
			->willReturnCallback([$this, 'createPollMock']);

		$this->shareService
			->expects($this->exactly($expectedShareCount))
			->method('add')
			->with($pollData['pollId'], $this->logicalOr(User::TYPE, Group::TYPE, Email::TYPE), $this->anything())
			->willReturnCallback(function (int $pollId, string $type, string $userId = '') use ($pollData, $mockedShares): Share {
				$userIdConstraint = $this->logicalOr(...$pollData['expectedShares'][$type] ?? []);
				$userIdConstraint->evaluate($userId);

				if (in_array($userId, $pollData['initialShares'][$type] ?? [])) {
					throw new ShareAlreadyExistsException();
				}

				return $mockedShares[$type][$userId];
			});

		$this->shareService
			->expects($this->exactly($expectedInvitationCount))
			->method('sendInvitation')
			->with($this->logicalOr(...$expectedShares));

		$command = new Add(
			$this->pollMapper,
			$this->shareMapper,
			$this->shareService,
			$this->userManager,
			$this->groupManager
		);

		$tester = new CommandTester($command);
		$tester->execute($input);

		$this->assertEquals("Users successfully invited to poll.\n", $tester->getDisplay());
	}

	public function validProvider(): array {
		return [
			[
				[
					'id' => 1,
				],
				[
					'pollId' => 1,
				],
			],
			[
				[
					'id' => 123,
					'--user' => ['user1', 'user2'],
					'--group' => ['group1'],
					'--email' => ['foo@example.com', 'bar@example.com'],
				],
				[
					'pollId' => 123,
					'expectedShares' => [
						'user' => ['user1', 'user2'],
						'group' => ['group1'],
						'email' => ['foo@example.com', 'bar@example.com'],
					],
					'expectedInvitations' => [
						'user' => ['user1', 'user2'],
						'group' => ['group1'],
						'email' => ['foo@example.com', 'bar@example.com'],
					],
				],
			],
			[
				[
					'id' => 456,
					'--user' => ['user2', 'user3', 'user4'],
					'--email' => ['foo@example.com', 'bar@example.com'],
				],
				[
					'pollId' => 456,
					'initialShares' => [
						'user' => ['user1', 'user2'],
						'email' => ['foo@example.com'],
					],
					'expectedShares' => [
						'user' => ['user2', 'user3', 'user4'],
						'email' => ['foo@example.com', 'bar@example.com'],
					],
					'expectedInvitations' => [
						'user' => ['user3', 'user4'],
						'email' => ['bar@example.com'],
					],
				],
			],
		];
	}
}
