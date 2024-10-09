<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\AppConstants;
use OCA\Polls\Service\NotificationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * render index page
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/', postfix: 'index')]
	#[FrontpageRoute(verb: 'GET', url: '/combo', postfix: 'combo')]
	#[FrontpageRoute(verb: 'GET', url: '/not-found', postfix: 'notFound')]
	#[FrontpageRoute(verb: 'GET', url: '/list/{category}', postfix: 'list')]
	public function index(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		return new TemplateResponse(AppConstants::APP_ID, 'main');
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
		$this->notificationService->removeNotification($id);
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		return new TemplateResponse(AppConstants::APP_ID, 'main');
	}
}
