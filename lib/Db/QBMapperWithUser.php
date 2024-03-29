<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 René Gieling <github@dartcafe.de>
 *
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
		?string $entityClass = null
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
