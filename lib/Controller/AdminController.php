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
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
	public function index(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		return new TemplateResponse(AppConstants::APP_ID, 'main', ['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * Get list of polls for administrative purposes
	 */
	public function list(): JSONResponse {
		return $this->response(fn () => $this->pollService->listForAdmin());
	}

	/**
	 * Takeover ownership of a poll
	 * @param int $pollId PollId to take over
	 */
	public function takeover(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->takeover($pollId));
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @param int $pollId poll id
	 */
	public function toggleArchive(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->toggleArchive($pollId));
	}

	/**
	 * Delete poll
	 * @param int $pollId poll id
	 */
	public function delete(int $pollId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => $this->pollService->delete($pollId));
	}

	public function runAutoReminderJob(): JSONResponse {
		return $this->response(fn () => $this->autoReminderCron->manuallyRun());
	}
	public function runJanitorJob(): JSONResponse {
		return $this->response(fn () => $this->janitorCron->manuallyRun());
	}
	public function runNotificationJob(): JSONResponse {
		return $this->response(fn () => $this->notificationCron->manuallyRun());
	}
}
