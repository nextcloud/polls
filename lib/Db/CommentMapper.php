<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @author René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Comment>
 */
class CommentMapper extends QBMapper {
	public const TABLE = Comment::TABLE;

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, Comment::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Comment
	 */
	public function find(int $id): Comment {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Comment[]
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * @return void
	 */
	public function deleteByPoll(int $pollId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName(), Self::TABLE)
		   ->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		$qb->executeStatement();
	}

	/**
	 * @return void
	 */
	public function deleteComment(int $id): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName(), Self::TABLE)
		   ->where(
		   	$qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
		   );

		$qb->executeStatement();
	}

	/**
	 * @return void
	 */
	public function renameUserId(string $userId, string $replacementName): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName(), Self::TABLE)
			->set(self::TABLE . '.user_id', $query->createNamedParameter($replacementName))
			->where($query->expr()->eq(self::TABLE . '.user_id', $query->createNamedParameter($userId)))
			->executeStatement();
	}

	/**
	 * Build the enhanced query with joined tables
	 */
	protected function buildQuery(): IQueryBuilder {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), Self::TABLE);

		$this->joinDisplayNameFromShare($qb, Self::TABLE);
		return $qb;
	}

	/**
	 * Joins shares to fetch displayName from shares
	 */
	protected function joinDisplayNameFromShare(IQueryBuilder &$qb, string $fromAlias): void {
		$joinAlias = 'shares';
		$qb->selectAlias($joinAlias . '.display_name', 'display_name');
		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($fromAlias . '.poll_id', $joinAlias . '.poll_id'),
				$qb->expr()->eq($fromAlias . '.user_id', $joinAlias . '.user_id'),
			)
		);
	}
}
