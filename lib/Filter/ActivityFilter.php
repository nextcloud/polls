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

namespace OCA\Polls\Filter;

use OCA\Polls\AppConstants;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\ShareEvent;
use OCP\IL10N;
use OCP\IURLGenerator;

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
		IURLGenerator $urlGenerator
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
