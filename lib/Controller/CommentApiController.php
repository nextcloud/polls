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

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\CommentService;

class CommentApiController extends ApiController {

	/** @var CommentService */
	private $commentService;

	use ResponseHandle;

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
		return $this->response(function () use ($pollId) {
			return ['comments' => $this->commentService->list($pollId)];
		});
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
		return $this->response(function () use ($pollId, $message) {
			return ['comment'=> $this->commentService->add($pollId, $message)];
		});
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
		return $this->responseDeleteTolerant(function () use ($commentId) {
			return ['comment'=> $this->commentService->delete($commentId)];
		});
	}
}
