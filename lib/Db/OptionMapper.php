<?php
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

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Option>
 */
class OptionMapper extends QBMapper {
	public const TABLE = Option::TABLE;

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, Option::TABLE, Option::class);
	}

	public function update(Entity $entity): Entity {
		$entity->setPollOptionHash(hash('md5', $entity->getPollId() . $entity->getPollOptionText() . $entity->getTimestamp()));
		return parent::update($entity);
	}

	public function insert(Entity $entity): Entity {
		$entity->setPollOptionHash(hash('md5', $entity->getPollId() . $entity->getPollOptionText() . $entity->getTimestamp()));
		return parent::insert($entity);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function find(int $id): Option {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function findConfirmed(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   )->andWhere(
		   	$qb->expr()->gt('confirmed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntities($qb);
	}

	/**
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   )
		   ->orderBy('order', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function findByPollAndText(int $pollId, string $pollOptionText): Option {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   )
		   ->andWhere(
		   	$qb->expr()->eq('poll_option_text', $qb->createNamedParameter($pollOptionText, IQueryBuilder::PARAM_STR))
		   )
		   ->orderBy('order', 'ASC');

		return $this->findEntity($qb);
	}

	public function remove(int $optionId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('id', $qb->createNamedParameter($optionId, IQueryBuilder::PARAM_INT))
		   );

		$qb->executeStatement();
	}

	public function deleteByPoll(int $pollId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		$qb->executeStatement();
	}

	/**
	 * @return Option[]
	 */
	public function findOptionsWithDuration(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('duration', $qb->createNamedParameter(86400, IQueryBuilder::PARAM_INT))
		   )
		   ->orderBy('order', 'ASC');

		return $this->findEntities($qb);
	}

	public function renameUserId(string $userId, string $replacementName): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('owner', $query->createNamedParameter($replacementName))
			->where($query->expr()->eq('owner', $query->createNamedParameter($userId)))
			->executeStatement();
	}
}
