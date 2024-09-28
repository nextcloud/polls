<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template T1 of EntityWithUser
 * @template-extends QBMapper<T1>
 */
abstract class QBMapperWithUser extends QBMapper {
	/**
	 * @param IDBConnection $db
	 * @param string $tableName
	 * @param class-string<T1>|null $entityClass
	 */
	public function __construct(
		IDBConnection $db,
		string $tableName,
		?string $entityClass = null,
	) {
		parent::__construct($db, $tableName, $entityClass);
	}

	/**
	 * Joins anonymous setting of poll
	 */
	protected function joinAnon(IQueryBuilder &$qb, string $fromAlias): void {
		$joinAlias = 'anon';

		$qb->selectAlias($joinAlias . '.anonymous', 'anonymized')
			->selectAlias($joinAlias . '.owner', 'poll_owner_id')
			->selectAlias($joinAlias . '.show_results', 'poll_show_results')
			->selectAlias($joinAlias . '.expire', 'poll_expire')
			->addGroupBy(
				$joinAlias . '.anonymous',
				$joinAlias . '.owner',
				$joinAlias . '.show_results',
				$joinAlias . '.expire',
			);

		$qb->leftJoin(
			$fromAlias,
			Poll::TABLE,
			$joinAlias,
			$qb->expr()->eq($joinAlias . '.id', $fromAlias . '.poll_id'),
		);

	}
}
