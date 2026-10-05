<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Middleware;

use Exception;
use OCA\Polls\Attributes\ShareTokenRequired;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\UserSession;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TooManyRequestsResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use ReflectionMethod;

class RequestAttributesMiddleware extends Middleware {
	private const CLIENT_ID_KEY = 'Nc-Polls-Client-Id';
	private const TIME_ZONE_KEY = 'Nc-Polls-Client-Time-Zone';
	private const LANGUAGE_KEY = 'Nc-Polls-Client-Language';
	private const SHARE_TOKEN = 'Nc-Polls-Share-Token';
	public const BRUTEFORCE_ACTION = 'polls_share_token';

	private ?ShareNotFoundException $rejectedToken = null;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IRequest $request,
		protected ISession $session,
		protected UserSession $userSession,
		protected IThrottler $throttler,
		protected IURLGenerator $urlGenerator,
	) {
	}

	public function beforeController(Controller $controller, string $methodName): void {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$clientId = $this->request->getHeader(self::CLIENT_ID_KEY);
		$clientTimeZone = $this->request->getHeader(self::TIME_ZONE_KEY);
		$clientLanguage = $this->request->getHeader(self::LANGUAGE_KEY);

		$this->userSession->cleanSession();

		if (!$clientId) {
			$clientId = $this->session->getId();
		}

		if ($clientId) {
			$this->userSession->setClientId($clientId);
		}

		if ($clientTimeZone) {
			$this->userSession->setClientTimeZone($clientTimeZone);
		}

		if ($clientLanguage) {
			$this->userSession->setClientLanguage($clientLanguage);
		}

		if (!empty($reflectionMethod->getAttributes(ShareTokenRequired::class))) {
			$this->userSession->setShareToken($this->getShareTokenFromURI());
			$this->protectShareToken();
		}
	}

	/**
	 * Slow down share token enumeration by using the server's brute force protection
	 *
	 * Unknown tokens are registered as failed attempts. Subsequent requests from
	 * the same IP get delayed and finally blocked, according to the admin's
	 * brute force settings (auth.bruteforce.*, bruteforcesettings allow list).
	 *
	 * @throws MaxDelayReached if the maximum of failed attempts is reached
	 * @throws ShareNotFoundException if the share token does not exist
	 */
	private function protectShareToken(): void {
		$remoteAddress = $this->request->getRemoteAddress();
		$this->throttler->sleepDelayOrThrowOnMax($remoteAddress, self::BRUTEFORCE_ACTION);

		// Requests without token are left to the controller, nothing to enumerate here
		if (!$this->userSession->hasShare()) {
			return;
		}

		try {
			$this->userSession->getShare();
		} catch (ShareNotFoundException $e) {
			$this->throttler->registerAttempt(self::BRUTEFORCE_ACTION, $remoteAddress);
			$this->rejectedToken = $e;
			throw $e;
		}
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($exception instanceof MaxDelayReached) {
			if ($controller instanceof OCSController) {
				throw new OCSException($exception->getMessage(), Http::STATUS_TOO_MANY_REQUESTS);
			}
			return new TooManyRequestsResponse();
		}

		// only handle the rejection from beforeController, not exceptions thrown by the controller
		if ($exception === $this->rejectedToken) {
			if ($controller instanceof OCSController) {
				throw new OCSNotFoundException($exception->getMessage());
			}

			if (stripos($this->request->getHeader('Accept'), 'html') === false) {
				return new JSONResponse(['message' => $exception->getMessage()], Http::STATUS_NOT_FOUND);
			}

			// Page request (e.g. /s/{token}): guests get redirected to the login page
			if ($this->userSession->getIsLoggedIn()) {
				return new NotFoundResponse();
			}
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		}

		throw $exception;
	}

	private function getShareTokenFromURI(): string {
		if ($this->request->getParam('token')) {
			return $this->request->getParam('token');
		}

		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = "$_SERVER[REQUEST_URI]";
			$pattern = '/\/s\/(.*?)(\/|$)/';

			if (preg_match($pattern, $uri, $matches)) {
				return $matches[1];
			}
		}

		// Fallback: check sessionToken in header
		if ($this->request->getHeader(self::SHARE_TOKEN)) {
			return $this->request->getHeader(self::SHARE_TOKEN);
		}

		return '';
	}
}
