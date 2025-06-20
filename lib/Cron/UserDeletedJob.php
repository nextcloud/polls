<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;

use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\ISession;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
class UserDeletedJob extends QueuedJob {
	public function __construct(
		private CommentMapper $commentMapper,
		private ISecureRandom $secureRandom,
		protected ITimeFactory $time,
		private LoggerInterface $logger,
		private LogMapper $logMapper,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private PreferencesMapper $preferencesMapper,
		private ShareMapper $shareMapper,
		private SubscriptionMapper $subscriptionMapper,
		private VoteMapper $voteMapper,
		private ISession $session,
	) {
		parent::__construct($time);
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->session->set(AppConstants::SESSION_KEY_CRON_JOB, true);
		$userId = $argument['userId'];
		$this->logger->info('Deleting polls for deleted user', [
			'userId' => $userId
		]);

		$replacementName = 'deleted_' . $this->secureRandom->generate(
			8,
			ISecureRandom::CHAR_DIGITS
			. ISecureRandom::CHAR_LOWER
			. ISecureRandom::CHAR_UPPER
		);

		$this->pollMapper->deleteByUserId($userId);
		$this->logMapper->deleteByUserId($userId);
		$this->shareMapper->deleteByIdAndType($userId, Share::TYPE_USER);
		$this->preferencesMapper->deleteByUserId($userId);
		$this->subscriptionMapper->deleteByUserId($userId);
		$this->commentMapper->renameUserId($userId, $replacementName);
		$this->optionMapper->renameUserId($userId, $replacementName);
		$this->voteMapper->renameUserId($userId, $replacementName);
		$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
	}
}
