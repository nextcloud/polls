<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\Vote;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/*
* @psalm-suppress UnusedClass
*/
class Version0721Date20240823135515 extends SimpleMigrationStep {

	private ISchemaWrapper $schema;
	public function __construct(
		private IDBConnection $connection
	) {
	}


	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();
		$userIds = $qb->selectDistinct('user_id')
			->from(Vote::TABLE)
			->executeQuery()
			->fetchAll();

		$userIds = array_map(fn ($row) => $row['user_id'], $userIds);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from(Share::TABLE)
			->where($qb->expr()->eq('type', $qb->createNamedParameter('external', IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->notIn('user_id', $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->in('email_address', $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));

		$affectedShares = $qb->executeQuery()->fetchAll();

		foreach ($affectedShares as $share) {
			$this->renameUserIdAndType($share['user_id'], $share['email_address']);
		}
	}


	private function renameUserIdAndType(string $userId, string $replacementName): void {
		$query = $this->connection->getQueryBuilder();
		$query->update(Share::TABLE)
			->set('user_id', $query->createNamedParameter($replacementName))
			->set('type', $query->createNamedParameter('email'))
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter('external')))
			->executeStatement();
	}
}
