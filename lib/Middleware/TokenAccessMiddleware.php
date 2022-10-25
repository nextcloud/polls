<?php

namespace OCA\Polls\Middleware;

use OCA\Polls\Controller\PublicController;
use OCP\AppFramework\Middleware;
use OCP\ISession;
use OCP\IRequest;

class TokenAccessMiddleware extends Middleware {
	/** @var IRequest */
	protected $request;

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
		if (!($controller instanceof PublicController)) {
			return;
		}

		$token = $this->request->getParam('token');
		$this->session->set('publicPollToken', $token);
	}
}
