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
use OCP\ILogger;
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
	private $optionMapper;
	private $options;
	private $option;
	private $groupManager;
	private $pollMapper;
	private $logger;
	private $logService;
	private $acl;

	/**
	 * OptionController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param OptionMapper $optionMapper
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param LogService $logService
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		OptionMapper $optionMapper,
		Option $option,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		ILogger $logger,
		LogService $logService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->optionMapper = $optionMapper;
		$this->option = $option;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->logger = $logger;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * Set properties from option array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return array Array of Option objects
	 */
	private function set($option) {

		$this->option->setPollId($option['pollId']);
		$this->option->setPollOptionText(trim(htmlspecialchars($option['pollOptionText'])));
		$this->option->setTimestamp($option['timestamp']);

		if ($option['timestamp']) {
			$this->option->setOrder($option['timestamp']);
		} else {
			$this->option->setOrder($option['order']);
		}

		if ($option['confirmed']) {
			// do not update confirmation date, if option is already confirmed
			if (!$this->option->getConfirmed()) {
				$this->option->setConfirmed(time());
			}
		} else {
			$this->option->setConfirmed(0);
		}
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return array Array of Option objects
	 */
	public function get($pollId) {

		try {

			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}

			$this->options = $this->optionMapper->findByPoll($pollId);

			return new DataResponse($this->options, Http::STATUS_OK);
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
			// return $this->get($this->acl->getPollId());
			$this->options = $this->optionMapper->findByPoll($this->acl->getPollId());
			return new DataResponse($this->options, Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function add($option) {

		if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->option = new Option();
			$this->set($option);
			$this->optionMapper->insert($this->option);
			$this->logService->setLog($option['pollId'], 'addOption');
			return new DataResponse($this->option, Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Update poll option
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function update($option) {

		if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->option = $this->optionMapper->find($option['id']);
			$this->set($option);
			$this->optionMapper->update($this->option);
			$this->logService->setLog($option['pollId'], 'updateOption');
			return new DataResponse($this->option, Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Remove a single option
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function remove($option) {
		try {

			if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

			$this->optionMapper->remove($option['id']);
			$this->logService->setLog($option['pollId'], 'deleteOption');

			return new DataResponse(array(
				'action' => 'deleted',
				'optionId' => $option['id']
			), Http::STATUS_OK);

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * Set order by order of the given array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $options
	 * @return DataResponse
	 */
	public function reorder($pollId, $options) {
		$i = 0;

		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		foreach ($options as $option) {
			$this->option = $this->optionMapper->find($option['id']);
			if ($pollId === intval($this->option->getPollId())) {
				$this->option->setOrder(++$i);
				$this->optionMapper->update($this->option);
			}
		}

		return $this->get($pollId);

	}
}
