<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PollGroup>
 */
class PollGroupMapper extends QBMapper {
	public const TABLE = PollGroup::TABLE;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, PollGroup::TABLE, PollGroup::class);
	}

	public function list(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->orderBy('title', 'ASC');
		return $this->findEntities($qb);
	}

}
