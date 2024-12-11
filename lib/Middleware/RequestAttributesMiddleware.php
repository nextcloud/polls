<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Middleware;

use OCA\Polls\Attributes\ShareTokenRequired;
use OCA\Polls\UserSession;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;
use ReflectionMethod;

class RequestAttributesMiddleware extends Middleware {
	private const CLIENT_ID_KEY = 'Nc-Polls-Client-Id';
	private const TIME_ZONE_KEY = 'Nc-Polls-Client-Time-Zone';
	private const SHARE_TOKEN = 'Nc-Polls-Share-Token';

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		protected IRequest $request,
		protected ISession $session,
		protected UserSession $userSession,
	) {
	}
	
	public function beforeController(Controller $controller, string $methodName): void {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$clientId = $this->request->getHeader(self::CLIENT_ID_KEY);
		$clientTimeZone = $this->request->getHeader(self::TIME_ZONE_KEY);

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
		

		if ($this->hasAttribute($reflectionMethod, ShareTokenRequired::class)) {
			$this->userSession->setShareToken($this->getShareTokenFromURI());
		}
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
	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	protected function hasAttribute(ReflectionMethod $reflectionMethod, string $attributeClass): bool {
		if (empty($reflectionMethod->getAttributes($attributeClass))) {
			return false;
		}
		
		return true;
	}

}
