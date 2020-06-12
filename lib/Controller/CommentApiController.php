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

use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\CommentService;



class CommentApiController extends ApiController {

	/**
	 * CommentApiController constructor.
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
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId, $token = '') {
		return new DataResponse($this->commentService->get($pollId, $token), Http::STATUS_OK);
	}

	/**
	 * Read all comments of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {
		return new DataResponse($this->commentService->get(0, $token), Http::STATUS_OK);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $message
	 * @param string $token
	 * @return DataResponse
	 */
	public function add($message, $pollId, $token) {
		try {
			return new DataResponse($this->commentService->add($message, $pollId, $token), Http::STATUS_OK);
		} catch (Exception $e) {
			return new OCSForbiddenException($e);
		}
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $commentId
	 * @param string $token
	 * @return DataResponse
	 */
	public function delete($commentId, $token) {
		try {
			return new DataResponse($this->commentService->delete($commentId, $token), Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_UNAUTHORIZED);
		}

	}

}
