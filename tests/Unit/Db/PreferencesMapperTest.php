<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\Server;

class PreferencesMapperTest extends UnitTestCase {
	private PreferencesMapper $preferencesMapper;
	/** @var Preferences[] $preferences */
	private array $preferences = [];

	protected function setUp(): void {
		parent::setUp();
		$this->preferencesMapper = Server::get(PreferencesMapper::class);

		for ($count = 0; $count < 2; $count++) {
			/** @var Preferences $prefs */
			$prefs = $this->fm->instance('OCA\Polls\Db\Preferences');
			array_push($this->preferences, $this->preferencesMapper->insert($prefs));
		}
	}

	public function testFind(): void {
		foreach ($this->preferences as $prefs) {
			$this->assertInstanceOf(
				Preferences::class,
				$this->preferencesMapper->find($prefs->getUserId())
			);
		}
	}

	public function testUpdate(): void {
		foreach ($this->preferences as &$prefs) {
			$prefs->setPreferences('{"updated":true}');
			$this->assertInstanceOf(Preferences::class, $this->preferencesMapper->update($prefs));
		}
		unset($prefs);
	}

	public function testDeleteByUserId(): void {
		foreach ($this->preferences as $prefs) {
			$this->expectNotToPerformAssertions();
			$this->preferencesMapper->deleteByUserId($prefs->getUserId());
		}
		$this->preferences = [];
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->preferences as $prefs) {
			$this->preferencesMapper->deleteByUserId($prefs->getUserId());
		}
	}
}
