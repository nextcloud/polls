<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\User;

use DateTimeZone;
use OCA\Polls\Model\UserBase;

class Anon extends UserBase {
	public const TYPE = 'anonymous';

	public function __construct(string $id) {
		parent::__construct($id, self::TYPE);
	}

	public function getId(): string {
		return $this->getHashedUserId();
	}

	public function getSimpleType(): string {
		return UserBase::TYPE_GUEST;
	}

	public function getLanguageCode(): string {
		return '';
	}

	public function getLocaleCode(): string {
		return '';
	}

	public function getTimeZone(): DateTimeZone {
		return new DateTimeZone('UTC');
	}

	public function getTimeZoneName(): string {
		return 'UTC';
	}

	public function getDisplayName(): string {
		return 'Anon';
	}

	public function getDescription(): string {
		return $this->getDisplayName();
	}

	public function getEmailAddress(): string {
		return '';
	}

	public function getEmailAndDisplayName(): string {
		return $this->getDisplayName();
	}

	public function getHasEmail(): bool {
		return false;
	}

	public function getIsNoUser(): bool {
		return true;
	}

	public function jsonSerialize(): array {
		return $this->getSimpleUserArray();
	}

	public function getSafeDisplayName(): string {
		return $this->getDisplayName();
	}

	public function getSafeEmailAddress(): string {
		return $this->getEmailAddress();
	}
}
