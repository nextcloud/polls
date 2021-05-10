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

use Psr\Log\LoggerInterface;
use DateTime;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\BadRequestException;
use OCA\Polls\Exceptions\DuplicateEntryException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\InvalidOptionPropertyException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Watch;
use OCA\Polls\Model\Acl;

class OptionService {

	/** @var LoggerInterface */
	private $logger;

	/** @var string */
	private $appName;

	/** @var Acl */
	private $acl;

	/** @var Option */
	private $option;

	/** @var int */
	private $countParticipants;

	/** @var Poll */
	private $poll;

	/** @var Option[] */
	private $options;

	/** @var Vote[] */
	private $votes;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var WatchService */
	private $watchService;

	public function __construct(
		string $AppName,
		LoggerInterface $logger,
		Acl $acl,
		Option $option,
		OptionMapper $optionMapper,
		PollMapper $pollMapper,
		VoteMapper $voteMapper,
		WatchService $watchService
	) {
		$this->appName = $AppName;
		$this->logger = $logger;
		$this->acl = $acl;
		$this->option = $option;
		$this->optionMapper = $optionMapper;
		$this->pollMapper = $pollMapper;
		$this->voteMapper = $voteMapper;
		$this->watchService = $watchService;
	}

	/**
	 * Get all options of given poll
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function list(int $pollId = 0, string $token = ''): array {
		if ($token) {
			$this->acl->setToken($token);
		} else {
			$this->acl->setPollId($pollId);
		}
		$this->poll = $this->acl->getPoll();

		try {
			$this->poll = $this->pollMapper->find($pollId);
			$this->options = $this->optionMapper->findByPoll($pollId);
			$this->votes = $this->voteMapper->findByPoll($pollId);
			$this->countParticipants = count($this->voteMapper->findParticipantsByPoll($pollId));

			$this->calculateVotes();

			if ($this->poll->getHideBookedUp() && !$this->acl->isAllowed(Acl::PERMISSION_POLL_EDIT)) {
				// hide booked up options except the user has edit permission
				$this->filterBookedUp();
			} elseif ($this->acl->isAllowed(Acl::PERMISSION_POLL_RESULTS_VIEW)) {
				$this->calculateRanks();
			}

			return array_values($this->options);
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	/**
	 * Get option
	 *
	 * @return Option
	 */
	public function get(int $optionId): Option {
		$option = $this->optionMapper->find($optionId);
		$this->acl->setPollId($option->getPollId());
		return $option;
	}


