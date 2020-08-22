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

use DateTime;
use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\BadRequestException;
use OCA\Polls\Exceptions\DuplicateEntryException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class OptionService {

	/** @var OptionMapper */
	private $optionMapper;

	/** @var Option */
	private $option;

	/** @var PollMapper */
	private $pollMapper;

	/** @var Poll */
	private $poll;

	/** @var LogService */
	private $logService;

	/** @var Acl */
	private $acl;

	/**
	 * OptionService constructor.
	 * @param OptionMapper $optionMapper
	 * @param Option $option
	 * @param PollMapper $pollMapper
	 * @param Poll $poll
	 * @param LogService $logService
	 * @param Acl $acl
	 */

	public function __construct(
		OptionMapper $optionMapper,
		Option $option,
		PollMapper $pollMapper,
   	 	Poll $poll,
		LogService $logService,
		Acl $acl
	) {
		$this->optionMapper = $optionMapper;
		$this->option = $option;
		$this->pollMapper = $pollMapper;
		$this->poll = $poll;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $token
	 * @return array Array of Option objects
	 * @throws NotAuthorizedException
	 */
	public function list($pollId = 0, $token = '') {
		$acl = $this->acl->set($pollId, $token);

		if (!$acl->getAllowView()) {
			throw new NotAuthorizedException;
		}

		try {
			return $this->optionMapper->findByPoll($acl->getPollId());
		} catch (DoesNotExistException $e) {
			return [];
		}
	}


	/**
	 * Add a new option
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param int $timestamp
	 * @param string $pollOptionText
	 * @return Option
	 * @throws NotAuthorizedException
	 */
	public function add($pollId, $timestamp = 0, $pollOptionText = '') {

		$this->poll = $this->pollMapper->find($pollId);
		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->option = new Option();
		$this->option->setPollId($pollId);
		$this->setOption($timestamp, $pollOptionText, 0);

		try {
			return $this->optionMapper->insert($this->option);
		} catch (UniqueConstraintViolationException $e) {
			throw new DuplicateEntryException('This option already exists');
		}

	}

	/**
	 * Update option
	 * @NoAdminRequired
	 * @param int $optionId
	 * @param int $timestamp
	 * @param string $pollOptionText
	 * @param int $order
	 * @return Option
	 * @throws NotAuthorizedException
	 */
	public function update($optionId, $timestamp = 0, $pollOptionText = '', $order = 0) {

		$this->option = $this->optionMapper->find($optionId);
		$this->poll = $this->pollMapper->find($this->option->getPollId());

		if (!$this->acl->set($this->option->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->setOption($timestamp, $pollOptionText, $order);

		return $this->optionMapper->update($this->option);
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 * @param int $optionId
	 * @return Option deleted Option
	 * @throws NotAuthorizedException
	 */
	public function delete($optionId) {
		$this->option = $this->optionMapper->find($optionId);

		if (!$this->acl->set($this->option->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->optionMapper->delete($this->option);

		return $this->option;
	}

	/**
	 * Switch optoin confirmation
	 * @NoAdminRequired
	 * @param int $optionId
	 * @return Option confirmed Option
	 * @throws NotAuthorizedException
	 */
	public function confirm($optionId) {
		$this->option = $this->optionMapper->find($optionId);

		if (!$this->acl->set($this->option->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		if ($this->option->getConfirmed()) {
			$this->option->setConfirmed(0);
		} else {
			$this->option->setConfirmed(time());
		}

		return $this->optionMapper->update($this->option);
	}

	/**
	 * Make a sequence of date poll options
	 * @NoAdminRequired
	 * @param int $optionId
	 * @param int $step
	 * @param string $unit
	 * @param int $amount
	 * @return array Array of Option objects
	 * @throws NotAuthorizedException
	 */
	public function sequence($optionId, $step, $unit, $amount) {

		$baseDate = new DateTime;
		$origin = $this->optionMapper->find($optionId);

		if (!$this->acl->set($origin->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		if ($step === 0) {
			return $this->optionMapper->findByPoll($origin->getPollId());
		}

		$baseDate->setTimestamp($origin->getTimestamp());

		for ($i=0; $i < $amount; $i++) {

			$this->option = new Option();
			$this->option->setPollId($origin->getPollId());
			$this->option->setConfirmed(0);
			$this->option->setTimestamp($baseDate->modify($step . ' ' . $unit)->getTimestamp());
			$this->option->setPollOptionText($baseDate->format('c'));
			$this->option->setOrder($baseDate->getTimestamp());
			try {
				$this->optionMapper->insert($this->option);
			} catch (UniqueConstraintViolationException $e) {
				\OC::$server->getLogger()->warning('skip adding ' . $baseDate->format('c') . 'for pollId' . $origin->getPollId() . '. Option alredy exists.');
			}
		}
		return $this->optionMapper->findByPoll($origin->getPollId());
	}

	/**
	 * Copy options from $fromPoll to $toPoll
	 * @NoAdminRequired
	 * @param int $fromPollId
	 * @param int $toPollId
	 * @return array Array of Option objects
	 * @throws NotAuthorizedException
	 */
	public function clone($fromPollId, $toPollId) {

		if (!$this->acl->set($fromPollId)->getAllowView()) {
			throw new NotAuthorizedException;
		}

		foreach ($this->optionMapper->findByPoll($fromPollId) as $origin) {
			$option = new Option();
			$option->setPollId($toPollId);
			$option->setConfirmed(0);
			$option->setPollOptionText($origin->getPollOptionText());
			$option->setTimestamp($origin->getTimestamp());
			$option->setOrder($origin->getOrder());
			$this->optionMapper->insert($option);
		}

		return $this->optionMapper->findByPoll($toPollId);
	}

	/**
	 * Reorder options with the order specified by $options
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param array $options - Array of options
	 * @return array Array of Option objects
	 * @throws NotAuthorizedException
	 * @throws BadRequestException
	 */
	public function reorder($pollId, $options) {

		$this->poll = $this->pollMapper->find($pollId);

		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		if ($this->poll->getType() === 'datePoll') {
			throw new BadRequestException("Not allowed in date polls");
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
	 * Change order for $optionId and reorder the options
	 * @NoAdminRequired
	 * @param int $optionId
	 * @param int $newOrder
	 * @return array Array of Option objects
	 * @throws NotAuthorizedException
	 * @throws BadRequestException
	 */
	public function setOrder($optionId, $newOrder) {

		$this->option = $this->optionMapper->find($optionId);
		$pollId = $this->option->getPollId();
		$this->poll = $this->pollMapper->find($pollId);

		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		if ($this->poll->getType() === 'datePoll') {
			throw new BadRequestException("Not allowed in date polls");
		}

		if ($newOrder < 1) {
			$newOrder = 1;
		} elseif ($newOrder > $this->getHighestOrder($pollId)) {
			$newOrder = $this->getHighestOrder($pollId);
		}

		$oldOrder = $this->option->getOrder();

		foreach ($this->optionMapper->findByPoll($pollId) as $option) {
			$currentOrder = $option->getOrder();
			if ($currentOrder > $oldOrder && $currentOrder <= $newOrder) {

				$option->setOrder($currentOrder - 1);
				$this->optionMapper->update($option);

			} elseif (
					   ($currentOrder < $oldOrder && $currentOrder >= $newOrder)
					|| ($currentOrder < $oldOrder && $currentOrder = $newOrder)
				) {

				$option->setOrder($currentOrder + 1);
				$this->optionMapper->update($option);

			} elseif ($currentOrder === $oldOrder) {

				$option->setOrder($newOrder);
				$this->optionMapper->update($option);

			} else {
				continue;
			}
		}

		return $this->optionMapper->findByPoll($this->option->getPollId());
	}

	/**
	 * Set option entities validated
	 * @NoAdminRequired
	 * @param int $timestamp
	 * @param string $pollOptionText
	 * @param int $order
	 * @throws BadRequestException
	 */
	private function setOption($timestamp = 0, $pollOptionText = '', $order = 0) {
		if ($this->poll->getType() === 'datePoll') {
			if ($timestamp) {
				$this->option->setTimestamp($timestamp);
				$this->option->setOrder($timestamp);
				$this->option->setPollOptionText(date('c', $timestamp));
			} else {
				throw new BadRequestException("Date poll must have a timestamp");
			}
		} elseif ($this->poll->getType() === 'textPoll') {
			if ($pollOptionText) {
				$this->option->setPollOptionText($pollOptionText);
			} else {
				throw new BadRequestException("Text poll must have a pollOptionText");
			}

			if (!$order && !$this->option->getOrder()) {
				$order = $this->getHighestOrder($this->option->getPollId()) + 1;
				$this->option->setOrder($order);
			}
		}
	}

	/**
	 * Get the highest order number in $pollId
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return int Highest order number
	 */
	private function getHighestOrder($pollId) {
		$order = 0;
		foreach ($this->optionMapper->findByPoll($pollId) as $option) {
			if ($option->getOrder() > $order) {
				$order = $option->getOrder();
			}
		}
		return $order;
	}
}
