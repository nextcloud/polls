<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Helper;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\IL10N;
use OCP\L10N\IFactory;
use Psr\Container\ContainerInterface;

abstract class Container {
	public static function getContainer(): ContainerInterface {
		$app = new App(AppConstants::APP_ID);
		return $app->getContainer();
	}

	public static function queryClass(string $class): mixed {
		return self::getContainer()->get($class);
	}

	public static function queryPoll(int $pollId): Poll {
		return self::queryClass(PollMapper::class)->find($pollId);
	}

	public static function findShare(int $pollId, string $userId): Share {
		return self::queryClass(ShareMapper::class)
			->findByPollAndUser($pollId, $userId);
	}

	public static function getL10N(?string $lang = null): IL10N {
		return self::queryClass(IFactory::class)->get(AppConstants::APP_ID, $lang);
	}
	public static function isAppEnabled(string $app): bool {
		return self::queryClass(IAppManager::class)->isEnabledForUser($app);
	}
}
