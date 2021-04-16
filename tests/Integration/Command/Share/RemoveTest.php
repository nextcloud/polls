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
		$expectedShareCount = 0;
		$expectedShareTokens = [];
		foreach ($pollData['initialShares'] ?? [] as $type => $shares) {
			foreach ($shares as $userId) {
				$initialShares[] = $this->createShareMock($pollData['pollId'], $type, $userId);

				if (in_array($userId, $pollData['expectedShares'][$type] ?? [])) {
					$expectedShareTokens[] = $this->getShareToken($pollData['pollId'], $type, $userId);
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
			->with($this->logicalOr(...$expectedShareTokens));

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
