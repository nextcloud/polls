<?php
/**
 * @copyright Copyright (c) 2021 Jonas Rittershofer <jotoeri@users.noreply.github.com>
 *
 * @author Jonas Rittershofer <jotoeri@users.noreply.github.com>
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

namespace OCA\Polls\Cron;

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\ILogger;

class UserDeletedJob extends QueuedJob {

	/** @var PollMapper */
	private $pollMapper;

	/** @var PollService */
	private $pollService;

	/** @var ILogger */
	private $logger;

	public function __construct(PollMapper $pollMapper,
								PollService $pollService,
								ITimeFactory $time,
								ILogger $logger) {
		parent::__construct($time);

		$this->pollMapper = $pollMapper;
		$this->pollService = $pollService;
		$this->logger = $logger;
	}

	protected function run($arguments) {
		$owner = $arguments['owner'];
		$this->logger->info('Deleting polls for deleted user {user}', [
			'user' => $owner
		]);

		$polls = $this->pollMapper->findForMe($owner);
		foreach ($polls as $poll) {
			$this->pollService->delete($poll->getId());
		}
	}
}
