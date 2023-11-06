<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
use OCP\ISession;
use OCP\IUserSession;

/**
 * @template-extends QBMapper<Share>
 */
class UserMapper extends QBMapper {
	public const TABLE = Share::TABLE;
	protected ?string $userId;
	protected ?string $userId;

	public function __construct(
		IDBConnection $db,
		protected ISession $session,
		private IUserSession $userSession,
	) {
		parent::__construct($db, Share::TABLE, Share::class);
	}

	/**
	 * getCurrentUserId - Returns the user id of the current internal or share user
	 */
	public function getCurrentUserId(): string {
		// try to get the right userId from all possible locations
		// and use the first valid
		$this->userId = $this->userId
			?? $this->userSession->getUser()?->getUID()
			?? $this->session->get('ncPollsUserId')
			?? $this->getShareFromSessionToken()->getUserId();

		// store userId in session to avoid unecessary db access
		$this->session->set('ncPollsUserId', $this->userId);

		return $this->userId;
	}

	private function getShareFromSessionToken(): Share {
		$token = $this->session->get('ncPollsPublicToken');
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR)));

		return $this->findEntity($qb);
	}

}
