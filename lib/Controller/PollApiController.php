<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class PollApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private CommentService $commentService,
		private PollService $pollService,
		private OptionService $optionService,
		private ShareService $shareService,
		private SubscriptionService $subscriptionService,
		private VoteService $voteService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/polls', requirements: ['apiVersion' => '(v2)'])]
	public function list(): DataResponse {
		return $this->response(fn () => ['polls' => $this->pollService->list()]);
	}
	
	/**
	 * get complete poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/poll/{pollId}', requirements: ['apiVersion' => '(v2)'])]
	public function get(int $pollId): DataResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->get($pollId),
			'options' => $this->optionService->list($pollId),
			'votes' => $this->voteService->list($pollId),
			'comments' => $this->commentService->list($pollId),
			'shares' => $this->shareService->list($pollId),
			'subscribed' => $this->subscriptionService->get($pollId),
			'acl' => $this->acl,
		]);
	}
	
	/**
	 * Add poll
	 * @param string $title Poll title
	 * @param string $type Poll type ('datePoll', 'textPoll')
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/poll', requirements: ['apiVersion' => '(v2)'])]
	public function add(string $type, string $title): DataResponse {
		return $this->responseCreate(fn () => ['poll' => $this->pollService->add($type, $title)]);
	}

	/**
	 * Update poll configuration
	 * @param int $pollId Poll id
	 * @param array $pollConfiguration poll config
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/poll/{pollId}', requirements: ['apiVersion' => '(v2)'])]
	public function update(int $pollId, array $pollConfiguration): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->update($pollId, $pollConfiguration)]);
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/poll/{pollId}/archive/toggle', requirements: ['apiVersion' => '(v2)'])]
	public function toggleArchive(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->toggleArchive($pollId)]);
	}

	/**
	 * Close poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/poll/{pollId}/close', requirements: ['apiVersion' => '(v2)'])]
	public function close(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->close($pollId)]);
	}

	/**
	 * Reopen poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/poll/{pollId}/reopen', requirements: ['apiVersion' => '(v2)'])]
	public function reopen(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->reopen($pollId)]);
	}

	/**
	 * Delete poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/{apiVersion}/poll/{pollId}', requirements: ['apiVersion' => '(v2)'])]
	public function delete(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->delete($pollId)]);
	}

	/**
	 * Clone poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/poll/{pollId}/clone', requirements: ['apiVersion' => '(v2)'])]
	public function clone(int $pollId): DataResponse {
		return $this->responseCreate(fn () => ['poll' => $this->pollService->clone($pollId)]);
	}

	/**
	 * Transfer all polls from one user to another (change owner of poll)
	 * @param string $sourceUser User to transfer polls from
	 * @param string $targetUser User to transfer polls to
	 */
	#[CORS]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/poll/transfer/{sourceUser}/{targetUser}', requirements: ['apiVersion' => '(v2)'])]
	public function transferPolls(string $sourceUser, string $targetUser): DataResponse {
		return $this->response(fn () => ['transferred' => $this->pollService->transferPolls($sourceUser, $targetUser)]);
	}

	/**
	 * Transfer singe poll to another user (change owner of poll)
	 * @param int $pollId Poll to transfer
	 * @param string $targetUser User to transfer the poll to
	 */
	#[CORS]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/poll/{pollId}/transfer/{targetUser}', requirements: ['apiVersion' => '(v2)'])]
	public function transferPoll(int $pollId, string $targetUser): DataResponse {
		return $this->response(fn () => ['transferred' => $this->pollService->transferPoll($pollId, $targetUser)]);
	}

	/**
	 * Collect email addresses from particitipants
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/poll/{pollId}/addresses', requirements: ['apiVersion' => '(v2)'])]
	public function getParticipantsEmailAddresses(int $pollId): DataResponse {
		return $this->response(fn () => ['addresses' => $this->pollService->getParticipantsEmailAddresses($pollId)]);
	}

	/**
	 * Get valid values for configuration options
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/poll/enum', requirements: ['apiVersion' => '(v2)'])]
	public function enum(): DataResponse {
		return $this->response(fn () => ['enum' => $this->pollService->getValidEnum()]);
	}
}
