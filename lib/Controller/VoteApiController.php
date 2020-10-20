<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

namespace OCA\Polls\Controller;

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\VoteService;

class VoteApiController extends ApiController {

	/** @var VoteService */
	private $voteService;

	/**
	 * VoteAPIController constructor
	 * @param string $appName
	 * @param IRequest $request
	 * @param VoteService $voteService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		VoteService $voteService
	) {
		parent::__construct($appName,
			$request,
			'PUT, GET, DELETE',
			'Authorization, Content-Type, Accept',
			1728000);
		$this->voteService = $voteService;
	}

	/**
	 * Read all votes of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		try {
			return new DataResponse(['votes' => $this->voteService->list($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'No votes'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Set vote answer
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $optionId
	 * @param string $setTo
	 * @return DataResponse
	 */
	public function set($optionId, $setTo) {
		try {
			return new DataResponse(['vote' => $this->voteService->set($optionId, $setTo)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option or poll not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}
}
