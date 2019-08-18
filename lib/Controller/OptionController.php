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

	private $userId;
	private $mapper;

	private $groupManager;
	private $eventMapper;

	/**
	 * OptionController constructor.
	 * @param string $AppName
	 * @param $UserId
	 * @param IRequest $request
	 * @param OptionMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param EventMapper $eventMapper
	 */
	public function __construct(
		string $AppName,
		$UserId,
		IRequest $request,
		OptionMapper $mapper,
		IGroupManager $groupManager,
		EventMapper $eventMapper
	) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
	}


	private function getTimestampTemp() {
		if ($this->getTimestamp() > 0) {
			return $this->getTimestamp();
		} else if (strtotime($this->getPollOptionText())) {
			return strtotime($this->getPollOptionText());
		} else {
			return 0;
		}
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return Array Array of Option objects
	 */
	public function list($pollId) {
		$returnList = array();
		$options = $this->mapper->findByPoll($pollId);

		foreach ($options as &$Option) {
			$Option = (object) [
				'id' => $Option->getId(),
				'pollId' => $Option->getPollId(),
				'text' => htmlspecialchars_decode($Option->getPollOptionText()),
				'timestamp' => $Option->getTimestamp()
			];
		}

		return new DataResponse($options, Http::STATUS_OK);
	}

	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @param Integer $pollId
	 * @param Array $option
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
		$NewOption->setPollOptionText(trim(htmlspecialchars($option['text'])));
		$NewOption->setTimestamp($option['timestamp']);

		// TODO: catch triying to add existing options
		// UniqueConstraintViolationException is not chatchable
		try {
			$this->mapper->insert($NewOption);
		} catch (Exeption $e){
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		// Lazy: return all options of this poll
		return new DataResponse($this->get($pollId), Http::STATUS_OK);
		// TODO: Return added option
		return new DataResponse($NewOption, Http::STATUS_OK);

	}

	/**
	 * Remove a single option
	 * @NoAdminRequired
	 * @param Array $optionId
	 * @return DataResponse
	 */
	public function remove($optionId) {
		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$AdminAccess = $this->groupManager->isAdmin($this->userId);
		}

		try {
			$this->mapper->remove($optionId);
		} catch (Exeption $e){
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse(array(
			'action' => 'deleted',
			'optionId' => $optionId
		), Http::STATUS_OK);

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

		return $this->list($pollId);

	}
}
