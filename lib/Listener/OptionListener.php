<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Db\Watch;
use OCA\Polls\Service\LogService;
use OCA\Polls\Service\WatchService;

class OptionListener implements IEventListener {

	/** @var LogService */
	private $logService;

	/** @var WatchService */
	private $watchService;

	/** @var string */
	private $table = Watch::OBJECT_OPTIONS;

	public function __construct(
		LogService $logService,
		WatchService $watchService
	) {
		$this->logService = $logService;
		$this->watchService = $watchService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof OptionEvent)) {
			return;
		}

		if ($event->getLogMsg()) {
			$this->logService->setLog($event->getPollId(), $event->getLogMsg(), $event->getActor());
		}
		$this->watchService->writeUpdate($event->getPollId(), $this->table);
	}
}
