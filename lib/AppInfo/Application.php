<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCA\Polls\Listener\PollsReferenceListener;
use OCA\Polls\Listener\ShareListener;
use OCA\Polls\Listener\UserDeletedListener;
use OCA\Polls\Listener\VoteListener;
use OCA\Polls\Middleware\RequestAttributesMiddleware;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\Settings\SystemSettings;
use OCA\Polls\Notification\Notifier;
use OCA\Polls\Provider\ReferenceProvider;
use OCA\Polls\Provider\SearchProvider;
use OCA\Polls\UserSession;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
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

		$context->registerEventListener(RenderReferenceEvent::class, PollsReferenceListener::class);
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
		$context->registerReferenceProvider(ReferenceProvider::class);

	}

	/**
	 * Register some Services
	 */
	private function registerServices(IRegistrationContext $context): void {
		$context->registerService(UserMapper::class, function (ContainerInterface $c): UserMapper {
			return new UserMapper(
				$c->get(IDBConnection::class),
				$c->get(IUserManager::class),
			);
		});

		$context->registerService(AppSettings::class, function (ContainerInterface $c): AppSettings {
			return new AppSettings(
				$c->get(IAppConfig::class),
				$c->get(UserSession::class),
				$c->get(SystemSettings::class),
				$c->get(LoggerInterface::class),
			);
		});

		$context->registerService(PollMapper::class, function (ContainerInterface $c): PollMapper {
			return new PollMapper(
				$c->get(IDBConnection::class),
				$c->get(UserSession::class),
			);
		});

		$context->registerService(CommentMapper::class, function (ContainerInterface $c): CommentMapper {
			return new CommentMapper(
				$c->get(IDBConnection::class),
				$c->get(UserSession::class),
			);
		});

		$context->registerService(VoteMapper::class, function (ContainerInterface $c): VoteMapper {
			return new VoteMapper(
				$c->get(IDBConnection::class),
				$c->get(LoggerInterface::class),
				$c->get(UserSession::class),
			);
		});

		$context->registerService(OptionMapper::class, function (ContainerInterface $c): OptionMapper {
			return new OptionMapper(
				$c->get(IDBConnection::class),
				$c->get(UserSession::class),
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
