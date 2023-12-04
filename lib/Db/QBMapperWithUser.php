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

abstract class QbMapperWithUser extends QBMapper {
	/**
	 * Joins shares to fetch displayName from shares
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
