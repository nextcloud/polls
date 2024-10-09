<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Filter;

use OCA\Polls\AppConstants;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\ShareEvent;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * @psalm-suppress UnusedClass
 */
class ActivityFilter implements \OCP\Activity\IFilter {
	private $l10n;
	private $urlGenerator;
	private const ALLOWED_TYPES = [
		ShareEvent::ADD,
		ShareEvent::ADD_PUBLIC,
		ShareEvent::CHANGE_EMAIL,
		ShareEvent::CHANGE_DISPLAY_NAME,
		ShareEvent::CHANGE_LABEL,
		ShareEvent::CHANGE_TYPE,
		ShareEvent::CHANGE_REG_CONSTR,
		ShareEvent::REGISTRATION,
		ShareEvent::DELETE,
		ShareEvent::LOCKED,
		ShareEvent::UNLOCKED,
		PollEvent::ADD,
		PollEvent::UPDATE,
		PollEvent::DELETE,
		PollEvent::RESTORE,
		PollEvent::EXPIRE,
		PollEvent::CLOSE,
		PollEvent::REOPEN,
		PollEvent::OWNER_CHANGE,
		PollEvent::OPTION_REORDER,
		OptionEvent::ADD,
		OptionEvent::UPDATE,
		OptionEvent::CONFIRM,
		OptionEvent::UNCONFIRM,
		OptionEvent::DELETE,
		CommentEvent::DELETE,
	];

	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
	) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	/*
	 * @inheritdoc
	 */
	public function getIdentifier(): string {
		return AppConstants::APP_ID;
	}

	/*
	 * @inheritdoc
	 */
	public function getName(): string {
		return $this->l10n->t('Polls');
	}

	/*
	 * @inheritdoc
	 */
	public function getPriority(): int {
		return 90;
	}

	/*
	 * @inheritdoc
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath(AppConstants::APP_ID, 'polls-dark.svg');
	}

	/*
	 * @inheritdoc
	 */
	public function filterTypes(array $types): array {
		// return $types;
		return array_merge($types, self::ALLOWED_TYPES);
	}

	/*
	 * @inheritdoc
	 */
	public function allowedApps(): array {
		return [AppConstants::APP_ID];
	}
}
