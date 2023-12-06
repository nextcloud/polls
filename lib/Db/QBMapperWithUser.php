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
		string $entityClass = null
	) {
		parent::__construct($db, $tableName, $entityClass);

	}

	/**
	 * Joins shares to fetch displayName from shares
	 *
	 * Returns
	 *  - dispalyName (shares.display_name),
	 *  - share/user type (shares.user_type) and
	 *  - emailaddress (shares.email_address)
	 * from joined share table matching poll id and user id
	 *
	 * @param IQueryBuilder &$qb queryBuilder object by reference
	 * @param string $fromAlias alias used for the source table
	 */
	protected function joinDisplayNameFromShare(IQueryBuilder &$qb, string $fromAlias): void {
		$joinAlias = 'shares';

		$fromPollIdColumn = $fromAlias . '.poll_id';
		$fromUserIdColumn = $fromAlias . '.user_id';

		$joinPollIdColumn = $joinAlias . '.poll_id';
		$joinUserIdColumn = $joinAlias . '.user_id';

		// adjust joined columns for particular tables
		if ($fromAlias === Poll::TABLE) {
			$fromPollIdColumn = $fromAlias . '.id';
			$fromUserIdColumn = $fromAlias . '.owner';
		} elseif ($fromAlias === Option::TABLE) {
			$fromUserIdColumn = $fromAlias . '.owner';
		}

		// force value into a MIN function to avoid grouping errors
		$qb->selectAlias($qb->func()->min($joinAlias . '.display_name'), 'display_name');
		$qb->selectAlias($qb->func()->min($joinAlias . '.type'), 'user_type');
		$qb->selectAlias($qb->func()->min($joinAlias . '.email_address'), 'email_address');
		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($fromPollIdColumn, $joinPollIdColumn),
				$qb->expr()->eq($fromUserIdColumn, $joinUserIdColumn),
			)
		);
	}
}
