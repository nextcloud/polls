<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use DateTimeZone;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Event\OptionConfirmedEvent;
use OCA\Polls\Event\OptionCreatedEvent;
use OCA\Polls\Event\OptionDeletedEvent;
use OCA\Polls\Event\OptionUnconfirmedEvent;
use OCA\Polls\Event\OptionUpdatedEvent;
use OCA\Polls\Event\PollOptionReorderedEvent;
use OCA\Polls\Exceptions\DuplicateEntryException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class OptionService {
	/** @var Option[] */
	private array $options;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private Acl $acl,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
		private Option $option,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private UserSession $userSession,
		private Poll $poll,
	) {
		$this->options = [];
	}

	public function get(int $optionId): Option {
		return $this->optionMapper->find($optionId);
	}
	
	/**
	 * Get all options of given poll
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function list(int $pollId): array {
		$this->getPoll($pollId, Poll::PERMISSION_POLL_VIEW);

		try {
			$this->options = $this->optionMapper->findByPoll($pollId, !$this->poll->getIsAllowed(Poll::PERMISSION_POLL_RESULTS_VIEW));
			$this->filterBookedUp();

		} catch (DoesNotExistException $e) {
			$this->options = [];
		}

		return array_values($this->options);
	}

	/**
	 * Add a new option to a poll
	 *
	 * @param int $pollId poll id of poll to add option to
	 * @param int $timestamp timestamp in case of date poll
	 * @param string $pollOptionText option text in case of text poll
	 * @param int $duration duration of option in case of date poll
	 * @return Option
	 */
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): Option {
		$this->getPoll($pollId, Poll::PERMISSION_OPTIONS_ADD);

		$this->option = new Option();
		$this->option->setPollId($pollId);
		$order = $this->getHighestOrder($pollId) + 1;

		$this->option->setOption($timestamp, $duration, $pollOptionText, $order);

		if (!$this->poll->getIsPollOwner()) {
			$this->option->setOwner($this->acl->getCurrentUserId());
		}

		try {
			$this->option = $this->optionMapper->insert($this->option);
		} catch (Exception $e) {
			
			// TODO: Change exception catch to actual exception
			// Currently OC\DB\Exceptions\DbalException is thrown instead of
			// UniqueConstraintViolationException
			// since the exception is from private namespace, we check the type string
			if (get_class($e) === 'OC\DB\Exceptions\DbalException' || $e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {

				$option = $this->optionMapper->findByPollAndText($pollId, $this->option->getPollOptionText(), true);
				if ($option->getDeleted()) {
					// Deleted option exist, restore deleted option
					$option->setDeleted(0);
					// return existing undeleted share
					return $this->optionMapper->update($option);
				}
				// optionalready exists
				throw new DuplicateEntryException('This option already exists');
			}
			throw $e;
		}

		$this->eventDispatcher->dispatchTyped(new OptionCreatedEvent($this->option));

		return $this->option;
	}
	/**
	 * Add a new option
	 *
	 * @return Option[]
	 */
	public function addBulk(int $pollId, string $pollOptionText = ''): array {
		$this->getPoll($pollId, Poll::PERMISSION_OPTIONS_ADD);

		$newOptions = array_unique(explode(PHP_EOL, $pollOptionText));
		foreach ($newOptions as $option) {
			if ($option) {
				try {
					$this->add($pollId, pollOptionText: $option);
				} catch (DuplicateEntryException $e) {
					continue;
				}
			}
		}
		return $this->list($pollId);
	}

	/**
	 * Update option
	 *
	 * @return Option
	 */
	public function update(int $optionId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): Option {
		$this->option = $this->optionMapper->find($optionId);
		$this->getPoll($this->option->getPollId(), Poll::PERMISSION_POLL_EDIT);

		$this->option->setOption($timestamp, $duration, $pollOptionText);

		$this->option = $this->optionMapper->update($this->option);
		$this->eventDispatcher->dispatchTyped(new OptionUpdatedEvent($this->option));

		return $this->option;
	}

	/**
	 * Delete option
	 * @param int $optionId Id of option to delete or restore
	 * @param bool $restore Set true, if option is to be restored
	 */
	public function delete(int $optionId, bool $restore = false): Option {
		$this->option = $this->optionMapper->find($optionId);

		if (!$this->acl->matchUser($this->option->getUserId())) {
			$this->pollMapper->find($this->option->getPollId())->request(Poll::PERMISSION_OPTION_DELETE);
		}

		$this->option->setDeleted($restore ? 0 : time());
		$this->optionMapper->update($this->option);
		$this->eventDispatcher->dispatchTyped(new OptionDeletedEvent($this->option));

		return $this->option;
	}

	/**
	 * Switch option confirmation
	 *
	 * @return Option
	 */
	public function confirm(int $optionId): Option {
		$this->option = $this->optionMapper->find($optionId);
		$this->getPoll($this->option->getPollId(), Poll::PERMISSION_POLL_EDIT);

		$this->option->setConfirmed($this->option->getConfirmed() ? 0 : time());
		$this->option = $this->optionMapper->update($this->option);

		if ($this->option->getConfirmed()) {
			$this->eventDispatcher->dispatchTyped(new OptionConfirmedEvent($this->option));
		} else {
			$this->eventDispatcher->dispatchTyped(new OptionUnconfirmedEvent($this->option));
		}

		return $this->option;
	}

	/**
	 * Make a sequence of date poll options
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function sequence(int $optionId, int $step, string $unit, int $amount): array {
		$this->option = $this->optionMapper->find($optionId);
		$this->getPoll($this->option->getPollId(), Poll::PERMISSION_POLL_EDIT);

		if ($this->poll->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Sequences are only available in date polls');
		}

		if ($step === 0) {
			return $this->optionMapper->findByPoll($this->option->getPollId());
		}

		$timezone = new DateTimeZone($this->userSession->getClientTimeZone());

		for ($i = 1; $i < ($amount + 1); $i++) {
			$clonedOption = new Option();
			$clonedOption->setPollId($this->option->getPollId());
			$clonedOption->setOption($this->option->getTimestamp(), $this->option->getDuration());
			$clonedOption->shiftOption($timezone, ($step * $i), $unit);

			try {
				$this->optionMapper->insert($clonedOption);
			} catch (Exception $e) {
				$this->logger->warning('Skip sequence no. {sequence} of option {optionId}. Option possibly already exists.', [
					'sequence' => $i,
					'optionId' => $this->option->getId(),
				]);
			}
		}

		$this->eventDispatcher->dispatchTyped(new OptionCreatedEvent($this->option));

		return $this->optionMapper->findByPoll($this->poll->getId());
	}

	/**
	 * Shift all date options
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function shift(int $pollId, int $step, string $unit): array {
		$this->getPoll($this->option->getPollId(), Poll::PERMISSION_POLL_EDIT);
		$timezone = new DateTimeZone($this->userSession->getClientTimeZone());

		if ($this->poll->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Shifting is only available in date polls');
		}

		$this->options = $this->optionMapper->findByPoll($pollId);

		if ($step > 0) {
			// start from last item if moving option into the future
			// avoid UniqueConstraintViolationException
			$this->options = array_reverse($this->options);
		}

		foreach ($this->options as $option) {
			$option->shiftOption($timezone, $step, $unit);
			$this->optionMapper->update($option);
		}

		return $this->optionMapper->findByPoll($pollId);
	}

	/**
	 * Copy options from $fromPoll to $toPoll
	 */
	public function clone(int $fromPollId, int $toPollId): void {
		$this->pollMapper->find($fromPollId)->request(Poll::PERMISSION_POLL_VIEW);
		$this->pollMapper->find($toPollId)->request(Poll::PERMISSION_POLL_EDIT);

		foreach ($this->optionMapper->findByPoll($fromPollId) as $origin) {
			$option = new Option();
			$option->setPollId($toPollId);
			$option->setConfirmed(0);
			$option->setOption(
				$origin->getTimestamp(),
				$origin->getDuration(),
				$origin->getPollOptionText(),
			);
			$option->setOrder($origin->getOrder());
			$option = $this->optionMapper->insert($option);
			$this->eventDispatcher->dispatchTyped(new OptionCreatedEvent($option));
		}
	}

	/**
	 * Reorder options with the order specified by $options
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function reorder(int $pollId, array $options): array {
		$this->getPoll($pollId, Poll::PERMISSION_POLL_EDIT);

		if ($this->poll->getType() === Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Not allowed in date polls');
		}

		$i = 0;
		foreach ($options as $option) {
			$this->option = $this->optionMapper->find($option['id']);
			if ($pollId === intval($this->option->getPollId())) {
				$this->option->setOrder(++$i);
				$this->optionMapper->update($this->option);
			}
		}

		$this->eventDispatcher->dispatchTyped(new OptionUpdatedEvent($this->option));

		return $this->optionMapper->findByPoll($pollId);
	}

	/**
	 * Change order for $optionId and reorder the options
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function setOrder(int $optionId, int $newOrder): array {
		$this->option = $this->optionMapper->find($optionId);
		$this->getPoll($this->option->getPollId(), Poll::PERMISSION_POLL_EDIT);

		if ($this->poll->getType() === Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Not allowed in date polls');
		}

		if ($newOrder < 1) {
			$newOrder = 1;
		} elseif ($newOrder > $this->getHighestOrder($this->poll->getId())) {
			$newOrder = $this->getHighestOrder($this->poll->getId());
		}

		foreach ($this->optionMapper->findByPoll($this->poll->getId()) as $option) {
			$option->setOrder($this->moveModifier($this->option->getOrder(), $newOrder, $option->getOrder()));
			$this->optionMapper->update($option);
		}

		$this->eventDispatcher->dispatchTyped(new PollOptionReorderedEvent($this->poll));

		return $this->optionMapper->findByPoll($this->poll->getId());
	}

	/**
	 * moveModifier - evaluate new order depending on the old and
	 * the new position of a moved array item
	 *
	 * @return int - The modified new position of the current item
	 */
	private function moveModifier(int $moveFrom, int $moveTo, int $currentPosition): int {
		$moveModifier = 0;
		if ($moveFrom < $currentPosition && $currentPosition <= $moveTo) {
			// moving forward
			$moveModifier = -1;
		} elseif ($moveTo <= $currentPosition && $currentPosition < $moveFrom) {
			//moving backwards
			$moveModifier = 1;
		} elseif ($moveFrom === $currentPosition) {
			return $moveTo;
		}
		return $currentPosition + $moveModifier;
	}

	/**
	 * Load the poll and check permissions
	 *
	 * @return void
	 */
	private function getPoll(int $pollId, string $permission = Poll::PERMISSION_POLL_VIEW): void {
		$this->poll = $this->pollMapper->find($pollId);
		$this->poll->request($permission);
	}

	/**
	 * Remove booked up options, because they are not votable
	 *
	 * @return void
	 */
	private function filterBookedUp() {
		if (!$this->poll->getHideBookedUp() || $this->poll->getIsAllowed(Poll::PERMISSION_POLL_EDIT)) {
			return;
		}

		$this->options = array_filter($this->options, function ($option) {
			return (!$option->getIsLockedByOptionLimit());
		});
	}

	/**
	 * Get the highest order number in $pollId
	 * Return Highest order number
	 *
	 * @return int
	 */
	public function getHighestOrder(int $pollId): int {
		$result = intval($this->optionMapper->getOrderBoundaries($pollId)['max']);
		return $result;
	}
}
