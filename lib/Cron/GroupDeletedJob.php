<?php
/**
 * @copyright Copyright (c) 2021 Jonas Rittershofer <jotoeri@users.noreply.github.com>
 *
 * @author Jonas Rittershofer <jotoeri@users.noreply.github.com>
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

namespace OCA\Polls\Cron;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;

class GroupDeletedJob extends QueuedJob {
	public function __construct(
		private ShareMapper $shareMapper,
		private ISecureRandom $secureRandom,
		protected ITimeFactory $time,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$group = $argument['group'];
		$this->logger->info('Removing group shares for deleted group {group}', [
			'group' => $group
		]);

		$replacementName = 'deleted_' . $this->secureRandom->generate(
			8,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$this->shareMapper->deleteByIdAndType($group, Share::TYPE_GROUP);
	}
}
