<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Tests\Integration\Command\Share;

use OCA\Polls\Command\Share\Remove;
use OCA\Polls\Db\Poll;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveTest extends TestCase {
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

		$command = new Remove(
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
			->willReturnCallback(static function (int $id): Poll {
				throw new DoesNotExistException('');
			});

		$command = new Remove(
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
		$initialShares = [];
		$expectedShares = [];
		$expectedShareCount = 0;

		foreach ($pollData['initialShares'] ?? [] as $type => $shares) {
			foreach ($shares as $userId) {
				$share = $this->createShareMock($pollData['pollId'], $type, $userId);
				$initialShares[] = $share;

				if (in_array($userId, $pollData['expectedShares'][$type] ?? [])) {
					$expectedShares[] = $share;
					$expectedShareCount++;
				}
			}
		}

		$this->pollMapper
			->expects($this->once())
			->method('find')
			->with($pollData['pollId'])
			->willReturnCallback([$this, 'createPollMock']);

		$this->shareMapper
			->method('findByPoll')
			->with($pollData['pollId'])
			->willReturn($initialShares);

		$this->shareService
			->expects($this->exactly($expectedShareCount))
			->method('delete')
			->with($this->logicalOr(...$expectedShares));

		$command = new Remove(
			$this->pollMapper,
			$this->shareMapper,
			$this->shareService,
			$this->userManager,
			$this->groupManager
		);

		$tester = new CommandTester($command);
		$tester->execute($input);

		$this->assertEquals("Poll invitations successfully revoked.\n", $tester->getDisplay());
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
					'--email' => ['foo@example.com'],
				],
				[
					'pollId' => 123,
					'initialShares' => [
						'user' => ['user1', 'user2', 'user3'],
						'group' => ['group1'],
						'email' => ['foo@example.com', 'bar@example.com'],
					],
					'expectedShares' => [
						'user' => ['user1', 'user2'],
						'group' => ['group1'],
						'email' => ['foo@example.com'],
					],
				],
			],
			[
				[
					'id' => 456,
					'--user' => ['user1', 'user2', 'user3', 'user4'],
					'--email' => ['foo@example.com', 'bar@example.com'],
				],
				[
					'pollId' => 456,
					'initialShares' => [
						'user' => ['user2', 'user3'],
						'email' => ['foo@example.com', 'baz@example.com'],
						'contact' => ['bar@example.com'],
					],
					'expectedShares' => [
						'user' => ['user2', 'user3'],
						'email' => ['foo@example.com'],
						'contact' => ['bar@example.com'],
					],
				]
			],
			[
				[
					'id' => 789,
					'--group' => ['group1'],
					'--email' => ['foo@example.com', 'baz@example.com'],
				],
				[
					'pollId' => 789,
					'initialShares' => [
						'user' => ['user1', 'user2'],
						'email' => ['foo@example.com'],
						'contact' => ['bar@example.com'],
						'external' => ['baz@example.com'],
					],
					'expectedShares' => [
						'email' => ['foo@example.com'],
						'external' => ['baz@example.com'],
					],
				]
			],
		];
	}
}
