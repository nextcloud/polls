<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Dashboard;

use OCA\Polls\AppInfo\Application;
use OCA\Polls\Helper\AssetLoader;
use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

class PollWidget implements IWidget {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getId(): string {
		return Application::APP_ID;
	}

	public function getTitle(): string {
		return $this->l10n->t('Polls');
	}

	public function getOrder(): int {
		return 50;
	}

	public function getIconClass(): string {
		return 'icon-polls-dark';
	}

	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.page.indexindex');
	}

	public function load(): void {
		Util::addScript(Application::APP_ID, AssetLoader::getScript('dashboard'));
	}
}
