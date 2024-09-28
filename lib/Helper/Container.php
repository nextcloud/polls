<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Helper;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Server;

abstract class Container {
	public static function queryClass(string $class): mixed {
		return Server::get($class);
	}

	public static function getPoll(int $pollId, bool $getDeleted = false): Poll {
		return Server::get(PollMapper::class)->get($pollId, $getDeleted);
	}

	public static function queryPoll(int $pollId): Poll {
		return Server::get(PollMapper::class)->find($pollId);
	}

	public static function findShare(int $pollId, string $userId): Share {
		return Server::get(ShareMapper::class)->findByPollAndUser($pollId, $userId);
	}

	public static function getL10N(string|null $lang = null): IL10N {
		return Server::get(IFactory::class)->get(AppConstants::APP_ID, $lang);
	}
	public static function isAppEnabled(string $app): bool {
		return Server::get(IAppManager::class)->isEnabledForUser($app);
	}
}
