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

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;

class UserDeletedJob extends QueuedJob {

	/** @var CommentMapper **/
	private $commentMapper;

	/** @var LogMapper **/
	private $logMapper;

	/** @var OptionMapper **/
	private $optionMapper;

	/** @var PollMapper **/
	private $pollMapper;

	/** @var PreferencesMapper **/
	private $preferencesMapper;

	/** @var SubscriptionMapper **/
	private $subscriptionMapper;

	/** @var VoteMapper **/
	private $voteMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(CommentMapper $commentMapper,
		LogMapper $logMapper,
		OptionMapper $optionMapper,
		PollMapper $pollMapper,
		PreferencesMapper $preferencesMapper,
		SubscriptionMapper $subscriptionMapper,
		VoteMapper $voteMapper,
		ITimeFactory $time,
		LoggerInterface $logger
	) {
		parent::__construct($time);
		$this->commentMapper = $commentMapper;
		$this->logMapper = $logMapper;
		$this->optionMapper = $optionMapper;
		$this->pollMapper = $pollMapper;
		$this->preferencesMapper = $preferencesMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->voteMapper = $voteMapper;
		$this->logger = $logger;
	}

	protected function run($arguments) {
		$owner = $arguments['owner'];
		$this->logger->info('Deleting polls for deleted user {user}', [
			'user' => $owner
		]);

		$replacementName = 'deleted_' . \OC::$server->getSecureRandom()->generate(
			8,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$this->pollMapper->deleteByUserId($owner);
		$this->logMapper->deleteByUserId($owner);
		$this->preferencesMapper->deleteByUserId($owner);
		$this->subscriptionMapper->deleteByUserId($owner);
		$this->commentMapper->renameUserId($owner, $replacementName);
		$this->optionMapper->renameUserId($owner, $replacementName);
		$this->voteMapper->renameUserId($owner, $replacementName);
	}
}
