<?php

namespace OCA\Polls\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;

class RequestAttributesMiddleware extends Middleware {
	private const CLIENT_ID_KEY = 'Nc-Polls-Client-Id';
	private const TIME_ZONE_KEY = 'Nc-Polls-Client-Time-Zone';

	public function __construct(
		protected IRequest $request,
		protected ISession $session
	) {
	}

	public function beforeController($controller, $methodName): void {
		$clientId = $this->request->getHeader(self::CLIENT_ID_KEY);
		$clientTimeZone = $this->request->getHeader(self::TIME_ZONE_KEY);

		if (!$clientId) {
			$clientId = $this->session->getId();
		}

		if ($clientId) {
			$this->session->set('ncPollsClientId', $clientId);
		}
		if ($clientTimeZone) {
			$this->session->set('ncPollsClientTimeZone', $clientTimeZone);
		}
	}
}
