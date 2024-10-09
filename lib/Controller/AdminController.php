<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\AppConstants;
use OCA\Polls\Cron\AutoReminderCron;
use OCA\Polls\Cron\JanitorCron;
use OCA\Polls\Cron\NotificationCron;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * @psalm-api
 */
class AdminController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private PollService $pollService,
		private IEventDispatcher $eventDispatcher,
		private AutoReminderCron $autoReminderCron,
		private JanitorCron $janitorCron,
		private NotificationCron $notificationCron,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Load admin page
	 */
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/administration')]
	public function index(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		return new TemplateResponse(AppConstants::APP_ID, 'main', ['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * Get list of polls for administrative purposes
	 */
	#[FrontpageRoute(verb: 'GET', url: '/administration/polls')]
	public function list(): JSONResponse {
		return $this->response(fn () => $this->pollService->listForAdmin());
	}

	/**
	 * Takeover ownership of a poll
	 * @param int $pollId PollId to take over
	 */
	#[FrontpageRoute(verb: 'PUT', url: '/administration/poll/{pollId}/takeover')]
	public function takeover(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->takeover($pollId));
	}

	/**
	 * Run auto reminder job
	 */
	#[FrontpageRoute(verb: 'GET', url: '/administration/autoReminder/run')]
	public function runAutoReminderJob(): JSONResponse {
		return $this->response(fn () => $this->autoReminderCron->manuallyRun());
	}

	/**
	 * Run janitor job
	 */
	#[FrontpageRoute(verb: 'GET', url: '/administration/janitor/run')]
	public function runJanitorJob(): JSONResponse {
		return $this->response(fn () => $this->janitorCron->manuallyRun());
	}

	/**
	 * Run notification job
	 */
	#[FrontpageRoute(verb: 'GET', url: '/administration/notification/run')]
	public function runNotificationJob(): JSONResponse {
		return $this->response(fn () => $this->notificationCron->manuallyRun());
	}

	/**
	 * Switch archived status (move to archived polls)
	 * @param int $pollId poll id
	 * @deprecated 8.0.0 Not used anymore (use PUT /poll/{pollId}/toggleArchive)
	 */
	#[FrontpageRoute(verb: 'PUT', url: '/administration/poll/{pollId}/toggleArchive')]
	public function toggleArchive(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->toggleArchive($pollId));
	}

	/**
	 * Delete poll
	 * @param int $pollId poll id
	 * @deprecated 8.0.0 Not used anymore (use DELETE /poll/{pollId})
	 */
	#[FrontpageRoute(verb: 'DELETE', url: '/administration/poll/{pollId}')]
	public function delete(int $pollId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => $this->pollService->delete($pollId));
	}
}
