<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use Exception;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PollGroup>
 */
class PollGroupMapper extends QBMapper {
	public const TABLE = PollGroup::TABLE;
	public const CONCAT_SEPARATOR = ',';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		IDBConnection $db,
		private UserSession $userSession,
	) {
		parent::__construct($db, PollGroup::TABLE, PollGroup::class);
	}

	/**
	 * List all PollGroups
	 *
	 * @return PollGroup[]
	 */
	public function list(): array {
		$qb = $this->buildQuery();
		$qb->orderBy('title', 'ASC');
		return $this->findEntities($qb);
	}

	/**
	 * Find a PollGroup by its ID
	 *
	 * @param int $id id off poll group
	 * @return PollGroup
	 */
	public function find(int $id): PollGroup {
		$qb = $this->buildQuery();

		$qb->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id)));

		return $this->findEntity($qb);
	}

	public function addPollToGroup(int $pollId, int $groupId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert(PollGroup::RELATION_TABLE)
			->setValue('poll_id', $qb->createNamedParameter($pollId))
			->setValue('group_id', $qb->createNamedParameter($groupId));
		$qb->executeStatement();
	}

	/**
	 * Remove a Poll from a PollGroup
	 *
	 * @param int $pollId id of poll
	 * @param int $groupId id of group
	 * @throws Exception
	 */
	public function removePollFromGroup(int $pollId, int $groupId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(PollGroup::RELATION_TABLE)
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId)),
					$qb->expr()->eq('group_id', $qb->createNamedParameter($groupId))
				)
			);
		$qb->executeStatement();
	}

	public function add(PollGroup $pollGroup): PollGroup {
		$pollGroup->setCreated(time());
		$pollGroup->setOwner($this->userSession->getCurrentUserId());
		return $this->insert($pollGroup);

	}

	public function tidyPollGroups(): void {
		$qb = $this->db->getQueryBuilder();

		// This is, what we wanna do
		//
		// DELETE FROM oc_polls_groups
		//   WHERE `id` not IN (
		//     SELECT `group_id`
		//     FROM oc_polls_groups_polls)
		//
		// should result in
		//
		// $qb->delete(PollGroup::TABLE)
		// 	->where($qb->expr()->notIn(
		// 		'id',
		// 		$qb->selectDistinct('group_id')
		// 			->from(PollGroup::RELATION_TABLE)
		// 			->getSQL(),
		// 		IQueryBuilder::PARAM_INT_ARRAY
		// 	)
		// );
		//
		// But we have to use a subquery, otherwise, we get 'InvalidArgumentException Only strings, Literals and Parameters are allowed'

		$subquery = $this->db->getQueryBuilder();
		$subquery->selectDistinct('group_id')->from(PollGroup::RELATION_TABLE);

		$qb->delete(PollGroup::TABLE)
			->where($qb->expr()->notIn(
				'id',
				$qb->createFunction($subquery->getSQL()),
				IQueryBuilder::PARAM_INT_ARRAY
			)
			);
		$qb->executeStatement();
	}

	/**
	 * Build the enhanced query with joined tables
	 */
	protected function buildQuery(): IQueryBuilder {
		$qb = $this->db->getQueryBuilder();
		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id');

		// Join polls
		$this->joinPollIds($qb);

		return $qb;
	}

	protected function joinPollIds(
		IQueryBuilder $qb,
		string $joinAlias = 'polls',
	): void {
		TableManager::getConcatenatedArray(
			qb: $qb,
			concatColumn: $joinAlias . '.poll_id',
			asColumn: 'poll_ids',
			dbProvider: $this->db->getDatabaseProvider(),
		);

		$qb->leftJoin(
			self::TABLE,
			PollGroup::RELATION_TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq(self::TABLE . '.id', $joinAlias . '.group_id'),
			)
		);
	}
}
