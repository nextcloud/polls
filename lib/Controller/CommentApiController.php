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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\CommentService;



class CommentApiController extends ApiController {

	/** @var CommentService */
	private $commentService;

	/**
	 * CommentApiController constructor
	 * @param string $appName
	 * @param IRequest $request
	 * @param CommentService $commentService
	 */

	public function __construct(
		string $appName,
		IRequest $request,
		CommentService $commentService
	) {
		parent::__construct($appName,
			$request,
			'POST, GET, DELETE',
            'Authorization, Content-Type, Accept',
            1728000);
		$this->commentService = $commentService;
	}

	/**
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		try {
			return new DataResponse(['comments' => $this->commentService->list($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll with id ' . $pollId . ' not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Add comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function add($pollId, $message) {
		try {
			return new DataResponse(['comment' => $this->commentService->add($pollId, $message)], Http::STATUS_CREATED);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll with id ' . $pollId . ' not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Delete comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $commentId
	 * @return DataResponse
	 */
	public function delete($commentId) {
		try {
			return new DataResponse(['comment' => $this->commentService->delete($commentId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Comment id ' . $commentId . ' does not exist'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

}
