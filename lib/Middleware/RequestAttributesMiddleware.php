<?php

namespace OCA\Polls\Middleware;

use OCP\AppFramework\Middleware;
use OCP\ISession;
use OCP\IRequest;

class RequestAttributesMiddleware extends Middleware {
	private const CLIENT_ID_KEY = 'Nc-Polls-Client-Id';

	/** @var ISession */
	protected $session;

	/** @var IRequest */
	protected $request;

	public function __construct(
		IRequest $request,
		ISession $session
	) {
		$this->request = $request;
		$this->session = $session;
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
