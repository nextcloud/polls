<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Controller;

use Exeption;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\UniqueConstraintViolationException;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;

class OptionController extends Controller {

	private $mapper;
	private $userId;

	private $groupManager;
	private $eventMapper;

	public function __construct(
		string $AppName,
		IRequest $request,
		OptionMapper $mapper,
		$UserId,
		IGroupManager $groupManager,
		EventMapper $eventMapper
	) {
		parent::__construct($AppName, $request);
		$this->mapper = $mapper;
		$this->userId = $UserId;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
	}

	/**
	 * Read all options of a poll based on the poll id
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return Array
	 */
	public function get($pollId) {
		$returnList = array();
		$options = $this->mapper->findByPoll($pollId);

		foreach ($options as $Option) {
			$returnList[] = $Option->read();
		}

		return $returnList;
	}

	/**
	 * Write poll (create/update)
	 * @NoAdminRequired
	 * @param Array $event
	 * @param Array $options
	 * @param Array  $shares
	 * @param String $mode
	 * @return DataResponse
	 */
	public function add($pollId, $option) {

		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$AdminAccess = $this->groupManager->isAdmin($this->userId);
		}

		$NewOption = new Option();

		$NewOption->setPollId($pollId);
		$NewOption->setpollOptionText(trim(htmlspecialchars($option['text'])));
		$NewOption->setTimestamp($option['timestamp']);

		// TODO: catch triying to add existing options
		// UniqueConstraintViolationException is not chatchable
		$this->mapper->insert($NewOption);

		return $this->get($pollId);

	}

	/**
	 * Write poll (create/update)
	 * @NoAdminRequired
	 * @param Array $event
	 * @param Array $options
	 * @param Array  $shares
	 * @param String $mode
	 * @return DataResponse
	 */
	public function remove($optionId) {
		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$AdminAccess = $this->groupManager->isAdmin($this->userId);
		}

		$this->mapper->remove($optionId);

		return $this->get($pollId);

	}

	/**
	 * Write poll (create/update)
	 * @NoAdminRequired
	 * @param Array $event
	 * @param Array $options
	 * @param Array  $shares
	 * @param String $mode
	 * @return DataResponse
	 */
	public function write($pollId, $options) {
		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$AdminAccess = $this->groupManager->isAdmin($this->userId);
		}

		$this->mapper->deleteByPoll($pollId);

		foreach ($options as $option) {
			$NewOption = new Option();

			$NewOption->setPollId($pollId);
			$NewOption->setpollOptionText(trim(htmlspecialchars($option['text'])));
			$NewOption->setTimestamp($option['timestamp']);

			$this->mapper->insert($NewOption);
		}

		return $this->get($pollId);

	}
}
