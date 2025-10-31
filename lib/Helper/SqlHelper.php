<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Helper;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

abstract class SqlHelper {
	/**
	 * Get a concatenated array of values from a column in the query builder.
	 *
	 * @param IQueryBuilder $qb The query builder instance per reference
	 * @param string $concatColumn The column to concatenate
	 * @param string $asColumn The alias for the concatenated column
	 * @param string $dbProvider The database provider (default: IDBConnection::PLATFORM_MYSQL)
	 * @param string $separator The separator for concatenation (default: ',')
	 *
	 * @psalm-param IDBConnection::PLATFORM_* $dbProvider
	 *
	 */
	public static function getConcatenatedArray(
		IQueryBuilder &$qb,
		string $concatColumn,
		string $asColumn,
		string $dbProvider,
		string $separator = ',',
	): void {
		$qb->addSelect(match ($dbProvider) {
			IDBConnection::PLATFORM_POSTGRES => $qb->createFunction('string_agg(distinct ' . $concatColumn . '::varchar, \'' . $separator . '\') AS ' . $asColumn),
			IDBConnection::PLATFORM_ORACLE => $qb->createFunction('listagg(distinct ' . $concatColumn . ', \'' . $separator . '\') WITHIN GROUP (ORDER BY ' . $concatColumn . ') AS ' . $asColumn),
			IDBConnection::PLATFORM_SQLITE => $qb->createFunction('group_concat(replace(distinct ' . $concatColumn . ' ,\'\',\'\'), \'' . $separator . '\') AS ' . $asColumn),
			default => $qb->createFunction('group_concat(distinct ' . $concatColumn . ' SEPARATOR \'' . $separator . '\') AS ' . $asColumn),
		});
	}
}
