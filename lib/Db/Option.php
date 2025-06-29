<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\Model\UserBase;
use OCP\IL10N;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method int getConfirmed()
 * @method void setConfirmed(int $value)
 * @method int getDuration()
 * @method void setDuration(int $value)
 * @method int getOrder()
 * @method void setOrder(int $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getPollId()
 * @method void setPollId(?int $value)
 * @method string getPollOptionText()
 * @method void setPollOptionText(string $value)
 * @method string getPollOptionHash()
 * @method void setPollOptionHash(string $value)
 * @method int getReleased()
 * @method void setReleased(int $value)
 * @method int getTimestamp()
 * @method void setTimestamp(int $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 *
 * Joined Attributes
 * @method string getUserVoteAnswer()
 * @method int getOptionLimit()
 * @method int getVoteLimit()
 * @method int getUserCountYesVotes()
 * @method int getCountOptionVotes()
 * @method int getVotesYes()
 * @method int getVotesNo()
 * @method int getVotesMaybe()
 * @method int getShowResults()
 */
class Option extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_options';

	// schema columns
	public $id = null;
	protected ?int $pollId = null;
	protected string $pollOptionText = '';
	protected string $pollOptionHash = '';
	protected int $timestamp = 0;
	protected int $duration = 0;
	protected int $order = 0;
	protected int $confirmed = 0;
	protected string $owner = '';
	protected int $released = 0;
	protected int $deleted = 0;

	// joined columns
	protected ?string $userVoteAnswer = '';
	protected int $optionLimit = 0;
	protected int $voteLimit = 0;
	protected int $userCountYesVotes = 0;
	protected int $countOptionVotes = 0;
	protected int $votesYes = 0;
	protected int $votesNo = 0;
	protected int $votesMaybe = 0;
	protected int $showResults = 0;

	public function __construct() {
		$this->addType('released', 'integer');
		$this->addType('pollId', 'integer');
		$this->addType('timestamp', 'integer');
		$this->addType('order', 'integer');
		$this->addType('confirmed', 'integer');
		$this->addType('duration', 'integer');
		$this->addType('deleted', 'integer');

		// joined Attributes
		$this->addType('optionLimit', 'integer');
		$this->addType('voteLimit', 'integer');
		$this->addType('userCountYesVotes', 'integer');
		$this->addType('countOptionVotes', 'integer');
		$this->addType('votesYes', 'integer');
		$this->addType('votesNo', 'integer');
		$this->addType('votesMaybe', 'integer');
		$this->addType('showResults', 'integer');
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'text' => $this->getPollOptionText(),
			'timestamp' => $this->getTimestamp(),
			'deleted' => $this->getDeleted(),
			'order' => $this->getOrder(),
			'confirmed' => $this->getConfirmed(),
			'duration' => $this->getDuration(),
			'locked' => $this->getIsLocked(),
			'hash' => $this->getPollOptionHash(),
			'isOwner' => $this->getCurrentUserIsEntityUser(),
			'votes' => $this->getVotes(),
			'owner' => $this->getOwnerUser(),
		];
	}

	private function getVotes(): array {
		return [
			'no' => $this->getVotesNo() * $this->getShowResults(),
			'yes' => $this->getVotesYes() * $this->getShowResults(),
			'maybe' => $this->getVotesMaybe() * $this->getShowResults(),
			'count' => $this->getCountOptionVotes() * $this->getShowResults(),
			'currentUser' => $this->getUserVoteAnswer(),
		];
	}

	public function getOwnerUser(): ?UserBase {
		if ($this->getOwner() === '') {
			return null;
		}
		return parent::getUser();
	}

	public function setFromSimpleOption(SimpleOption $option): void {
		$this->setOption(
			$option->getTimestamp(),
			$option->getDuration(),
			$option->getText(),
			$option->getOrder(),
		);
	}

	/**
	 * cumulative Set option entities cumulative and validated
	 * if timestamp is given, the pollOptionText will be synced according to the timestamp and duration
	 *
	 * @param int $timestamp Timestamp to set
	 * @param int $duration Set duration of option in seconds and used together with timestamp, defaults to 0
	 * @param string $pollOptionText Option text, ignored if $timestamp is set
	 * @param int $order Set order of this option inside the poll, defaults to 0, ignored if timestap is set
	 * @return void
	 */
	public function setOption(
		int $timestamp = 0,
		int $duration = 0,
		string $pollOptionText = '',
		int $order = 0,
	): void {

		if ($timestamp) {
			$this->setTimestamp($timestamp);
			$this->setDuration($duration);
		} elseif ($pollOptionText) {
			$this->setPollOptionText($pollOptionText);
			if ($order > 0) {
				$this->setOrder($order);
			}
		} else {
			throw new InsufficientAttributesException('Option must have a value');
		}

		$this->syncOption();
	}

	public function shiftOption(DateTimeZone $timeZone, int $step, string $unit): void {
		$from = (new DateTime())
			->setTimestamp($this->getTimestamp())
			->setTimezone($timeZone)
			->modify($step . ' ' . $unit);
		$to = (new DateTime())
			->setTimestamp($this->getTimestamp() + $this->getDuration())
			->setTimezone($timeZone)
			->modify($step . ' ' . $unit);
		$this->setTimestamp($from->getTimestamp());
		$this->setDuration($to->getTimestamp() - $from->getTimestamp());
		$this->syncOption();
	}

	/**
	 * Syncs pollOptionText and order according to timestamp and duration if timestamp > 0
	 * Updates hash
	 */
	public function syncOption(): void {
		// make sure, pollOptionText matches timestamp and duration
		// timestamp gets precedence over pollOptionText
		if ($this->getTimestamp()) {
			$this->setOrder($this->getTimestamp());

			if ($this->duration) {
				$this->setPollOptionText(date('c', $this->getTimestamp()) . ' - ' . date('c', $this->getTimestamp() + $this->getDuration()));
			} else {
				$this->setPollOptionText(date('c', $this->timestamp));
			}
		}

		// update hash
		$this->updateHash();
	}

	private function updateHash(): void {
		$this->setPollOptionHash(hash('md5', $this->getPollId() . $this->getPollOptionText() . $this->getTimestamp()));
	}

	public function getPollOptionText(): string {
		if ($this->getTimestamp() === 0) {
			return htmlspecialchars_decode($this->pollOptionText);
		}

		// return timespan, if duration is set
		if ($this->getDuration()) {
			return date('c', $this->getTimestamp()) . ' - ' . date('c', $this->getTimestamp() + $this->getDuration());
		}

		// else return formatted timestamp
		return date('c', $this->getTimestamp());
	}

	/** @psalm-suppress PossiblyUnusedMethod */
	public function getPollOptionTextEnd(): string {
		if ($this->getTimestamp()) {
			return date('c', $this->getTimestamp() + $this->getDuration());
		}
		return htmlspecialchars_decode($this->pollOptionText);
	}

	public function getPollOptionTextStart(): string {
		if ($this->getTimestamp()) {
			return date('c', $this->getTimestamp());
		}
		return htmlspecialchars_decode($this->pollOptionText);
	}

	public function getIsLocked(): bool {
		return $this->getDeleted()
			|| ($this->getUserVoteAnswer() !== Vote::VOTE_YES
			&& $this->getUserVoteAnswer() !== Vote::VOTE_EVENTUALLY
			&& ($this->getIsLockedByOptionLimit() || $this->getIsLockedByVotesLimit()));
	}

	/**
	 * @return bool Returns true, if this option is locked by the optionLimit and the user has not voted yes
	 */
	public function getIsLockedByOptionLimit(): bool {
		return $this->getOptionLimit() && $this->getVotesYes() >= $this->getOptionLimit() && $this->getUserVoteAnswer() !== Vote::VOTE_YES;
	}

	public function getIsLockedByVotesLimit(): bool {
		// IF a vote limit is set
		// AND the user did not vote yes for this option
		// AND the count of yes votes of the current user is EQUAL OR GREATER THAN the vote limit
		// return true (locked option for current user)
		return $this->getVoteLimit() && $this->getUserCountYesVotes() >= $this->getVoteLimit();
	}

	public function getOrder(): int {
		if ($this->timestamp) {
			return $this->getTimestamp();
		}
		return $this->order;
	}

	// alias of getOwner()
	public function getUserId(): string {
		return $this->getOwner();
	}

	public function getDateStringLocalized(DateTimeZone $timeZone, IL10N $l10n): string {
		$mutableFrom = DateTime::createFromImmutable($this->getDateObjectFrom($timeZone));
		$mutableTo = DateTime::createFromImmutable($this->getDateObjectTo($timeZone));
		$dayLongSecond = new DateInterval('PT1S');
		$sameDay = $this->getDateObjectFrom($timeZone)->format('Y-m-d') === $this->getDateObjectTo($timeZone)->format('Y-m-d');

		// If duration is zero, the option represents a moment with day and time
		if ($this->getDuration() === 0) {
			return (string)$l10n->l('datetime', $mutableFrom);
		}

		$dateTimeFrom = $l10n->l('datetime', $mutableFrom);
		$dateTimeTo = $l10n->l('datetime', $mutableTo);

		// If the option spans over on or more whole days, the option represents only the days without time
		// adjust the end by substracting a second, to represent the last moment at the day and not the first moment of the following day
		// which is calculated by adding the duration
		if ($this->getDaylong($timeZone)) {
			$dateTimeFrom = $l10n->l('date', $mutableFrom);
			$dateTimeTo = $l10n->l('date', $mutableTo->sub($dayLongSecond));
			// if start and end day are identiacal, just return the start day
			if ($dateTimeFrom === $dateTimeTo) {
				return (string)$dateTimeFrom;
			}
		}

		if ($sameDay) {
			$dateTimeTo = $l10n->l('time', $mutableTo);
		}

		return (string)$dateTimeFrom . ' - ' . (string)$dateTimeTo;
	}

	/**
	 * Check, if the date option spans one or more whole days (from 00:00 to 24:00)
	 */
	private function getDaylong(DateTimeZone $timeZone): bool {
		$from = $this->getDateObjectFrom($timeZone);
		$to = $this->getDateObjectTo($timeZone);
		$dateInterval = $from->diff($to);

		if (
			$this->getDuration() > 0
			&& $from->format('H') === '00'
			&& $dateInterval->h + $dateInterval->i + $dateInterval->h === 0
		) {
			return true;
		}
		return false;
	}

	private function getDateObjectFrom(DateTimeZone $timeZone): DateTimeImmutable {
		$dateTime = (new DateTimeImmutable())->setTimestamp($this->getTimestamp());
		return $dateTime->setTimezone($timeZone);
	}

	private function getDateObjectTo(DateTimeZone $timeZone): DateTimeImmutable {
		$dateTime = (new DateTimeImmutable())->setTimestamp($this->getTimestamp() + $this->getDuration());
		return $dateTime->setTimezone($timeZone);
	}
}
