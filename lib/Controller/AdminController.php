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
use OCA\Polls\Service\SystemService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\BackgroundJob\IJob;
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
		private SystemService $systemService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Load admin page
	 */
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/administration')]
	public function index(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		return new TemplateResponse(AppConstants::APP_ID, 'main', ['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * Get list of cron jobs
	 * @return JSONResponse A list of cron jobs
	 */
	#[FrontpageRoute(verb: 'GET', url: '/administration/jobs')]
	public function getJobs(): JSONResponse {
		return $this->response(fn () => ['jobs' => $this->systemService->getCronJobs()]);
	}

	/**
	 * Run job
	 * @param class-string<IJob> $job Job to run
	 */
	#[FrontpageRoute(verb: 'POST', url: '/administration/job/run')]
	public function runJob($job): JSONResponse {
		return $this->response(fn () => ['job' => $this->systemService->runJob($job)]);
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
		return $this->response(fn () => [
			'poll' => $this->pollService->takeover($pollId)
		]);
	}

	/**
	 * Run auto reminder job
	 */
	#[FrontpageRoute(verb: 'GET', url: '/administration/autoreminder/run')]
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
}
