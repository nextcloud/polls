<?php

namespace OCA\Polls\Middleware;

use OCP\AppFramework\Middleware;
use OCP\ISession;
use OCP\IRequest;

class RequestAttributesMiddleware extends Middleware {
	private const CLIENT_ID_KEY = 'Nc-Polls-Client-Id';

	/** @var ISession */
	protected $session;

	public function __construct(
		IRequest $request,
		ISession $session
	) {
		$this->request = $request;
		$this->session = $session;
	}

	public function beforeController($controller, $methodName) {
		$headers = getallheaders();
		if (array_key_exists(self::CLIENT_ID_KEY, $headers)) {
			$clientId = $headers[self::CLIENT_ID_KEY];
			$this->session->set('ncPollsClientId', $clientId);
		} else {
			// use session_id as fallback, if no clientId is given
			$this->session->set('ncPollsClientId', session_id());
		}
	}
}
