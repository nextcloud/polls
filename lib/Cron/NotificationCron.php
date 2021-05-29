<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Cron;

use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCA\Polls\Service\MailService;

class NotificationCron extends TimedJob {

	/** @var MailService */
	private $mailService;

	public function __construct(
		ITimeFactory $time,
		MailService $mailService
	) {
		parent::__construct($time);
		$this->mailService = $mailService;
		parent::setInterval(5); // run every 5 minutes
	}

	/**
	 * @param mixed $arguments
	 * @return void
	 */
	protected function run($argument) {
		$this->mailService->sendNotifications();
	}
}
