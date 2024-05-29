<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit;

use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase {
	protected FactoryMuffin $fm;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->fm = new FactoryMuffin();
		$this->fm->loadFactories(__DIR__ . '/Factories');
	}
}
