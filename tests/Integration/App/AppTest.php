<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Integration\App;

use OCP\AppFramework\App;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase {

	private $container;

	protected function setUp(): void {
		parent::setUp();
		$app = new App('polls');
		$this->container = $app->getContainer();
	}

	public function testAppInstalled() {
		$appManager = $this->container->query('OCP\App\IAppManager');
		$this->assertTrue($appManager->isInstalled('polls'));
	}
}
