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
use OCA\Polls\Db\Vote;
use OCA\Polls\Event\OptionConfirmedEvent;
use OCA\Polls\Event\OptionCreatedEvent;
use OCA\Polls\Event\OptionDeletedEvent;
use OCA\Polls\Event\OptionUnconfirmedEvent;
use OCA\Polls\Event\OptionUpdatedEvent;
use OCA\Polls\Event\PollOptionReorderedEvent;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Model\Sequence;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class OptionService {
	/** @var Option[] */
	private array $options;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private UserSession $userSession,
		private Poll $poll,
		private VoteService $voteService,
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
	 * Intermediate step to avoid code duplication
	 */
	public function addWithSequenceAndAutoVote(
		int $pollId,
		SimpleOption $option,
		bool $voteYes = false,
		?Sequence $sequence = null,
	): array {

		$newOption = $this->add($pollId, $option, $voteYes);


		if ($sequence) {
			$repetitions = $this->sequence($newOption, $sequence, $voteYes);
		} else {
			$repetitions = [];
		}

		return [
			'option' => $newOption,
			'repetitions' => $repetitions,
		];
	}

	/**
	 * Add a new option to a poll
	 *
	 * @param int $pollId poll id of poll to add option to
	 * @param SimpleOption $simpleOption SimpleOption object
	 * @param bool $voteYes Directly vote 'yes' for the new option
	 * @return Option
	 */
	public function add(int $pollId, SimpleOption $simpleOption, bool $voteYes = false): Option {
		$this->getPoll($pollId, Poll::PERMISSION_OPTION_ADD);

		if ($this->poll->getType() === Poll::TYPE_TEXT) {
			$simpleOption->setOrder($this->getHighestOrder($pollId) + 1);
		}

		// Build the new option
		$newOption = new Option();
		$newOption->setPollId($pollId);
		$newOption->setFromSimpleOption($simpleOption);

		if (!$this->poll->getIsPollOwner()) {
			$newOption->setOwner($this->userSession->getCurrentUserId());
		}

		try {
			// Insert the new option
			$newOption = $this->optionMapper->insert($newOption);
		} catch (Exception $e) {
			// TODO: Change exception catch to actual exception
			// Currently OC\DB\Exceptions\DbalException is thrown instead of
			// UniqueConstraintViolationException
			// since the exception is from private namespace, we check the type string
			if (get_class($e) === 'OC\DB\Exceptions\DbalException' || $e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {

				// Option already exists, so we need to update the existing one
				// and remove deleted setting
				$option = $this->optionMapper->findByPollAndText($pollId, $newOption->getPollOptionText(), true);
				$option->setDeleted(0);

				$newOption = $this->optionMapper->update($option);

			} else {
				throw $e;
			}
		}


		if ($voteYes) {
			// Set the vote for the new option on request
			$this->voteService->set($newOption, Vote::VOTE_YES);
		}

		$this->eventDispatcher->dispatchTyped(new OptionCreatedEvent($newOption));

		return $newOption;
	}
	/**
	 * Add a new option
	 * @param int $pollId poll id of poll to add option to
	 * @param string $bulkText Text for new options separated by new lines
	 * @return Option[]
	 */
	public function addBulk(int $pollId, string $bulkText = ''): array {
		$this->getPoll($pollId, Poll::PERMISSION_OPTION_ADD);

		$newOptionsTexts = array_unique(explode(PHP_EOL, $bulkText));

		foreach ($newOptionsTexts as $pollOptionText) {
			if ($pollOptionText) {
				$this->add($pollId, new SimpleOption($pollOptionText, 0));
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
		$option = $this->optionMapper->find($optionId);
		$this->getPoll($option->getPollId(), Poll::PERMISSION_POLL_EDIT);

		$option->setOption($timestamp, $duration, $pollOptionText);

		$option = $this->optionMapper->update($option);
		$this->eventDispatcher->dispatchTyped(new OptionUpdatedEvent($option));

		return $option;
	}

	/**
	 * Delete option
	 * @param int $optionId Id of option to delete or restore
	 * @param bool $restore Set true, if option is to be restored
	 */
	public function delete(int $optionId, bool $restore = false): Option {
		$option = $this->optionMapper->find($optionId);

		if (!$option->getCurrentUserIsEntityUser()) {
			$this->pollMapper->find($option->getPollId())->request(Poll::PERMISSION_OPTION_DELETE);
		}

		$option->setDeleted($restore ? 0 : time());
		$this->optionMapper->update($option);
		$this->eventDispatcher->dispatchTyped(new OptionDeletedEvent($option));

		return $option;
	}

	/**
	 * Switch option confirmation
	 *
	 * @return Option
	 */
	public function confirm(int $optionId): Option {
		$option = $this->optionMapper->find($optionId);
		$this->getPoll($option->getPollId(), Poll::PERMISSION_OPTION_CONFIRM);

		$option->setConfirmed($option->getConfirmed() ? 0 : time());
		$option = $this->optionMapper->update($option);

		if ($option->getConfirmed()) {
			$this->eventDispatcher->dispatchTyped(new OptionConfirmedEvent($option));
		} else {
			$this->eventDispatcher->dispatchTyped(new OptionUnconfirmedEvent($option));
		}

		return $option;
	}

	/**
	 * Make a sequence of date poll options
	 *
	 * @param int | Option $optionOrOptionId Option od optionId of the option to clone
	 * @param Sequence $sequence Sequence object
	 * @param bool $voteYes Directly vote 'yes' for the new options
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function sequence(int|Option $optionOrOptionId, Sequence $sequence, bool $voteYes = false): array {
		if ($sequence->getRepetitions() < 1) {
			return [];
		}

		if ($optionOrOptionId instanceof Option) {
			$baseOption = $optionOrOptionId;
		} else {
			$baseOption = $this->optionMapper->find($optionOrOptionId);
		}

		$this->getPoll($baseOption->getPollId(), Poll::PERMISSION_OPTION_ADD);

		if ($this->poll->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Sequences are only available in date polls');
		}

		$sequence->setTimeZone(new DateTimeZone($this->userSession->getClientTimeZone()));
		$sequence->setBaseTimeStamp($baseOption->getTimestamp());

		// iterate over the amount of options to create
		for ($i = 1; $i <= ($sequence->getRepetitions()); $i++) {
			// build a new option
			$this->add(
				$baseOption->getPollId(),
				new SimpleOption(
					'',
					$sequence->getOccurence($i),
					$baseOption->getDuration(),
				),
				$voteYes
			);
		}

		$this->eventDispatcher->dispatchTyped(new OptionCreatedEvent($baseOption));

		// return list of all options of the poll
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
		$this->getPoll($pollId, Poll::PERMISSION_OPTIONS_SHIFT);
		$timezone = new DateTimeZone($this->userSession->getClientTimeZone());

		if ($this->poll->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Shifting is only available in date polls');
		}

		$options = $this->optionMapper->findByPoll($pollId);

		if ($step > 0) {
			// start from last item if moving option into the future
			// avoid UniqueConstraintViolationException
			$options = array_reverse($options);
		}

		foreach ($options as $option) {
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
		$this->pollMapper->find($toPollId)->request(Poll::PERMISSION_OPTION_ADD);

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
			// we do not trust the delivered array, so we try to load the option from the db
			$loadedOption = $this->optionMapper->find($option['id']);

			// check, if the loaded option matches the pollId
			if ($pollId === intval($loadedOption->getPollId())) {
				$loadedOption->setOrder(++$i);
				$this->optionMapper->update($loadedOption);
				$this->eventDispatcher->dispatchTyped(new OptionUpdatedEvent($loadedOption));
			} else {
				$this->logger->error('Option {optionId} does not belong to poll {pollId}', [
					'optionId' => $loadedOption->getId(),
					'pollId' => $pollId,
				]);
				throw new DoesNotExistException('Option does not belong to poll');
			}
		}

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
		$option = $this->optionMapper->find($optionId);
		$this->getPoll($option->getPollId(), Poll::PERMISSION_POLL_EDIT);

		if ($this->poll->getType() === Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Not allowed in date polls');
		}

		if ($newOrder < 1) {
			$newOrder = 1;
		} elseif ($newOrder > $this->getHighestOrder($this->poll->getId())) {
			$newOrder = $this->getHighestOrder($this->poll->getId());
		}

		foreach ($this->optionMapper->findByPoll($this->poll->getId()) as $option) {
			$option->setOrder($this->moveModifier($option->getOrder(), $newOrder, $option->getOrder()));
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
		if ($this->poll->getId() !== $pollId) {
			$this->poll = $this->pollMapper->find($pollId);
		}
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
