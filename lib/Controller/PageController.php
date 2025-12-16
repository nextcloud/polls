<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\AppConstants;
use OCA\Polls\Service\NotificationService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Util;

/**
 * @psalm-api
 */
class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private NotificationService $notificationService,
		private IEventDispatcher $eventDispatcher,
		private IAppManager $appManager,
		private string $scriptPrefix = '',
	) {
		parent::__construct($appName, $request);
		$this->scriptPrefix = 'polls-' . $this->appManager->getAppVersion(AppConstants::APP_ID) . '-';
	}

	/**
	 * render index page
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/', postfix: 'index')]
	#[FrontpageRoute(verb: 'GET', url: '/combo', postfix: 'combo')]
	#[FrontpageRoute(verb: 'GET', url: '/list/{category}', postfix: 'list')]
	#[FrontpageRoute(verb: 'GET', url: '/group/{slug}', postfix: 'group')]
	public function index(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, $this->scriptPrefix . 'main');
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		$response = new TemplateResponse(AppConstants::APP_ID, 'main');
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedWorkerSrcDomain('blob:');
		$csp->addAllowedWorkerSrcDomain("'self'");
		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	/**
	 * render vote page
	 * @param $id poll id
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/vote/{id}')]
	public function vote(int $id): TemplateResponse {
		$this->notificationService->removeNotificationsForPoll($id);
		Util::addScript(AppConstants::APP_ID, $this->scriptPrefix . 'main');
		$response = new TemplateResponse(AppConstants::APP_ID, 'main');
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedWorkerSrcDomain('blob:');
		$csp->addAllowedWorkerSrcDomain("'self'");
		$response->setContentSecurityPolicy($csp);
		return $response;
	}
}
