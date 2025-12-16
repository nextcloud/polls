<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Listener;

use OCA\Polls\AppConstants;
use OCA\Polls\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<Event>
 * @psalm-api
 */
class PollsReferenceListener implements IEventListener {
	public function __construct(
		private IAppManager $appManager,
		private string $scriptPrefix = '',
	) {
		$this->scriptPrefix = 'polls-' . $this->appManager->getAppVersion(AppConstants::APP_ID) . '-';

	}
	public function handle(Event $event): void {
		if (!$event instanceof RenderReferenceEvent) {
			return;
		}

		Util::addScript(Application::APP_ID, $this->scriptPrefix . 'reference');
	}
}
