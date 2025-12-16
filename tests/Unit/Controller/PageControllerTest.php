<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Controller;

use OCA\Polls\Controller\PageController;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\AppFramework\Http\TemplateResponse;

class PageControllerTest extends UnitTestCase {
	private PageController $controller;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		$request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$notificationService = $this->getMockBuilder('OCA\Polls\Service\NotificationService')
			->disableOriginalConstructor()
			->getMock();
		$eventDispatcher = $this->getMockBuilder('OCP\EventDispatcher\IEventDispatcher')
			->disableOriginalConstructor()
			->getMock();
		$appManager = $this->getMockBuilder('OCP\App\IAppManager')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new PageController(
			'polls',
			$request,
			$notificationService,
			$eventDispatcher,
			$appManager,
		);
	}

	/**
	 * Basic controller index route test.
	 */
	public function testIndex() {
		$result = $this->controller->index();

		$this->assertEquals('main', $result->getTemplateName());
		$this->assertInstanceOf(TemplateResponse::class, $result);
	}
}
