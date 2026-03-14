<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Settings;

use OCA\Polls\AppInfo\Application;
use OCA\Polls\Helper\AssetLoader;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * @psalm-suppress UnusedClass
 */
class AdminSettings implements ISettings {
	public function __construct() {
	}

	public function getForm(): TemplateResponse {
		Util::addScript(Application::APP_ID, AssetLoader::getScript('adminSettings'));
		return new TemplateResponse(Application::APP_ID, 'main', []);
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority():int {
		return 50;
	}
}
