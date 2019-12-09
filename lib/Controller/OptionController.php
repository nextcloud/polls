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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;

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
use OCA\Polls\Model\Acl;

class OptionController extends Controller {

	private $userId;
	private $mapper;

	private $groupManager;
	private $eventMapper;
	private $acl;

	/**
	 * OptionController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param IRequest $request
	 * @param OptionMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param EventMapper $eventMapper
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		OptionMapper $mapper,
		IGroupManager $groupManager,
		EventMapper $eventMapper,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
		$this->acl = $acl;
	}


	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return array Array of Option objects
	 */
	public function get($pollId) {

		try {

			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}

			$options = $this->mapper->findByPoll($pollId);

			foreach ($options as &$Option) {
				// Fix for empty timestamps on date polls
				// generate timestamp from pollOptionText
				if ($Option->getTimestamp() > 0) {
					$ts = $Option->getTimestamp();
				} else if (strtotime($Option->getPollOptionText())) {
					$ts = strtotime($Option->getPollOptionText());
				} else {
					$ts = 0;
				}


				$Option = (object) [
					'id' => $Option->getId(),
					'pollId' => $Option->getPollId(),
					'pollOptionText' => htmlspecialchars_decode($Option->getPollOptionText()),
					'timestamp' => $ts
				];
			}
			return new DataResponse($options, Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * getByToken
	 * Read all options of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {

		try {
			$this->acl->setToken($token);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return $this->get($this->acl->getPollId());

	}

	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function add($option) {

		try {
			$Event = $this->eventMapper->find($option['pollId']);

			if (!\OC::$server->getUserSession()->isLoggedIn()
				|| (!$this->groupManager->isAdmin($this->userId) && ($Event->getOwner() !== $this->userId))
			) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}


		$NewOption = new Option();

		$NewOption->setPollId($option['pollId']);
		$NewOption->setPollOptionText(trim(htmlspecialchars($option['pollOptionText'])));
		$NewOption->setTimestamp($option['timestamp']);

		try {
			$this->mapper->insert($NewOption);
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($NewOption, Http::STATUS_OK);

	}

	/**
	 * Update poll option
	 * @NoAdminRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function update($option) {

		try {
			$updateOption = $this->mapper->find($option['id']);
			$Event = $this->eventMapper->find($updateOption->getPollId());

			if (!\OC::$server->getUserSession()->isLoggedIn()
				&& (!$this->groupManager->isAdmin($this->userId) || ($Event->getOwner() === $this->userId))
			) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

			$updateOption->setPollOptionText(trim(htmlspecialchars($option['pollOptionText'])));
			$updateOption->setTimestamp($option['timestamp']);
			$this->mapper->update($updateOption);

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($updateOption, Http::STATUS_OK);
	}

	/**
	 * Remove a single option
	 * @NoAdminRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function remove($option) {
		// throw new \Exception( gettype($option) );
		try {
			$Event = $this->eventMapper->find($option['pollId']);

			if (!\OC::$server->getUserSession()->isLoggedIn()
				|| (!$this->groupManager->isAdmin($this->userId) && ($Event->getOwner() !== $this->userId))
			) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		try {
			$this->mapper->remove($option['id']);
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse(array(
			'action' => 'deleted',
			'optionId' => $option['id']
		), Http::STATUS_OK);

	}

}
