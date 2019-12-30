<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Tests\Unit\Controller;

use OCA\Polls\Controller\PageController;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\AppFramework\Http\TemplateResponse;

class PageControllerTest extends UnitTestCase {

	/** @var PageController */
	private $controller;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		$request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$urlGenerator = $this->getMockBuilder('OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new PageController(
			'polls',
			$request,
			$urlGenerator
		);
	}

	/**
	 * Basic controller index route test.
	 */
	public function testIndex() {
		$result = $this->controller->index();

		$this->assertEquals('polls.tmpl', $result->getTemplateName());
		$this->assertInstanceOf(TemplateResponse::class, $result);
	}
}
