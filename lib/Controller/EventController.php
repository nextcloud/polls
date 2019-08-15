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

use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;



class EventController extends Controller {

	private $mapper;
	private $userId;
	private $logger;


	private $groupManager;

	public function __construct(
		string $AppName,
		IRequest $request,
		ILogger $logger,
		EventMapper $mapper,
		$UserId,
		IGroupManager $groupManager
	) {
		parent::__construct($AppName, $request);
		$this->mapper = $mapper;
		$this->userId = $UserId;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
	}

	 /**
 	 * Read an entire poll based on poll id
 	 * @NoAdminRequired
 	 * @NoCSRFRequired
 	 * @param Integer $pollId
 	 * @return Array
 	 */
 	public function get($pollId) {

 		$data = array();

 		try {
 			$data = $this->mapper->find($pollId)->read();
 		} catch (DoesNotExistException $e) {
			$this->logger->info('Poll ' . $pollId . ' not found!', ['app' => 'polls']);
			$this->logger->debug($e, ['app' => 'polls']);
			$data['poll'] = ['result' => 'notFound'];
 		} finally {
 			return $data;
 		}

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
	public function write($event, $mode) {
		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$adminAccess = $this->groupManager->isAdmin($this->userId);
		}


		$NewEvent = new Event();

		// Set the configuration options entered by the user
		$NewEvent->setTitle($event['title']);
		$NewEvent->setDescription($event['description']);

		$NewEvent->setType($event['type']);
		$NewEvent->setIsAnonymous($event['isAnonymous']);
		$NewEvent->setFullAnonymous($event['fullAnonymous']);
		$NewEvent->setAllowMaybe($event['allowMaybe']);

		if ($event['access'] === 'select') {
			$shareAccess = '';
			foreach ($shares as $shareElement) {
				if ($shareElement['type'] === 'user') {
					$shareAccess = $shareAccess . 'user_' . $shareElement['id'] . ';';
				} elseif ($shareElement['type'] === 'group') {
					$shareAccess = $shareAccess . 'group_' . $shareElement['id'] . ';';
				}
			}
			$NewEvent->setAccess(rtrim($shareAccess, ';'));
		} else {
			$NewEvent->setAccess($event['access']);
		}

		if ($event['expiration']) {
			$NewEvent->setExpire(date('Y-m-d H:i:s', strtotime($event['expirationDate'])));
		} else {
			$NewEvent->setExpire(null);
		}

		if ($event['type'] === 'datePoll') {
			$NewEvent->setType(0);
		} elseif ($event['type'] === 'textPoll') {
			$NewEvent->setType(1);
		}

		if ($mode === 'edit') {
			// Edit existing poll
			$oldEvent = $this->mapper->find($event['id']);

			// Check if current user is allowed to edit existing poll
			if ($oldEvent->getOwner() !== $this->userId && !$adminAccess) {
				// If current user is not owner of existing poll deny access
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

			// else take owner, hash and id of existing poll
			$NewEvent->setOwner($oldEvent->getOwner());
			$NewEvent->setHash($oldEvent->getHash());
			$NewEvent->setId($oldEvent->getId());
			try {
				$this->mapper->update($NewEvent);
				$this->logger->debug('updating', ['app' => 'polls']);

			} catch (Exeption $e) {
				$this->logger->alert('Poll ' . $pollId . ' not found!', ['app' => 'polls']);
			}

		} elseif ($mode === 'create') {
			// Create new poll
			// Define current user as owner, set new creation date and create a new hash
			$NewEvent->setOwner($this->userId);
			$NewEvent->setCreated(date('Y-m-d H:i:s'));
			$NewEvent->setHash(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
			$NewEvent = $this->mapper->insert($NewEvent);
		}
		return $this->get($NewEvent->getId());
	}
}
