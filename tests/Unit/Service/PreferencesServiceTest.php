<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Preferences;
use OCA\Polls\Service\PreferencesService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\ISession;
use OCP\Server;

class PreferencesServiceTest extends UnitTestCase {
	private PreferencesService $preferencesService;
	private ISession $session;

	protected function setUp(): void {
		parent::setUp();
		$this->session = Server::get(ISession::class);
		$this->session->set('ncPollsUserId', 'admin');

		// PreferencesService is a singleton; call load() explicitly so it picks
		// up the session user set above instead of whatever was set before.
		$this->preferencesService = Server::get(PreferencesService::class);
		$this->preferencesService->load();
	}

	// --- get ---

	public function testGetReturnsPreferences(): void {
		$prefs = $this->preferencesService->get();
		$this->assertInstanceOf(Preferences::class, $prefs);
		$this->assertSame('admin', $prefs->getUserId());
	}

	public function testGetReturnsDefaultSettings(): void {
		$settings = $this->preferencesService->get()->getUserSettings();
		$this->assertArrayHasKey('useAlternativeStyling', $settings);
		$this->assertArrayHasKey('defaultViewTextPoll', $settings);
	}

	// --- write ---

	public function testWriteUpdatesSettings(): void {
		$newSettings = array_merge(
			Preferences::DEFAULT_SETTINGS,
			['useAlternativeStyling' => true],
		);
		$prefs = $this->preferencesService->write($newSettings);
		$this->assertInstanceOf(Preferences::class, $prefs);
		$this->assertTrue($prefs->getUserSettings()['useAlternativeStyling']);
	}

	public function testWriteDoesNotAffectOtherKeys(): void {
		$newSettings = array_merge(
			Preferences::DEFAULT_SETTINGS,
			['relevantOffset' => 60],
		);
		$prefs = $this->preferencesService->write($newSettings);
		$this->assertSame(60, $prefs->getUserSettings()['relevantOffset']);
		// Other defaults remain intact
		$this->assertArrayHasKey('defaultViewDatePoll', $prefs->getUserSettings());
	}

	public function testWriteUpdatesTimestamp(): void {
		$before = time();
		$prefs = $this->preferencesService->write(Preferences::DEFAULT_SETTINGS);
		$this->assertGreaterThanOrEqual($before, $prefs->getTimestamp());
	}
}
