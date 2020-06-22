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

namespace OCA\Polls\Service;

use Exception;

use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class OptionService  {

	private $optionMapper;
	private $option;
	private $logService;
	private $acl;

	/**
	 * OptionController constructor.
	 * @param OptionMapper $optionMapper
	 * @param Option $option
	 * @param LogService $logService
	 * @param Acl $acl
	 */

	public function __construct(
		OptionMapper $optionMapper,
		Option $option,
		LogService $logService,
		Acl $acl
	) {
		$this->optionMapper = $optionMapper;
		$this->option = $option;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * Set properties from option array
	 * @NoAdminRequired
	 * @param Array $option
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
	 * @param integer $pollId
	 * @param string $token
	 * @return array Array of Option objects
	 */
	public function list($pollId = 0, $token = '') {

		if (!$this->acl->setPollIdOrToken($pollId, $token)->getAllowView()) {
			throw new NotAuthorizedException;
		}

		return $this->optionMapper->findByPoll($pollId);

	}


	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @param Array $option
	 * @return Option
	 */
	public function add($option) {

		if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->option = new Option();
		$this->set($option);
		$this->optionMapper->insert($this->option);
		$this->logService->setLog($option['pollId'], 'addOption');

		return $this->option;
	}

	/**
	 * Remove a single option
	 * @NoAdminRequired
	 * @param Option $option
	 * @return array Array of Option objects
	 */
	public function delete($optionId) {
		$this->option = $this->optionMapper->find($optionId);

		if (!$this->acl->setPollId($this->option->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->optionMapper->delete($this->option);

		return $this->option;

	}

	/**
	 * Update poll option
	 * @NoAdminRequired
	 * @param array $option
	 * @return Option
	 */
	public function update($option) {
		if (!$this->acl->setPollId($option['pollId'])->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		try {
			$this->option = $this->optionMapper->find($option['id']);
			$this->set($option);
			$this->optionMapper->update($this->option);
			$this->logService->setLog($option['pollId'], 'updateOption');

			return $this->option;
		} catch (Exception $e) {
			return new DoesNotExistException($e);
		}

	}

	/**
	 * Set order by order of the given array
	 * @NoAdminRequired
	 * @param array $options
	 * @return array Array of Option objects
	 */
	public function reorder($pollId, $options) {

		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$i = 0;
		foreach ($options as $option) {
			$this->option = $this->optionMapper->find($option['id']);
			if ($pollId === intval($this->option->getPollId())) {
				$this->option->setOrder(++$i);
				$this->optionMapper->update($this->option);
			}
		}

		return $this->optionMapper->findByPoll($pollId);

	}

	/**
	 * Set order by order of the given array
	 * @NoAdminRequired
	 * @param integer $fromPollId
	 * @param integer $toPollId
	 * @return array Array of Option objects
	 */
	public function clone($fromPollId, $toPollId) {

		if (!$this->acl->setPollId($fromPollId)->getAllowView()) {
			throw new NotAuthorizedException;
		}

		foreach ($this->optionMapper->findByPoll($fromPollId) as $option) {
			$option->setPollId($toPollId);
			$this->optionMapper->insert($option);
		}

		return $this->optionMapper->findByPoll($toPollId);

	}
}
