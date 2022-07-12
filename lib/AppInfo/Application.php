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
use OCP\Collaboration\Resources\IProviderManager;
use OCP\Notification\IManager as NotificationManager;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Util;
use OCA\Polls\Event\CommentAddEvent;
use OCA\Polls\Event\CommentDeleteEvent;
use OCA\Polls\Event\OptionConfirmedEvent;
use OCA\Polls\Event\OptionCreatedEvent;
use OCA\Polls\Event\OptionDeletedEvent;
use OCA\Polls\Event\OptionUnconfirmedEvent;
use OCA\Polls\Event\PollOptionReorderedEvent;
use OCA\Polls\Event\OptionUpdatedEvent;
use OCA\Polls\Event\PollArchivedEvent;
use OCA\Polls\Event\PollCreatedEvent;
use OCA\Polls\Event\PollDeletedEvent;
use OCA\Polls\Event\PollExpiredEvent;
use OCA\Polls\Event\PollOwnerChangeEvent;
use OCA\Polls\Event\PollRestoredEvent;
use OCA\Polls\Event\PollTakeoverEvent;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Event\ShareChangedDisplayNameEvent;
use OCA\Polls\Event\ShareCreateEvent;
use OCA\Polls\Event\ShareTypeChangedEvent;
use OCA\Polls\Event\ShareChangedEmailEvent;
use OCA\Polls\Event\ShareChangedRegistrationConstraintEvent;
use OCA\Polls\Event\ShareDeletedEvent;
use OCA\Polls\Event\ShareRegistrationEvent;
use OCA\Polls\Event\VoteSetEvent;
use OCA\Polls\Notification\Notifier;
use OCA\Polls\Listener\UserDeletedListener;
use OCA\Polls\Listener\GroupDeletedListener;
use OCA\Polls\Listener\CommentListener;
use OCA\Polls\Listener\OptionListener;
use OCA\Polls\Listener\PollListener;
use OCA\Polls\Listener\ShareListener;
use OCA\Polls\Listener\VoteListener;
use OCA\Polls\Provider\ResourceProvider;
use OCA\Polls\Provider\SearchProvider;

class Application extends App implements IBootstrap
{

	/** @var string */
	public const APP_ID = 'polls';

	public function __construct(array $urlParams = [])
	{
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function boot(IBootContext $context): void
	{
		$context->injectFn(Closure::fromCallable([$this, 'registerNotifications']));
		$context->injectFn(Closure::fromCallable([$this, 'registerCollaborationResources']));
	}

	public function register(IRegistrationContext $context): void
	{
		include_once __DIR__ . '/../../vendor/autoload.php';

		$context->registerEventListener(CommentAddEvent::class, CommentListener::class);
		$context->registerEventListener(CommentDeleteEvent::class, CommentListener::class);
		$context->registerEventListener(OptionConfirmedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionCreatedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionDeletedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionUnconfirmedEvent::class, OptionListener::class);
		$context->registerEventListener(PollOptionReorderedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionUpdatedEvent::class, OptionListener::class);
		$context->registerEventListener(PollArchivedEvent::class, PollListener::class);
		$context->registerEventListener(PollCreatedEvent::class, PollListener::class);
		$context->registerEventListener(PollDeletedEvent::class, PollListener::class);
		$context->registerEventListener(PollExpiredEvent::class, PollListener::class);
		$context->registerEventListener(PollRestoredEvent::class, PollListener::class);
		$context->registerEventListener(PollOwnerChangeEvent::class, PollListener::class);
		$context->registerEventListener(PollTakeoverEvent::class, PollListener::class);
		$context->registerEventListener(PollUpdatedEvent::class, PollListener::class);
		$context->registerEventListener(ShareChangedEmailEvent::class, ShareListener::class);
		$context->registerEventListener(ShareChangedDisplayNameEvent::class, ShareListener::class);
		$context->registerEventListener(ShareChangedRegistrationConstraintEvent::class, ShareListener::class);
		$context->registerEventListener(ShareCreateEvent::class, ShareListener::class);
		$context->registerEventListener(ShareDeletedEvent::class, ShareListener::class);
		$context->registerEventListener(ShareRegistrationEvent::class, ShareListener::class);
		$context->registerEventListener(ShareTypeChangedEvent::class, ShareListener::class);
		$context->registerEventListener(VoteSetEvent::class, VoteListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$context->registerSearchProvider(SearchProvider::class);
	}

	public function registerNotifications(NotificationManager $notificationManager): void
	{
		$notificationManager->registerNotifierService(Notifier::class);
	}
	protected function registerCollaborationResources(IProviderManager $resourceManager, IEventDispatcher $eventDispatcher): void
	{
		$resourceManager->registerResourceProvider(ResourceProvider::class);
		$eventDispatcher->addListener('\OCP\Collaboration\Resources::loadAdditionalScripts', static function () {
			Util::addScript(self::APP_ID, 'polls-collections');
		});
	}
}
