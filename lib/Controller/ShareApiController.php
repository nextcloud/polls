<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Attributes\ShareTokenRequired;
use OCA\Polls\Model\SentResult;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\ShareService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type PollsShare from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsSentResult from \OCA\Polls\ResponseDefinitions
 */
class ShareApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private MailService $mailService,
		private ShareService $shareService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List shares of a poll
	 * 200: Returns list of shares
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{shares: list<PollsShare>}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/shares')]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['shares' => $this->shareService->list($pollId)]);
	}

	/**
	 * Get share by token
	 * 200: Returns share
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[PublicPage]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/share/{token}')]
	public function get(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->getEffectiveShare($token)]);
	}

	/**
	 * Register as guest for a public poll
	 * 201: Guest registered
	 * @param string $token Share token
	 * @param string $displayName Guest display name
	 * @param string $emailAddress Guest email address
	 * @param string $timeZone Guest time zone
	 * @return DataResponse<Http::STATUS_CREATED, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[PublicPage]
	#[ShareTokenRequired]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: 'api/v1.0/s/{token}/register')]
	public function register(string $token, string $displayName, string $emailAddress = '', string $timeZone = ''): DataResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->registerGuest($token, $displayName, $emailAddress, $timeZone),
		], Http::STATUS_CREATED);
	}

	/**
	 * Set email address for a share
	 * 200: Email address updated
	 * @param string $token Share token
	 * @param string $emailAddress New email address
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[PublicPage]
	#[ShareTokenRequired]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: 'api/v1.0/s/{token}/email/{emailAddress}')]
	public function setEmailAddress(string $token, string $emailAddress = ''): DataResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($this->shareService->getEffectiveShare($token), $emailAddress)
		]);
	}

	/**
	 * Delete email address from share
	 * 200: Email address removed
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[PublicPage]
	#[ShareTokenRequired]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: 'api/v1.0/s/{token}/email')]
	public function deleteEmailAddress(string $token): DataResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->deleteEmailAddress($this->shareService->getEffectiveShare($token))
		]);
	}

	/**
	 * Add share to a poll
	 * 201: Share added
	 * @param int $pollId Poll id
	 * @param string $type Share type
	 * @param string $userId User id
	 * @param string $displayName Display name of user
	 * @param string $emailAddress Email address of user
	 * @return DataResponse<Http::STATUS_CREATED, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/share/{type}')]
	public function add(int $pollId, string $type, string $userId = '', string $displayName = '', string $emailAddress = ''): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->add($pollId, $type, $userId, $displayName, $emailAddress)], Http::STATUS_CREATED);
	}

	/**
	 * Delete share
	 * 200: Share deleted
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/share/{token}')]
	public function delete(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token)]);
	}

	/**
	 * Restore deleted share
	 * 200: Share restored
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/restore')]
	public function restore(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token, restore: true)]);
	}

	/**
	 * Lock a share (read only)
	 * 200: Share locked
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/lock')]
	public function lock(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token)]);
	}

	/**
	 * Unlock share
	 * 200: Share unlocked
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/unlock')]
	public function unlock(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token, unlock: true)]);
	}

	/**
	 * Send invitation mails for a share
	 * 200: Invitation sent
	 * @param string $token Share token
	 * @return DataResponse<Http::STATUS_OK, array{share: PollsShare, sentResult: PollsSentResult}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/invite')]
	public function sendInvitation(string $token): DataResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->mailService->sendInvitation($share, new SentResult()),
		]);
	}
}
