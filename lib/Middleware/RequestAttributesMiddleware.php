<?php

namespace OCA\Polls\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;

class RequestAttributesMiddleware extends Middleware {
	private const CLIENT_ID_KEY = 'Nc-Polls-Client-Id';

	public function __construct(
		protected IRequest $request,
		protected ISession $session
	) {
	}

	public function beforeController($controller, $methodName): void {
		$clientId = $this->request->getHeader(self::CLIENT_ID_KEY);

		if (!$clientId) {
			$clientId = $this->session->getId();
		}

		if ($clientId) {
			$this->session->set('ncPollsClientId', $clientId);
		}
	}
}
