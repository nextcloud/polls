<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Kai Schröer <kai@schroeer.co>
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
use OCP\AppFramework\Http\TemplateResponse;
use PHPUnit_Framework_TestCase;

class PageControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;
	private $userId = 'john';

	public function setUp() {
		$request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$avatarManager = $this->getMockBuilder('OCP\IAvatarManager')
			->disableOriginalConstructor()
			->getMock();
		$logger = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$l10n = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$urlGenerator = $this->getMockBuilder('OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();
		$commentMapper = $this->getMockBuilder('OCA\Polls\Db\CommentMapper')
			->disableOriginalConstructor()
			->getMock();
		$dateMapper = $this->getMockBuilder('OCA\Polls\Db\DateMapper')
			->disableOriginalConstructor()
			->getMock();
		$eventMapper = $this->getMockBuilder('OCA\Polls\Db\EventMapper')
			->disableOriginalConstructor()
			->getMock();
		$notificationMapper = $this->getMockBuilder('OCA\Polls\Db\NotificationMapper')
			->disableOriginalConstructor()
			->getMock();
		$participationMapper = $this->getMockBuilder('OCA\Polls\Db\ParticipationMapper')
			->disableOriginalConstructor()
			->getMock();
		$participationTextMapper = $this->getMockBuilder('OCA\Polls\Db\ParticipationTextMapper')
			->disableOriginalConstructor()
			->getMock();
		$textMapper = $this->getMockBuilder('OCA\Polls\Db\TextMapper')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new PageController(
			'polls',
			$request,
			$userManager,
			$groupManager,
			$avatarManager,
			$logger,
			$l10n,
			$urlGenerator,
			$this->userId,
			$commentMapper,
			$dateMapper,
			$eventMapper,
			$notificationMapper,
			$participationMapper,
			$participationTextMapper,
			$textMapper
		);
	}

	public function testIndex() {
		$result = $this->controller->index();

		$this->assertEquals('main.tmpl', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}
}