	/**
	 * Add a new option
	 *
	 * @return Option
	 */
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = '', ?int $duration = 0, string $token = ''): Option {
		if ($token) {
			$this->acl->setToken($token)->request(Acl::PERMISSION_OPTIONS_ADD);
			$pollId = $this->acl->getPollId();
		} else {
			$this->acl->setPollId($pollId)->request(Acl::PERMISSION_OPTIONS_ADD);
		}

		$this->option = new Option();
		$this->option->setPollId($pollId);
		$this->option->setOrder($this->getHighestOrder($this->option->getPollId()) + 1);
		$this->setOption($timestamp, $pollOptionText, $duration);

		if (!$this->acl->getIsOwner()) {
			$this->option->setOwner($this->acl->getUserId());
		}

		if (!$this->acl->getIsOwner()) {
			$this->option->setOwner($this->acl->getUserId());
		}

		try {
			$this->option = $this->optionMapper->insert($this->option);
			$this->watchService->writeUpdate($this->option->getPollId(), Watch::OBJECT_OPTIONS);
		} catch (UniqueConstraintViolationException $e) {
			throw new DuplicateEntryException('This option already exists');
		}
		return $this->option;
	}

	/**
	 * Update option
	 *
	 * @return Option
	 */
	public function update(int $optionId, int $timestamp = 0, ?string $pollOptionText = '', ?int $duration = 0): Option {
		$this->option = $this->optionMapper->find($optionId);
		if (!$this->acl->setPollId($this->option->getPollId())->isAllowed(Acl::PERMISSION_POLL_EDIT)
			&& $this->option->getOwner() !== $this->acl->getUserId()) {
			throw new NotAuthorizedException('You are not allowed to delete this option');
		}
		$this->setOption($timestamp, $pollOptionText, $duration);

		$this->option = $this->optionMapper->update($this->option);
		$this->watchService->writeUpdate($this->option->getPollId(), Watch::OBJECT_OPTIONS);
		return $this->option;
	}

	/**
	 * Delete option
	 *
	 * @return Option
	 */
	public function delete(int $optionId, string $token = ''): Option {
		$this->option = $this->optionMapper->find($optionId);
		if ($token) {
			$this->acl->setToken($token);
		} else {
			$this->acl->setPollId($this->option->getPollId());
		}

		if (!$this->acl->isAllowed(Acl::PERMISSION_POLL_EDIT)
			&& $this->option->getOwner() !== $this->acl->getUserId()) {
			throw new NotAuthorizedException('You are not allowed to delete this option');
		}
		$this->optionMapper->delete($this->option);
		$this->watchService->writeUpdate($this->option->getPollId(), Watch::OBJECT_OPTIONS);

		return $this->option;
	}

	/**
	 * Switch option confirmation
	 *
	 * @return Option
	 */
	public function confirm(int $optionId): Option {
		$this->option = $this->optionMapper->find($optionId);
		$this->acl->setPollId($this->option->getPollId())->request(Acl::PERMISSION_POLL_EDIT);

		$this->option->setConfirmed($this->option->getConfirmed() ? 0 : time());
		$this->option = $this->optionMapper->update($this->option);
		$this->watchService->writeUpdate($this->option->getPollId(), Watch::OBJECT_OPTIONS);
		return $this->option;
	}

	/**
	 * Make a sequence of date poll options
	 * @param int $optionId
	 * @param int $step - The step for creating the sequence
	 * @param string $unit - The timeunit (year, month, ...)
	 * @param int $amount - Number of sequence items to create
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function sequence(int $optionId, int $step, string $unit, int $amount): array {
		$baseDate = new DateTime;
		$this->option = $this->optionMapper->find($optionId);
		$this->acl->setPollId($this->option->getPollId())->request(Acl::PERMISSION_POLL_EDIT);

		if ($this->pollMapper->find($this->acl->getPollId())->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Only allowed in date polls');
		}


		if ($step === 0) {
			return $this->optionMapper->findByPoll($this->option->getPollId());
		}

		$baseDate->setTimestamp($this->option->getTimestamp());

		for ($i = 0; $i < $amount; $i++) {
			$clonedOption = new Option();
			$clonedOption->setPollId($this->option->getPollId());
			$clonedOption->setDuration($this->option->getDuration());
			$clonedOption->setConfirmed(0);
			$clonedOption->setTimestamp($baseDate->modify($step . ' ' . $unit)->getTimestamp());
			$clonedOption->setOrder($clonedOption->getTimestamp());
			$clonedOption->setPollOptionText($baseDate->format('c'));
			try {
				$this->optionMapper->insert($clonedOption);
			} catch (UniqueConstraintViolationException $e) {
				$this->logger->warning('skip adding ' . $baseDate->format('c') . 'for pollId' . $this->option->getPollId() . '. Option alredy exists.');
			}
		}
		$this->watchService->writeUpdate($this->option->getPollId(), Watch::OBJECT_OPTIONS);
		return $this->optionMapper->findByPoll($this->option->getPollId());
	}

	/**
	 * Shift all date options
	 * @param int $pollId
	 * @param int $step - The step for creating the sequence
	 * @param string $unit - The timeunit (year, month, ...)
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function shift(int $pollId, int $step, string $unit): array {
		$this->acl->setPollId($pollId)->request(Acl::PERMISSION_POLL_EDIT);
		if ($this->pollMapper->find($pollId)->getType() !== Poll::TYPE_DATE) {
			throw new InvalidPollTypeException('Only allowed in date polls');
		}

		$this->options = $this->optionMapper->findByPoll($pollId);
		$shiftedDate = new DateTime;

		if ($step > 0) {
			// avoid contraint errors
			$this->options = array_reverse($this->options);
		}

		foreach ($this->options as $option) {
			$shiftedDate->setTimestamp($option->getTimestamp());
			$option->setTimestamp($shiftedDate->modify($step . ' ' . $unit)->getTimestamp());
			$this->optionMapper->update($option);
		}

		return $this->optionMapper->findByPoll($pollId);
	}

	/**
	 * Copy options from $fromPoll to $toPoll
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function clone(int $fromPollId, int $toPollId): array {
		$this->acl->setPollId($fromPollId);

		foreach ($this->optionMapper->findByPoll($fromPollId) as $origin) {
			$option = new Option();
			$option->setPollId($toPollId);
			$option->setConfirmed(0);
			$option->setPollOptionText($origin->getPollOptionText());
			$option->setTimestamp($origin->getTimestamp());
			$option->setDuration($origin->getDuration());
			$option->setOrder($option->getOrder());
			$this->optionMapper->insert($option);
		}

		return $this->optionMapper->findByPoll($toPollId);
	}

	/**
	 * Reorder options with the order specified by $options
	 *
	 * @return Option[]
	 *
	 * @psalm-return array<array-key, Option>
	 */
	public function reorder(int $pollId, array $options): array {
		try {
			$this->poll = $this->pollMapper->find($pollId);
			$this->acl->setPoll($this->poll)->request(Acl::PERMISSION_POLL_EDIT);

			if ($this->poll->getType() === Poll::TYPE_DATE) {
				throw new BadRequestException('Not allowed in date polls');
			}
		} catch (DoesNotExistException $e) {
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

		$this->watchService->writeUpdate($pollId, Watch::OBJECT_OPTIONS);
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
		try {
			$this->option = $this->optionMapper->find($optionId);
			$this->poll = $this->pollMapper->find($this->option->getPollId());
			$this->acl->setPoll($this->poll)->request(Acl::PERMISSION_POLL_EDIT);

			if ($this->poll->getType() === Poll::TYPE_DATE) {
				throw new InvalidPollTypeException('Not allowed in date polls');
			}
		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException;
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

		$this->watchService->writeUpdate($this->option->getPollId(), Watch::OBJECT_OPTIONS);
		return $this->optionMapper->findByPoll($this->option->getPollId());
	}

	/**
	 * moveModifier - evaluate new order depending on the old and
	 * the new position of a moved array item
	 * @param int $moveFrom - old position of the moved item
	 * @param int $moveTo - target posotion of the moved item
	 * @param int $currentPosition - current position of the current item
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
		$this->poll = $this->pollMapper->find($this->option->getPollId());

		if ($this->poll->getType() === Poll::TYPE_DATE) {
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
			throw new InvalidOptionPropertyException('Option must have a value');
		}
	}

	/**
	 * Get all voteOptionTexts of the options, the user opted in
	 * with yes or maybe
	 *
	 * @return array
	 */
	private function getUsersVotes() {
		// Thats an ugly solution, but for now, it seems to work
		// Optimization proposals are welcome

		// First: Find votes, where the user voted yes or maybe
		$userId = $this->acl->getUserId();
		$exceptVotes = array_filter($this->votes, function ($vote) use ($userId) {
			return $vote->getUserId() === $userId && in_array($vote->getVoteAnswer(), ['yes', 'maybe']);
		});

		// Second: Extract only the vote option texts to an array
		return array_values(array_map(function ($vote) {
			return $vote->getVoteOptionText();
		}, $exceptVotes));
	}

	/**
	 * Remove booked up options, because they are not votable
	 *
	 * @return void
	 */
	private function filterBookedUp() {
		$exceptVotes = $this->getUsersVotes();
		$this->options = array_filter($this->options, function ($option) use ($exceptVotes) {
			return (!$option->isBookedUp || in_array($option->getPollOptionText(), $exceptVotes));
		});
	}

	/**
	 * Calculate the votes of each option and determines if the option is booked up
	 * - unvoted counts as no
	 * - realNo reports the actually opted out votes
	 *
	 * @return void
	 */
	private function calculateVotes() {
		foreach ($this->options as $option) {
			$option->yes = count(
				array_filter($this->votes, function ($vote) use ($option) {
					return ($vote->getVoteOptionText() === $option->getPollOptionText()
						&& $vote->getVoteAnswer() === 'yes') ;
				})
			);

			$option->realNo = count(
				array_filter($this->votes, function ($vote) use ($option) {
					return ($vote->getVoteOptionText() === $option->getPollOptionText()
						&& $vote->getVoteAnswer() === 'no');
				})
			);

			$option->maybe = count(
				array_filter($this->votes, function ($vote) use ($option) {
					return ($vote->getVoteOptionText() === $option->getPollOptionText()
						&& $vote->getVoteAnswer() === 'maybe');
				})
			);

			$option->isBookedUp = $this->poll->getOptionLimit() ? $this->poll->getOptionLimit() <= $option->yes : false;

			// remove details, if the results shall be hidden
			if (!$this->acl->isAllowed(Acl::PERMISSION_POLL_RESULTS_VIEW)) {
				$option->yes = 0;
				$option->no = 0;
				$option->maybe = 0;
				$option->realNo = 0;
			} else {
				$option->no = $this->countParticipants - $option->maybe - $option->yes;
			}
		}
	}

	/**
	 * Calculate the rank of each option based on the
	 * yes and maybe votes and recognize equal ranked options
	 *
	 * @return void
	 */
	private function calculateRanks() {
		// sort array by yes and maybe votes
		usort($this->options, function (Option $a, Option $b):int {
			$diff = $b->yes - $a->yes;
			return ($diff !== 0) ? $diff : $b->maybe - $a->maybe;
		});

		// calculate the rank
		$count = count($this->options);
		for ($i = 0; $i < $count; $i++) {
			if ($i > 0 && $this->options[$i]->yes === $this->options[$i - 1]->yes && $this->options[$i]->maybe === $this->options[$i - 1]->maybe) {
				$this->options[$i]->rank = $this->options[$i - 1]->rank;
			} else {
				$this->options[$i]->rank = $i + 1;
			}
		}

		// restore original order
		usort($this->options, function (Option $a, Option $b):int {
			return $a->getOrder() - $b->getOrder();
		});
	}

	/**
	 * Get the highest order number in $pollId
	 * Return Highest order number
	 *
	 * @return int
	 */
	private function getHighestOrder(int $pollId): int {
		$highestOrder = 0;
		foreach ($this->optionMapper->findByPoll($pollId) as $option) {
			$highestOrder = ($option->getOrder() > $highestOrder) ? $option->getOrder() : $highestOrder;
		}
		return $highestOrder;
	}
}
