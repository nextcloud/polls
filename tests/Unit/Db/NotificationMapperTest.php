<?php
/**
 * @copyright Copyright (c) 2017 Kai Schröer <kai@schroeer.co>
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

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\NotificationMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;

class NotificationMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var NotificationMapper */
	private $notificationMapper;
	/** @var EventMapper */
	private $eventMapper;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->notificationMapper = new NotificationMapper($this->con);
		$this->eventMapper = new EventMapper($this->con);
	}

	public function testCreate() {

	}

	/**
	 * @depends testCreate
	 */
	public function testUpdate() {

	}

	/**
	 * @depends testDelete
	 */
	public function testDelete() {

	}
}
