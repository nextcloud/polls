<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\CommentService;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class CommentApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private CommentService $commentService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read all comments of a poll based on the poll id and return list as array
	 * @param int pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/comments', requirements: ['apiVersion' => '(v2)'])]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['comments' => $this->commentService->list($pollId)]);
	}

	/**
	 * Add comment
	 * @param int $pollId poll id
	 * @param string $comment Comment text to add
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/comment', requirements: ['apiVersion' => '(v2)'])]
	public function add(int $pollId, string $comment): DataResponse {
		return $this->response(fn () => ['comment' => $this->commentService->add($comment, $pollId)]);
	}

	/**
	 * Delete comment
	 * @param int $commentId Id of comment to delete
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/comment/{commentId}', requirements: ['apiVersion' => '(v2)'])]
	public function delete(int $commentId): DataResponse {
		return $this->response(fn () => ['comment' => $this->commentService->delete($commentId)]);
	}

	/**
	 * Restore comment
	 * @param int $commentId Id of comment to restore
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/comment/{commentId}/restore', requirements: ['apiVersion' => '(v2)'])]
	public function restore(int $commentId): DataResponse {
		return $this->response(fn () => ['comment' => $this->commentService->restore($commentId)]);
	}
}
