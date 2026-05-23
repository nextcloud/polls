<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Model\User;

use OCA\Polls\Model\UserBase;

class Email extends UserBase {
	public const TYPE = 'email';

	public function __construct(
		string $id,
		string $displayName = '',
		string $emailAddress = '',
		string $languageCode = '',
	) {
		parent::__construct($id, self::TYPE, languageCode: $languageCode);
		$this->richObjectType = 'email';
		$this->description = $emailAddress !== '' ? $emailAddress : $this->l10n->t('External Email');
		$this->emailAddress = $emailAddress !== '' ? $emailAddress : $id;
		$this->displayName = $displayName !== '' ? $displayName : $this->displayName;
	}

	public function getDisplayName(): string {
		return $this->displayName ? $this->displayName : $this->id;
	}

	/** @psalm-suppress PossiblyUnusedMethod */
	public function jsonSerialize(): array {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getRichUserArray();
		}
		return $this->getSimpleUserArray();
	}

	public function getDescription(): string {
		if ($this->getDisplayName()) {
			return $this->getEmailAndDisplayName();
		}
		return $this->getEmailAddress();
	}
}
