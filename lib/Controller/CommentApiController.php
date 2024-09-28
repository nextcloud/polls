<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\CommentService;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class CommentApiController extends BaseApiController {
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
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list($pollId)
		]);
	}

	/**
	 * Add comment
	 * @param int $pollId poll id
	 * @param string $comment Comment text to add
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function add(int $pollId, string $comment): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($comment, $pollId)
		]);
	}

	/**
	 * Delete comment
	 * @param int $commentId Id of comment to delete
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function delete(int $commentId): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->delete($commentId)]);
	}

	/**
	 * Restore comment
	 * @param int $commentId Id of comment to restore
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function restore(int $commentId): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->delete($commentId, true)
		]);
	}
}
