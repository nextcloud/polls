<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Model\User;

use OCA\Polls\Model\UserBase;

class GenericUser extends UserBase {
	public const TYPE = 'external';
	public const ICON_DEFAULT = 'icon-share';
	public const ICON_PUBLIC = 'icon-public';

	public function __construct(
		string $id,
		string $type = self::TYPE,
		string $displayName = '',
		string $emailAddress = '',
		string $languageCode = '',
		string $localeCode = '',
		string $timeZoneName = ''
	) {
		parent::__construct($id, $type, $displayName, $emailAddress, $languageCode, $localeCode, $timeZoneName);

		$this->icon = self::ICON_DEFAULT;
		$this->description = $this->l10n->t('External participant');
		$this->richObjectType = 'guest';

		if ($type === UserBase::TYPE_PUBLIC) {
			$this->icon = self::ICON_PUBLIC;
			// $this->description = $this->l10n->t('Public link');
			$this->description = '';
		}
	}
}
