<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\CommentService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type PollsComment from \OCA\Polls\ResponseDefinitions
 */
class CommentApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private CommentService $commentService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all comments of a poll
	 * 200: Returns list of comments
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{comments: list<PollsComment>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/comments')]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['comments' => array_values(array_map(fn ($c) => $c->jsonSerialize(), $this->commentService->list($pollId)))]);
	}

	/**
	 * Add comment
	 * 200: Comment added
	 * @param int $pollId Poll id
	 * @param string $comment Comment text to add
	 * @return DataResponse<Http::STATUS_OK, array{comment: PollsComment}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/comment')]
	public function add(int $pollId, string $comment): DataResponse {
		return $this->response(fn () => ['comment' => $this->commentService->add($comment, $pollId)->jsonSerialize()]);
	}

	/**
	 * Delete comment
	 * 200: Comment deleted
	 * @param int $commentId Id of comment to delete
	 * @return DataResponse<Http::STATUS_OK, array{comment: PollsComment}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/comment/{commentId}')]
	public function delete(int $commentId): DataResponse {
		return $this->response(fn () => ['comment' => $this->commentService->delete($commentId)->jsonSerialize()]);
	}

	/**
	 * Restore comment
	 * 200: Comment restored
	 * @param int $commentId Id of comment to restore
	 * @return DataResponse<Http::STATUS_OK, array{comment: PollsComment}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/comment/{commentId}/restore')]
	public function restore(int $commentId): DataResponse {
		return $this->response(fn () => ['comment' => $this->commentService->restore($commentId)->jsonSerialize()]);
	}
}
