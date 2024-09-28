<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Share;

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Service\ShareService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

trait TShareCommand {
	public function __construct(
		private PollMapper $pollMapper,
		private ShareMapper $shareMapper,
		private ShareService $shareService,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	/**
	 * @psalm-suppress UnusedMethod
	 */
	private function completeUserValues(CompletionContext $context): array {
		return array_map(function (IUser $user) {
			return $user->getUID();
		}, $this->userManager->search($context->getCurrentWord()));
	}

	/**
	 * @psalm-suppress UnusedMethod
	 */
	private function completeGroupValues(CompletionContext $context): array {
		return array_map(function (IGroup $group) {
			return $group->getGID();
		}, $this->groupManager->search($context->getCurrentWord()));
	}
}
