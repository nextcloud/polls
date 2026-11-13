<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Middleware;

use OCA\Polls\Attributes\ShareTokenRequired;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Middleware\RequestAttributesMiddleware;
use OCA\Polls\UserSession;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TooManyRequestsResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenTestController extends Controller {
	#[ShareTokenRequired]
	public function validated(): void {
	}

	public function withoutToken(): void {
	}
}

class TokenTestOCSController extends OCSController {
	#[ShareTokenRequired]
	public function validated(): void {
	}
}

/**
 * Brute force protection against share token enumeration
 */
class RequestAttributesMiddlewareTest extends TestCase {
	private const REMOTE_ADDRESS = '192.0.2.1';
	private const LOGIN_URL = '/login';
	private const ACCEPT_HTML = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
	private const ACCEPT_JSON = 'application/json, text/plain, */*';

	private IRequest&MockObject $request;
	private UserSession&MockObject $userSession;
	private IThrottler&MockObject $throttler;
	private PollMapper&MockObject $pollMapper;
	private IUserManager&MockObject $userManager;
	private IAppConfig&MockObject $appConfig;
	private RequestAttributesMiddleware $middleware;
	private TokenTestController $controller;
	private TokenTestOCSController $ocsController;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getRemoteAddress')->willReturn(self::REMOTE_ADDRESS);
		$this->userSession = $this->createMock(UserSession::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->pollMapper = $this->createMock(PollMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRoute')->with('core.login.showLoginForm')->willReturn(self::LOGIN_URL);

		$this->middleware = new RequestAttributesMiddleware(
			$this->request,
			$this->createMock(ISession::class),
			$this->userSession,
			$this->throttler,
			$urlGenerator,
			$this->pollMapper,
			$this->userManager,
			$this->appConfig,
		);
		$this->controller = new TokenTestController('polls', $this->request);
		$this->ocsController = new TokenTestOCSController('polls', $this->request);
	}

	private function withToken(string $token, string $accept = self::ACCEPT_JSON): void {
		$this->request->method('getParam')->willReturnMap([['token', null, $token]]);
		$this->request->method('getHeader')->willReturnCallback(
			fn (string $name) => $name === 'Accept' ? $accept : ''
		);
		$this->userSession->method('hasShare')->willReturn($token !== '');
	}

	/**
	 * Run the middleware with an unknown token and return the rejection
	 */
	private function rejectUnknownToken(Controller $controller, string $accept = self::ACCEPT_JSON): ShareNotFoundException {
		$this->withToken('unknownToken', $accept);
		$exception = new ShareNotFoundException();
		$this->userSession->method('getShare')->willThrowException($exception);

		try {
			$this->middleware->beforeController($controller, 'validated');
			$this->fail('Unknown token must be rejected');
		} catch (ShareNotFoundException $e) {
			$this->assertSame($exception, $e);
		}
		return $exception;
	}

	public function testValidTokenIsNotRegisteredAsFailedAttempt(): void {
		$this->withToken('validToken');
		$this->userSession->expects($this->once())->method('setShareToken')->with('validToken');
		$this->userSession->method('getShare')->willReturn($this->createMock(Share::class));

		$this->throttler->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with(self::REMOTE_ADDRESS, RequestAttributesMiddleware::BRUTEFORCE_ACTION);
		$this->throttler->expects($this->never())->method('registerAttempt');

		$this->middleware->beforeController($this->controller, 'validated');
	}

	/**
	 * Prepare a valid token for a poll owned by an account with the given state
	 */
	private function withPollOwner(bool $ownerEnabled, string $hideDisabledUserShares = 'yes'): void {
		$this->withToken('validToken');
		$share = new Share();
		$share->setPollId(1);
		$share->setToken('validToken');
		$this->userSession->method('getShare')->willReturn($share);

		$poll = new Poll();
		$poll->setOwner('alice');
		$this->pollMapper->method('get')->with(1)->willReturn($poll);

		$owner = $this->createMock(IUser::class);
		$owner->method('isEnabled')->willReturn($ownerEnabled);
		$this->userManager->method('get')->with('alice')->willReturn($owner);

		$this->appConfig->method('getValueString')
			->with('files_sharing', 'hide_disabled_user_shares', 'yes')
			->willReturn($hideDisabledUserShares);
	}

	public function testShareOfDisabledOwnerIsRejectedWithoutRegisteringAttempt(): void {
		$this->withPollOwner(ownerEnabled: false);
		$this->throttler->expects($this->never())->method('registerAttempt');

		try {
			$this->middleware->beforeController($this->controller, 'validated');
			$this->fail('Share of a disabled owner must be rejected');
		} catch (ShareNotFoundException $e) {
			$response = $this->middleware->afterException($this->controller, 'validated', $e);
			$this->assertInstanceOf(JSONResponse::class, $response);
			$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		}
	}

	public function testShareOfEnabledOwnerIsAccepted(): void {
		$this->withPollOwner(ownerEnabled: true);

		$this->middleware->beforeController($this->controller, 'validated');
		$this->addToAssertionCount(1);
	}

	public function testShareOfDisabledOwnerIsAcceptedIfNotHiddenByConfig(): void {
		$this->withPollOwner(ownerEnabled: false, hideDisabledUserShares: 'no');

		$this->middleware->beforeController($this->controller, 'validated');
		$this->addToAssertionCount(1);
	}

	public function testUnknownTokenIsRegisteredAndRejectedWithJson(): void {
		$this->throttler->expects($this->once())
			->method('registerAttempt')
			->with(RequestAttributesMiddleware::BRUTEFORCE_ACTION, self::REMOTE_ADDRESS);

		$exception = $this->rejectUnknownToken($this->controller);

		$response = $this->middleware->afterException($this->controller, 'validated', $exception);
		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testUnknownTokenOnPageRedirectsGuestToLogin(): void {
		$this->userSession->method('getIsLoggedIn')->willReturn(false);
		$exception = $this->rejectUnknownToken($this->controller, self::ACCEPT_HTML);

		$response = $this->middleware->afterException($this->controller, 'validated', $exception);
		$this->assertInstanceOf(RedirectResponse::class, $response);
		$this->assertSame(self::LOGIN_URL, $response->getRedirectURL());
	}

	public function testUnknownTokenOnPageShowsNotFoundToLoggedInUser(): void {
		$this->userSession->method('getIsLoggedIn')->willReturn(true);
		$exception = $this->rejectUnknownToken($this->controller, self::ACCEPT_HTML);

		$response = $this->middleware->afterException($this->controller, 'validated', $exception);
		$this->assertInstanceOf(NotFoundResponse::class, $response);
	}

	public function testUnknownTokenOnOcsControllerThrowsOcsNotFound(): void {
		$exception = $this->rejectUnknownToken($this->ocsController);

		$this->expectException(OCSNotFoundException::class);
		$this->middleware->afterException($this->ocsController, 'validated', $exception);
	}

	public function testMissingTokenIsLeftToController(): void {
		$this->withToken('');
		$this->userSession->expects($this->never())->method('getShare');
		$this->throttler->expects($this->never())->method('registerAttempt');

		$this->middleware->beforeController($this->controller, 'validated');
	}

	public function testMethodWithoutAttributeIsNotProtected(): void {
		$this->userSession->expects($this->never())->method('setShareToken');
		$this->throttler->expects($this->never())->method('sleepDelayOrThrowOnMax');

		$this->middleware->beforeController($this->controller, 'withoutToken');
	}

	public function testMaxDelayReachedReturnsTooManyRequests(): void {
		$response = $this->middleware->afterException($this->controller, 'validated', new MaxDelayReached());

		$this->assertInstanceOf(TooManyRequestsResponse::class, $response);
		$this->assertSame(Http::STATUS_TOO_MANY_REQUESTS, $response->getStatus());
	}

	public function testMaxDelayReachedOnOcsControllerThrowsOcsException(): void {
		try {
			$this->middleware->afterException($this->ocsController, 'validated', new MaxDelayReached());
			$this->fail('OCSException expected');
		} catch (OCSException $e) {
			$this->assertSame(Http::STATUS_TOO_MANY_REQUESTS, $e->getCode());
		}
	}

	/**
	 * ShareNotFoundExceptions thrown by the controller itself are not
	 * registered as failed attempts and must be passed through
	 */
	public function testForeignShareNotFoundExceptionIsPassedThrough(): void {
		$exception = new ShareNotFoundException();

		$this->expectExceptionObject($exception);
		$this->middleware->afterException($this->controller, 'validated', $exception);
	}
}
