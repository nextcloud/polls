<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Settings;

use OCA\Polls\AppConstants;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * @psalm-suppress UnusedClass
 */
class PersonalSettings implements ISettings {
	public function __construct(
		private IAppManager $appManager,
		private string $scriptPrefix = '',
	) {
		$this->scriptPrefix = 'polls-' . $this->appManager->getAppVersion(AppConstants::APP_ID) . '-';

	}

	public function getForm(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, $this->scriptPrefix . 'userSettings');
		return new TemplateResponse(AppConstants::APP_ID, 'main', []);
	}

	public function getSection(): string {
		return AppConstants::APP_ID;
	}

	public function getPriority(): int {
		return 50;
	}
}
