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

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class OptionController extends Controller {

	private $userId;
	private $mapper;

	private $groupManager;
	private $pollMapper;
	private $logService;
	private $acl;

	/**
	 * OptionController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param IRequest $request
	 * @param OptionMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param LogService $logService
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		OptionMapper $mapper,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		LogService $logService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->logService = $logService;
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

			return new DataResponse($this->mapper->findByPoll($pollId), Http::STATUS_OK);

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
			return $this->get($this->acl->getPollId());

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function add($option) {

		try {
			$this->acl->setPollId($option['pollId']);

			if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}
			$NewOption = new Option();

			$NewOption->setPollId($option['pollId']);
			$NewOption->setPollOptionText(trim(htmlspecialchars($option['pollOptionText'])));
			$NewOption->setTimestamp($option['timestamp']);
			$NewOption->setOrder($option['order']);

			$this->mapper->insert($NewOption);
			$this->logService->setLog($option['pollId'], 'addOption');
			return new DataResponse($NewOption, Http::STATUS_OK);

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

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

			if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

			$updateOption->setPollOptionText(trim(htmlspecialchars($option['pollOptionText'])));
			$updateOption->setTimestamp($option['timestamp']);
			$updateOption->setOrder($option['order']);

			$this->mapper->update($updateOption);
			$this->logService->setLog($option['pollId'], 'updateOption');

			return new DataResponse($updateOption, Http::STATUS_OK);

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
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

			if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

			$this->mapper->remove($option['id']);
			$this->logService->setLog($option['pollId'], 'deleteOption');

			return new DataResponse(array(
				'action' => 'deleted',
				'optionId' => $option['id']
			), Http::STATUS_OK);

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

}
