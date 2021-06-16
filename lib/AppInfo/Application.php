<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

namespace OCA\Polls\AppInfo;

use Closure;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Notification\IManager as NotificationManager;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\OptionConfirmedEvent;
use OCA\Polls\Event\OptionCreatedEvent;
use OCA\Polls\Event\OptionDeletedEvent;
use OCA\Polls\Event\PollArchivedEvent;
use OCA\Polls\Event\PollCreatedEvent;
use OCA\Polls\Event\PollDeletedEvent;
use OCA\Polls\Event\PollExpiredEvent;
use OCA\Polls\Event\PollRestoredEvent;
use OCA\Polls\Event\PollTakeoverEvent;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Event\ShareEvent;
use OCA\Polls\Event\VoteEvent;
use OCA\Polls\Notification\Notifier;
use OCA\Polls\Listener\UserDeletedListener;
use OCA\Polls\Listener\GroupDeletedListener;
use OCA\Polls\Listener\CommentListener;
use OCA\Polls\Listener\OptionListener;
use OCA\Polls\Listener\PollListener;
use OCA\Polls\Listener\ShareListener;
use OCA\Polls\Listener\VoteListener;

class Application extends App implements IBootstrap {

	/** @var string */
	public const APP_ID = 'polls';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNotifications']));
	}

	public function register(IRegistrationContext $context): void {
		include_once __DIR__ . '/../../vendor/autoload.php';

		$context->registerEventListener(CommentEvent::class, CommentListener::class);
		$context->registerEventListener(OptionEvent::class, OptionListener::class);
		$context->registerEventListener(OptionConfirmedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionCreatedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionDeletedEvent::class, OptionListener::class);
		$context->registerEventListener(PollArchivedEvent::class, PollListener::class);
		$context->registerEventListener(PollCreatedEvent::class, PollListener::class);
		$context->registerEventListener(PollDeletedEvent::class, PollListener::class);
		$context->registerEventListener(PollExpiredEvent::class, PollListener::class);
		$context->registerEventListener(PollRestoredEvent::class, PollListener::class);
		$context->registerEventListener(PollTakeoverEvent::class, PollListener::class);
		$context->registerEventListener(PollUpdatedEvent::class, PollListener::class);
		$context->registerEventListener(ShareEvent::class, ShareListener::class);
		$context->registerEventListener(VoteEvent::class, VoteListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
	}

	public function registerNotifications(NotificationManager $notificationManager): void {
		$notificationManager->registerNotifierService(Notifier::class);
	}
}
