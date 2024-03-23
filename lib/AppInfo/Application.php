<?php

declare(strict_types=1);
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

use OCA\Polls\AppConstants;
use OCA\Polls\Dashboard\PollWidget;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Event\CommentAddEvent;
use OCA\Polls\Event\CommentDeleteEvent;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\OptionConfirmedEvent;
use OCA\Polls\Event\OptionCreatedEvent;
use OCA\Polls\Event\OptionDeletedEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\PollCloseEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\PollExpiredEvent;
use OCA\Polls\Event\PollOptionReorderedEvent;
use OCA\Polls\Event\PollOwnerChangeEvent;
use OCA\Polls\Event\PollReopenEvent;
use OCA\Polls\Event\PollRestoredEvent;
use OCA\Polls\Event\PollTakeoverEvent;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Event\ShareChangedDisplayNameEvent;
use OCA\Polls\Event\ShareChangedEmailEvent;
use OCA\Polls\Event\ShareChangedLabelEvent;
use OCA\Polls\Event\ShareChangedRegistrationConstraintEvent;
use OCA\Polls\Event\ShareCreateEvent;
use OCA\Polls\Event\ShareDeletedEvent;
use OCA\Polls\Event\ShareEvent;
use OCA\Polls\Event\ShareLockedEvent;
use OCA\Polls\Event\ShareRegistrationEvent;
use OCA\Polls\Event\ShareTypeChangedEvent;
use OCA\Polls\Event\VoteEvent;
use OCA\Polls\Event\VoteSetEvent;
use OCA\Polls\Listener\CommentListener;
use OCA\Polls\Listener\GroupDeletedListener;
use OCA\Polls\Listener\OptionListener;
use OCA\Polls\Listener\PollListener;
use OCA\Polls\Listener\ShareListener;
use OCA\Polls\Listener\UserDeletedListener;
use OCA\Polls\Listener\VoteListener;
use OCA\Polls\Middleware\RequestAttributesMiddleware;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Notification\Notifier;
use OCA\Polls\Provider\SearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\Events\UserDeletedEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
class Application extends App implements IBootstrap {
	/** @var string */
	public const APP_ID = AppConstants::APP_ID;

	public function __construct(array $urlParams = []) {
		parent::__construct(AppConstants::APP_ID, $urlParams);
	}

	public function boot(IBootContext $context): void {
		# empty method, but is mandatory as defined in the interface
	}

	public function register(IRegistrationContext $context): void {
		include_once __DIR__ . '/../../vendor/autoload.php';
		$this->registerServices($context);

		$context->registerMiddleWare(RequestAttributesMiddleware::class);
		$context->registerNotifierService(Notifier::class);
		
		$context->registerEventListener(CommentEvent::class, CommentListener::class);
		$context->registerEventListener(CommentAddEvent::class, CommentListener::class);
		$context->registerEventListener(CommentDeleteEvent::class, CommentListener::class);

		$context->registerEventListener(OptionEvent::class, OptionListener::class);
		$context->registerEventListener(OptionConfirmedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionCreatedEvent::class, OptionListener::class);
		$context->registerEventListener(OptionDeletedEvent::class, OptionListener::class);
		
		$context->registerEventListener(PollEvent::class, PollListener::class);
		$context->registerEventListener(PollExpiredEvent::class, PollListener::class);
		$context->registerEventListener(PollOptionReorderedEvent::class, PollListener::class);
		$context->registerEventListener(PollOwnerChangeEvent::class, PollListener::class);
		$context->registerEventListener(PollRestoredEvent::class, PollListener::class);
		$context->registerEventListener(PollTakeoverEvent::class, PollListener::class);
		$context->registerEventListener(PollUpdatedEvent::class, PollListener::class);
		$context->registerEventListener(PollReopenEvent::class, PollListener::class);
		$context->registerEventListener(PollCloseEvent::class, PollListener::class);

		$context->registerEventListener(ShareEvent::class, ShareListener::class);
		$context->registerEventListener(ShareChangedDisplayNameEvent::class, ShareListener::class);
		$context->registerEventListener(ShareChangedLabelEvent::class, ShareListener::class);
		$context->registerEventListener(ShareChangedEmailEvent::class, ShareListener::class);
		$context->registerEventListener(ShareChangedRegistrationConstraintEvent::class, ShareListener::class);
		$context->registerEventListener(ShareCreateEvent::class, ShareListener::class);
		$context->registerEventListener(ShareDeletedEvent::class, ShareListener::class);
		$context->registerEventListener(ShareLockedEvent::class, ShareListener::class);
		$context->registerEventListener(ShareRegistrationEvent::class, ShareListener::class);
		$context->registerEventListener(ShareTypeChangedEvent::class, ShareListener::class);

		$context->registerEventListener(VoteEvent::class, VoteListener::class);
		$context->registerEventListener(VoteSetEvent::class, VoteListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);

		$context->registerSearchProvider(SearchProvider::class);
		$context->registerDashboardWidget(PollWidget::class);
	}

	/**
	 * Register some Services
	 */
	private function registerServices(IRegistrationContext $context): void {
		$context->registerService(UserMapper::class, function (ContainerInterface $c): UserMapper {
			return new UserMapper(
				$c->get(IDBConnection::class),
				$c->get(ISession::class),
				$c->get(IUserSession::class),
				$c->get(IUserManager::class),
				$c->get(LoggerInterface::class),
			);
		});

		$context->registerService(AppSettings::class, function (ContainerInterface $c): AppSettings {
			return new AppSettings(
				$c->get(IConfig::class),
				$c->get(IGroupManager::class),
				$c->get(IUserSession::class),
			);
		});

		$context->registerService(PollMapper::class, function (ContainerInterface $c): PollMapper {
			return new PollMapper(
				$c->get(IDBConnection::class),
				$c->get(UserMapper::class)
			);
		});

		$context->registerService(CommentMapper::class, function (ContainerInterface $c): CommentMapper {
			return new CommentMapper(
				$c->get(IDBConnection::class),
			);
		});

		$context->registerService(VoteMapper::class, function (ContainerInterface $c): VoteMapper {
			return new VoteMapper(
				$c->get(IDBConnection::class),
				$c->get(LoggerInterface::class),
			);
		});

		$context->registerService(OptionMapper::class, function (ContainerInterface $c): OptionMapper {
			return new OptionMapper(
				$c->get(IDBConnection::class),
				$c->get(UserMapper::class),
			);
		});

		$context->registerService(SubscriptionMapper::class, function (ContainerInterface $c): SubscriptionMapper {
			return new SubscriptionMapper(
				$c->get(IDBConnection::class),
			);
		});

		$context->registerService(LogMapper::class, function (ContainerInterface $c): LogMapper {
			return new LogMapper(
				$c->get(IDBConnection::class),
			);
		});
	}
}
