<?php

declare(strict_types=1);
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
use DateTimeZone;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Event\OptionConfirmedEvent;
use OCA\Polls\Event\OptionCreatedEvent;
use OCA\Polls\Event\OptionDeletedEvent;
use OCA\Polls\Event\OptionUnconfirmedEvent;
use OCA\Polls\Event\OptionUpdatedEvent;
use OCA\Polls\Event\PollOptionReorderedEvent;
use OCA\Polls\Exceptions\DuplicateEntryException;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Model\Acl;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ISession;
use Psr\Log\LoggerInterface;

class OptionService {
	/** @var Option[] */
	private array $options;

	public function __construct(
		private Acl $acl,
		private AnonymizeService $anonymizer,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
		private Option $option,
		private OptionMapper $optionMapper,
		private ISession $session,
	) {
		$this->options = [];
	}

	/**
	 * Get all options of given poll
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function list(int $pollId = 0, ?Acl $acl = null): array {
		if ($acl) {
			$this->acl = $acl;
		} else {
			$this->acl->setPollId($pollId);
		}

		try {
			$this->options = $this->optionMapper->findByPoll($this->acl->getPollId(), !$this->acl->getIsAllowed(Acl::PERMISSION_POLL_RESULTS_VIEW));
			if (!$this->acl->getIsAllowed(Acl::PERMISSION_POLL_USERNAMES_VIEW) || !$this->acl->getIsAllowed(Acl::PERMISSION_POLL_RESULTS_VIEW)) {
				$this->anonymizer->set($this->acl->getpollId(), $this->acl->getUserId());
				$this->anonymizer->anonymize($this->options);
			}

			if ($this->acl->getPoll()->getHideBookedUp() && !$this->acl->getIsAllowed(Acl::PERMISSION_POLL_EDIT)) {
				// hide booked up options except the user has edit permission
				$this->filterBookedUp();
			}
		} catch (DoesNotExistException $e) {
			$this->options = [];
		}

		return array_values($this->options);
	}

	/**
	 * Add a new option
	 *
	 * @return Option
	 */
	public function add(int $pollId = 0, int $timestamp = 0, string $pollOptionText = '', int $duration = 0, ?Acl $acl = null): Option {
		if ($acl) {
			$this->acl = $acl;
		} else {
			$this->acl->setPollId($pollId, Acl::PERMISSION_OPTIONS_ADD);
		}

		$this->option = new Option();
		$this->option->setPollId($this->acl->getPollId());
		$this->option->setOrder($this->getHighestOrder($this->acl->getPollId()) + 1);
		$this->setOption($timestamp, $pollOptionText, $duration);

		if (!$this->acl->getIsOwner()) {
			$this->option->setOwner($this->acl->getUserId());
		}

		try {
			$this->option = $this->optionMapper->add($this->option);
		} catch (Exception $e) {
			// TODO: Change exception catch to actual exception
			// Currently OC\DB\Exceptions\DbalException is thrown instead of
			// UniqueConstraintViolationException
			// since the exception is from private namespace, we check the type string
			if (get_class($e) === 'OC\DB\Exceptions\DbalException' || $e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {

				$option = $this->optionMapper->findByPollAndText($pollId, $this->option->getPollOptionText(), true);
				if ($option->getDeleted()) {
					// Deleted option exist, restore deleted option and generate new token
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
		$this->acl->setPollId($pollId, Acl::PERMISSION_OPTIONS_ADD);

		$newOptions = array_unique(explode(PHP_EOL, $pollOptionText));
		foreach ($newOptions as $option) {
			if ($option) {
				try {
					$this->add($pollId, 0, $option);
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
	public function update(int $optionId, int $timestamp = 0, ?string $pollOptionText = '', ?int $duration = 0): Option {
		$this->option = $this->optionMapper->find($optionId);
		$this->acl->setPollId($this->option->getPollId(), Acl::PERMISSION_POLL_EDIT);

		$this->setOption($timestamp, $pollOptionText, $duration);

		$this->option = $this->optionMapper->change($this->option);
		$this->eventDispatcher->dispatchTyped(new OptionUpdatedEvent($this->option));

		return $this->option;
	}

	/**
	 * Delete option
	 * @param int $optionId Id of option to delete or restore
	 * @param Acl $acl Acl
	 * @param bool $restore Set true, if option is to be restored
	 */
	public function delete(int $optionId, ?Acl $acl = null, bool $restore = false): Option {
		$this->option = $this->optionMapper->find($optionId);

		if ($acl) {
			$this->acl = $acl;
		} else {
			$this->acl->setPollId($this->option->getPollId());
		}

		if ($this->option->getPollId() !== $this->acl->getPollid()) {
			throw new ForbiddenException('Trying to delete or restore an option with foreign poll id');
		}

		if ($this->option->getOwner() !== $this->acl->getUserId()) {
			$this->acl->request(Acl::PERMISSION_POLL_EDIT);
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
		$this->acl->setPollId($this->option->getPollId(), Acl::PERMISSION_POLL_EDIT);

		$this->option->setConfirmed($this->option->getConfirmed() ? 0 : time());
		$this->option = $this->optionMapper->change($this->option);

		if ($this->option->getConfirmed()) {
			$this->eventDispatcher->dispatchTyped(new OptionConfirmedEvent($this->option));
		} else {
			$this->eventDispatcher->dispatchTyped(new OptionUnconfirmedEvent($this->option));
		}

		return $this->option;
	}

	private function getModifiedDateOption(Option $option, DateTimeZone $timeZone, int $step, string $unit): array {
		$from = (new DateTime())
			->setTimestamp($option->getTimestamp())
			->setTimezone($timeZone)
			->modify($step . ' ' . $unit);
		$to = (new DateTime())
			->setTimestamp($option->getTimestamp() + $option->getDuration())
			->setTimezone($timeZone)
			->modify($step . ' ' . $unit);
		return [
			'from' => $from,
			'to' => $to,
			'duration' => $to->getTimestamp() - $from->getTimestamp(),
		];
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
		$this->acl->setPollId($this->option->getPollId(), Acl::PERMISSION_POLL_EDIT);
		$timezone = new DateTimeZone($this->session->get(AppConstants::CLIENT_TZ));

		if ($this->acl->getPoll()->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Sequences are only available in date polls');
		}

		if ($step === 0) {
			return $this->optionMapper->findByPoll($this->option->getPollId());
		}

		for ($i = 1; $i < ($amount + 1); $i++) {
			$clonedOption = new Option();
			$clonedOption->setPollId($this->option->getPollId());
			$clonedOption->setConfirmed(0);

			$newDates = $this->getModifiedDateOption($this->option, $timezone, ($step * $i), $unit);
			$clonedOption->setTimestamp($newDates['from']->getTimestamp());
			$clonedOption->setDuration($newDates['duration']);

			try {
				$this->optionMapper->add($clonedOption);
			} catch (Exception $e) {
				$this->logger->warning('skip adding ' . $newDates['from']->format('c') . 'for pollId ' . $this->option->getPollId() . '. Option already exists.');
			}
		}

		$this->eventDispatcher->dispatchTyped(new OptionCreatedEvent($this->option));

		return $this->optionMapper->findByPoll($this->acl->getPollId());
	}

	/**
	 * Shift all date options
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function shift(int $pollId, int $step, string $unit): array {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
		$timezone = new DateTimeZone($this->session->get(AppConstants::CLIENT_TZ));

		if ($this->acl->getPoll()->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Shifting is only available in date polls');
		}

		$this->options = $this->optionMapper->findByPoll($pollId);

		if ($step > 0) {
			// start from last item if moving option into the future
			// avoid UniqueConstraintViolationException
			$this->options = array_reverse($this->options);
		}

		foreach ($this->options as $option) {
			$newDates = $this->getModifiedDateOption($option, $timezone, $step, $unit);
			$option->setTimestamp($newDates['from']->getTimestamp());
			$option->setDuration($newDates['duration']);
			$this->optionMapper->update($option);
		}

		return $this->optionMapper->findByPoll($pollId);
	}

	/**
	 * Copy options from $fromPoll to $toPoll
	 */
	public function clone(int $fromPollId, int $toPollId): void {
		$this->acl->setPollId($fromPollId);

		foreach ($this->optionMapper->findByPoll($fromPollId) as $origin) {
			$option = new Option();
			$option->setPollId($toPollId);
			$option->setConfirmed(0);
			$option->setPollOptionText($origin->getPollOptionText());
			$option->setTimestamp($origin->getTimestamp());
			$option->setDuration($origin->getDuration());
			$option->setOrder($origin->getOrder());
			$this->optionMapper->add($option);
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
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);

		if ($this->acl->getPoll()->getType() === Poll::TYPE_DATE) {
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
		$this->acl->setPollId($this->option->getPollId(), Acl::PERMISSION_POLL_EDIT);

		if ($this->acl->getPoll()->getType() === Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Not allowed in date polls');
		}

		if ($newOrder < 1) {
			$newOrder = 1;
		} elseif ($newOrder > $this->getHighestOrder($this->acl->getPollId())) {
			$newOrder = $this->getHighestOrder($this->acl->getPollId());
		}

		foreach ($this->optionMapper->findByPoll($this->acl->getPollId()) as $option) {
			$option->setOrder($this->moveModifier($this->option->getOrder(), $newOrder, $option->getOrder()));
			$this->optionMapper->update($option);
		}

		$this->eventDispatcher->dispatchTyped(new PollOptionReorderedEvent($this->acl->getPoll()));

		return $this->optionMapper->findByPoll($this->acl->getPollId());
	}

	/**
	 * moveModifier - evaluate new order depending on the old and
	 * the new position of a moved array item
	 *
	 * @return int - The modified new new position of the current item
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
	 * Set option entities validated
	 *
	 * @return void
	 */
	private function setOption(int $timestamp = 0, ?string $pollOptionText = '', ?int $duration = 0): void {
		if ($timestamp) {
			$this->option->setTimestamp($timestamp);
			$this->option->setOrder($timestamp);
			$this->option->setDuration($duration ?? 0);
			if ($duration > 0) {
				$this->option->setPollOptionText(date('c', $timestamp) . ' - ' . date('c', $timestamp + $duration));
			} else {
				$this->option->setPollOptionText(date('c', $timestamp));
			}
		} elseif ($pollOptionText) {
			$this->option->setPollOptionText($pollOptionText);
		} else {
			throw new InsufficientAttributesException('Option must have a value');
		}
	}

	/**
	 * Remove booked up options, because they are not votable
	 *
	 * @return void
	 */
	private function filterBookedUp() {
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
