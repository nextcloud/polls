<?php

namespace OCA\Polls\Middleware;

use OCA\Polls\AppConstants;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
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
	) {
	}

	public function beforeController($controller, $methodName): void {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$clientId = $this->request->getHeader(self::CLIENT_ID_KEY);
		$clientTimeZone = $this->request->getHeader(self::TIME_ZONE_KEY);
		// $shareToken = $this->request->getHeader(self::SHARE_TOKEN);

		if (!$clientId) {
			$clientId = $this->session->getId();
		}

		if ($clientId) {
			$this->session->set(AppConstants::CLIENT_ID, $clientId);
		}
		if ($clientTimeZone) {
			$this->session->set(AppConstants::CLIENT_TZ, $clientTimeZone);
		}

		if (!$this->hasAttribute($reflectionMethod, PublicPage::class)) {
			// authenticated session don't use share tokens
			$this->session->remove(AppConstants::SESSION_KEY_SHARE_TOKEN);
		}

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
