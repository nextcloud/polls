<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\V11\TableManager;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Helper\Hash;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;

class TableManagerTest extends UnitTestCase {
	private const STALE_HASH = 'ffffffffffffffffffffffffffffffff';

	private IDBConnection $connection;
	private PollMapper $pollMapper;
	private TableManager $tableManager;
	private Poll $poll;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->pollMapper = Server::get(PollMapper::class);
		$this->tableManager = Server::get(TableManager::class);

		/** @var Poll $poll */
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$this->poll = $this->pollMapper->insert($poll);
	}

	/**
	 * A stale hash on an option whose sibling already holds the recalculated hash must
	 * not stop the hash update. The sibling is deleted here, so the live option has to
	 * survive together with its vote.
	 */
	public function testUpdateHashesResolvesCollisionWithDeletedOption(): void {
		$text = 'collision ' . bin2hex(random_bytes(8));
		$hash = Hash::getOptionHash($this->poll->getId(), $text);

		$this->insertOptionRow($text, $hash, ['deleted' => time() - 86400]);
		$liveId = $this->insertOptionRow($text, self::STALE_HASH);
		$this->insertVoteRow('voter', $text, self::STALE_HASH);

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(1, $options, 'the duplicate option should be gone');
		$this->assertSame($liveId, (int)$options[0]['id'], 'the live option should have survived');
		$this->assertSame($hash, $options[0]['poll_option_hash']);

		$this->assertSame(0, $this->countOrphanedVotes(), 'the vote should still belong to an option');
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * Same collision between two live options. Both votes belong to the same voter and
	 * collide as well, as soon as the two options are merged into one.
	 */
	public function testUpdateHashesResolvesCollisionWithLiveOption(): void {
		$text = 'collision ' . bin2hex(random_bytes(8));
		$hash = Hash::getOptionHash($this->poll->getId(), $text);

		$survivorId = $this->insertOptionRow($text, $hash);
		$this->insertOptionRow($text, self::STALE_HASH);
		$this->insertVoteRow('voter', $text, $hash);
		$this->insertVoteRow('voter', $text, self::STALE_HASH);

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(1, $options, 'the duplicate option should be gone');
		$this->assertSame($survivorId, (int)$options[0]['id']);
		$this->assertSame($hash, $options[0]['poll_option_hash']);

		$votes = $this->fetchVoteRows();
		$this->assertCount(1, $votes, 'the duplicate vote should be gone');
		$this->assertSame($hash, $votes[0]['vote_option_hash']);

		$this->assertSame(0, $this->countOrphanedVotes());
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * The surviving option is the one with the lowest id, so it can be the one still
	 * carrying the stale hash and needing the update after its twin is removed
	 */
	public function testUpdateHashesResolvesCollisionOnTheSurvivingOption(): void {
		$text = 'collision ' . bin2hex(random_bytes(8));
		$hash = Hash::getOptionHash($this->poll->getId(), $text);

		$survivorId = $this->insertOptionRow($text, self::STALE_HASH);
		$this->insertOptionRow($text, $hash);
		$this->insertVoteRow('voter', $text, $hash);

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(1, $options, 'the duplicate option should be gone');
		$this->assertSame($survivorId, (int)$options[0]['id']);
		$this->assertSame($hash, $options[0]['poll_option_hash']);

		$this->assertCount(1, $this->fetchVoteRows());
		$this->assertSame(0, $this->countOrphanedVotes(), 'the vote should still belong to an option');
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * A confirmed option carries the poll's result, so it has to win over its
	 * duplicate even when the duplicate has the lower id
	 */
	public function testUpdateHashesKeepsTheConfirmedOption(): void {
		$text = 'collision ' . bin2hex(random_bytes(8));
		$hash = Hash::getOptionHash($this->poll->getId(), $text);

		$this->insertOptionRow($text, $hash);
		$confirmedId = $this->insertOptionRow($text, self::STALE_HASH, ['confirmed' => time()]);

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(1, $options);
		$this->assertSame($confirmedId, (int)$options[0]['id'], 'the confirmed option should have survived');
		$this->assertSame($hash, $options[0]['poll_option_hash']);
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * Date options sharing an iso timestamp resolve to the same hash, but the unique
	 * index keys on the raw timestamp column. Rows whose raw value drifted apart do
	 * not collide and both have to be kept.
	 */
	public function testUpdateHashesKeepsDateOptionsDifferingInTheStoredTimestamp(): void {
		$iso = '2025-06-01T10:00:00+00:00';
		$timestamp = (new \DateTimeImmutable($iso))->getTimestamp();
		$hash = Hash::getOptionHash($this->poll->getId(), $iso);

		$firstId = $this->insertOptionRow($iso, self::STALE_HASH, ['timestamp' => $timestamp, 'iso_timestamp' => $iso]);
		$secondId = $this->insertOptionRow($iso, self::STALE_HASH, ['timestamp' => $timestamp + 1, 'iso_timestamp' => $iso]);

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(2, $options, 'both date options should have been kept');
		$this->assertSame([$firstId, $secondId], array_map(static fn (array $row): int => (int)$row['id'], $options));
		$this->assertSame($hash, $options[0]['poll_option_hash']);
		$this->assertSame($hash, $options[1]['poll_option_hash']);
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * Oracle reads an empty hash back as null, which must not break the entity
	 */
	public function testUpdateHashesWritesTheEmptyDefaultHash(): void {
		$text = 'empty hash ' . bin2hex(random_bytes(8));

		$optionId = $this->insertOptionRow($text, '');
		$this->insertVoteRow('voter', $text, '');

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(1, $options);
		$this->assertSame($optionId, (int)$options[0]['id']);
		$this->assertSame(Hash::getOptionHash($this->poll->getId(), $text), $options[0]['poll_option_hash']);

		$this->assertSame(0, $this->countOrphanedVotes(), 'the vote should still belong to an option');
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * Options which do not collide keep their row and only get their hash corrected
	 */
	public function testUpdateHashesKeepsDistinctOptions(): void {
		$first = 'distinct ' . bin2hex(random_bytes(8));
		$second = 'distinct ' . bin2hex(random_bytes(8));

		$firstId = $this->insertOptionRow($first, self::STALE_HASH);
		$secondId = $this->insertOptionRow($second, Hash::getOptionHash($this->poll->getId(), $second));

		$this->tableManager->updateHashes();

		$options = $this->fetchOptionRows();
		$this->assertCount(2, $options);
		$this->assertSame([$firstId, $secondId], array_map(static fn (array $row): int => (int)$row['id'], $options));
		$this->assertSame(Hash::getOptionHash($this->poll->getId(), $first), $options[0]['poll_option_hash']);
		$this->assertSame(Hash::getOptionHash($this->poll->getId(), $second), $options[1]['poll_option_hash']);
		$this->assertSame(0, $this->tableManager->getFailedHashUpdates());
	}

	/**
	 * tearDown
	 */
	public function tearDown(): void {
		parent::tearDown();

		foreach ([OptionMapper::TABLE, VoteMapper::TABLE] as $table) {
			$qb = $this->connection->getQueryBuilder();
			$qb->delete($table)
				->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($this->poll->getId(), IQueryBuilder::PARAM_INT)));
			$qb->executeStatement();
		}

		$this->pollMapper->delete($this->poll);
	}

	/**
	 * Insert an option bypassing the mapper, which would recalculate the hash
	 */
	private function insertOptionRow(string $text, string $hash, array $columns = []): int {
		$qb = $this->connection->getQueryBuilder();
		$columns = array_merge([
			'timestamp' => 0,
			'deleted' => 0,
			'confirmed' => 0,
			'iso_timestamp' => null,
		], $columns);

		$qb->insert(OptionMapper::TABLE)
			->values([
				'poll_id' => $qb->createNamedParameter($this->poll->getId(), IQueryBuilder::PARAM_INT),
				'poll_option_text' => $qb->createNamedParameter($text, IQueryBuilder::PARAM_STR),
				'poll_option_hash' => $qb->createNamedParameter($hash, IQueryBuilder::PARAM_STR),
				'timestamp' => $qb->createNamedParameter($columns['timestamp'], IQueryBuilder::PARAM_INT),
				'deleted' => $qb->createNamedParameter($columns['deleted'], IQueryBuilder::PARAM_INT),
				'confirmed' => $qb->createNamedParameter($columns['confirmed'], IQueryBuilder::PARAM_INT),
				'iso_timestamp' => $qb->createNamedParameter($columns['iso_timestamp'], IQueryBuilder::PARAM_STR),
				'owner' => $qb->createNamedParameter('tester', IQueryBuilder::PARAM_STR),
			]);
		$qb->executeStatement();

		return $qb->getLastInsertId();
	}

	/**
	 * Insert a vote bypassing the mapper, which would recalculate the hash
	 */
	private function insertVoteRow(string $userId, string $text, string $hash): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert(VoteMapper::TABLE)
			->values([
				'poll_id' => $qb->createNamedParameter($this->poll->getId(), IQueryBuilder::PARAM_INT),
				'user_id' => $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				'vote_option_text' => $qb->createNamedParameter($text, IQueryBuilder::PARAM_STR),
				'vote_option_hash' => $qb->createNamedParameter($hash, IQueryBuilder::PARAM_STR),
				'vote_answer' => $qb->createNamedParameter('yes', IQueryBuilder::PARAM_STR),
			]);
		$qb->executeStatement();

		return $qb->getLastInsertId();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function fetchOptionRows(): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'poll_option_text', 'poll_option_hash', 'deleted')
			->from(OptionMapper::TABLE)
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($this->poll->getId(), IQueryBuilder::PARAM_INT)))
			->orderBy('id');

		return $qb->executeQuery()->fetchAll();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function fetchVoteRows(): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'vote_option_hash')
			->from(VoteMapper::TABLE)
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($this->poll->getId(), IQueryBuilder::PARAM_INT)))
			->orderBy('id');

		return $qb->executeQuery()->fetchAll();
	}

	/**
	 * Votes of this poll which VoteMapper::removeOrphanedVotes() would delete
	 */
	private function countOrphanedVotes(): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('votes.id')
			->from(VoteMapper::TABLE, 'votes')
			->leftJoin(
				'votes',
				OptionMapper::TABLE,
				'options',
				$qb->expr()->andX(
					$qb->expr()->eq('votes.poll_id', 'options.poll_id'),
					$qb->expr()->eq('votes.vote_option_hash', 'options.poll_option_hash'),
				)
			)
			->where($qb->expr()->eq('votes.poll_id', $qb->createNamedParameter($this->poll->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('options.poll_id'));

		return count($qb->executeQuery()->fetchAll());
	}
}
