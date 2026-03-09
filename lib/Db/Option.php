<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use DateTimeZone;
use JsonSerializable;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Helper\Hash;
use OCA\Polls\Model\DateInterval;
use OCA\Polls\Model\DateTimeImmutable;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\Model\UserBase;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method int getConfirmed()
 * @method void setConfirmed(int $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getPollId()
 * @method int getReleased()
 * @method void setReleased(int $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 *
 * No magic getters, getters are overwritten for special handling of timestamp and option text
 * @method void setOrder(int $value)
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
 *
 * Protected setters for pollId, pollOptionText, setPollOptionHash, timestamp
 * and duration, as they should not be set directly, but through setPoll(),
 * setText(), setDateTime() and setInterval()
 *
 * protected setPollId(int $value)
 * protected setTimestamp(int $value)
 * protected setDuration(int $value)
 * protected setIsoTimestamp(?string $value)
 * protected setIsoDuration(?string $value)
 * protected setPollOptionText(string $value)
 * protected setPollOptionHash(string $value)
 *
 * Nextcloud generates them automagically through the entity class.
 *
 */
class Option extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_options';

	// schema columns
	public $id = null;
	protected int $pollId = 0;
	protected string $pollOptionText = '';
	protected string $pollOptionHash = '';
	protected int $timestamp = 0;
	protected int $duration = 0;
	protected int $order = 0;
	protected ?string $isoTimestamp = null;
	protected ?string $isoDuration = null;
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

	protected DateInterval $optionInterval;
	protected DateTimeImmutable $optionDateTimeImmutable;

	// deprecated columns

	public function __construct() {
		$this->addType('released', 'integer');
		$this->addType('pollId', 'integer');
		$this->addType('order', 'integer');
		$this->addType('timestamp', 'integer');
		$this->addType('duration', 'integer');
		$this->addType('confirmed', 'integer');
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
	 * Build a fresh Option entity pre-populated from this option's data,
	 * assigned to $toPollId. The fresh entity's id is never set, so
	 * QBMapper::insert() relies on the DB autoincrement to assign a fresh id.
	 */
	public function createClone(int $toPollId): self {
		$clone = new self();
		$clone->setPoll($toPollId);
		if ($this->getTimestamp() !== 0) {
			$clone->setDateTime($this->getDateTime());
			$clone->setInterval($this->getInterval());
		} else {
			$clone->setText($this->getPollOptionText());
			$clone->setOrder($this->order);
		}
		return $clone;
	}

	/**
	 * Return the option data as an associative array for JSON serialization.
	 *
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
			'duration' => $this->getDuration(),
			'isoTimestamp' => $this->getIsoTimestamp(),
			'isoDuration' => $this->getIsoDuration(),
			'deleted' => $this->getDeleted(),
			'order' => $this->getOrder(),
			'confirmed' => $this->getConfirmed(),
			'locked' => $this->getIsLocked(),
			'hash' => $this->getPollOptionHash(),
			'isOwner' => $this->getCurrentUserIsEntityUser(),
			'votes' => $this->getVotes(),
			'owner' => $this->getOwnerUser(),
		];
	}

	/**
	 * Get the vote status for this option
	 *
	 * @return array An associative array containing the counts of 'yes', 'no', 'maybe' votes, the total count of votes and the current user's vote answer.
	 */
	private function getVotes(): array {
		return [
			'no' => $this->getVotesNo() * $this->getShowResults(),
			'yes' => $this->getVotesYes() * $this->getShowResults(),
			'maybe' => $this->getVotesMaybe() * $this->getShowResults(),
			'count' => $this->getCountOptionVotes() * $this->getShowResults(),
			'currentUser' => $this->getUserVoteAnswer(),
		];
	}

	/**
	 * alias of getOwner()
	 *
	 * @return string The user ID of the option's owner
	 */
	public function getUserId(): string {
		return $this->getOwner();
	}

	/***************************************************
	 * special getters
	 ***************************************************/
	/**
	 * Get the owner user object. Returns null if the owner is not set or if the owner does not exist.
	 *
	 * @return UserBase|null The owner user object or null if not set or does not exist
	 */
	public function getOwnerUser(): ?UserBase {
		if ($this->getUserId() === '') {
			return null;
		}
		return parent::getUser();
	}

	public function getPollOptionHash(): string {
		return Hash::getOptionHash(
			$this->getPollId(),
			$this->getPollOptionText(),
		);
	}

	public function getPollOptionHashInDB(): string {
		return $this->pollOptionHash;
	}
	/**
	 * Get the order of the option. If the option has a valid timestamp,
	 * the order will be determined by the timestamp to ensure correct
	 * ordering of options based on their date and time.
	 *
	 * @return int The order of the option, determined by the timestamp if valid, otherwise the order value from the database
	 */
	public function getOrder(): int {
		return $this->getTimestamp() !== 0
			? $this->getTimestamp()
			: $this->order;
	}

	/**
	 * Get the text of the option. If the option has a valid timestamp,
	 * the text will be generated from the timestamp and duration
	 */
	public function getPollOptionText(): string {
		return $this->getTimestamp() !== 0
			? $this->getDateTimePollOptionText()
			: htmlspecialchars_decode($this->pollOptionText);
	}

	/***************************************************
	 * special setters
	 ***************************************************/

	/**
	 * Set the poll ID for the option. Also updates the poll option hash to ensure
	 */
	public function setPoll(int $pollId): void {
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setPollId($pollId);
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setPollOptionHash($this->getPollOptionHash());
	}

	/**
	 * Set the text of the option. Also updates the poll option hash to ensure
	 * it is always in sync with the text and poll ID.
	 *
	 * @param string $text The text to set for the option
	 */
	public function setText(string $text): void {
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setPollOptionText($text);
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setPollOptionHash($this->getPollOptionHash());
	}
	/***************************************************
	 * date and time getters
	 ***************************************************/

	/**
	 * Get the option's date and time as a DateTimeImmutable object.
	 * Returns the date and time based on the isoTimestamp if it is set,
	 * otherwise it falls back to the timestamp.
	 *
	 * @return DateTimeImmutable
	 */
	public function getDateTime(): DateTimeImmutable {
		if ($this->isoTimestamp !== null && $this->isoTimestamp !== '') {
			return new DateTimeImmutable($this->isoTimestamp);
		}
		return new DateTimeImmutable($this->timestamp);
	}

	/**
	 * Get the option's timestamp as an integer.
	 * Always returns the timestamp from getDateTime()
	 *
	 * @return int The timestamp of the option as Unix timestamp in seconds
	 */
	public function getTimestamp(): int {
		return $this->getDateTime()->getTimestamp();
	}

	/**
	 * Get the option's date and time as an ISO 8601 string.
	 * Always returns the ISO timestamp from getDateTime()
	 *
	 * @return string|null The ISO 8601 timestamp string of the option, or null if no valid date and time is set
	 */
	public function getIsoTimestamp(): ?string {
		return $this->getDateTime()->getISO();
	}

	/***************************************************
	 * date and time setters
	 ***************************************************/

	/**
	 * Set the option's date and time from a DateTimeImmutable object.
	 * Also updates the timestamp, isoTimestamp, order, and pollOptionText
	 * based on the new date and time.
	 *
	 * Prefer this method over setTimestamp() and setIsoTimestamp()
	 *
	 * @param DateTimeImmutable $dateTime The DateTimeImmutable object to set the option's date and time from
	 * @return void
	 */
	public function setDateTime(DateTimeImmutable $dateTime): void {
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setTimestamp($dateTime->getTimestamp());
		$this->setOrder($dateTime->getTimestamp());
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setIsoTimestamp($dateTime->getISO());
		$this->setText($this->getDateTimePollOptionText());
	}

	/***************************************************
	 * Duration getters
	 ***************************************************/

	/**
	 * Get the option's duration as a DateInterval object.
	 * Returns the duration based on the isoDuration if it is set,
	 * otherwise it falls back to the duration in seconds.
	 *
	 * @return DateInterval The duration of the option as a DateInterval object, or null if no valid duration is set
	 */
	public function getInterval(): DateInterval {
		if ($this->isoDuration !== null && $this->isoDuration !== '') {
			return new DateInterval($this->isoDuration);
		}
		return new DateInterval($this->duration);
	}

	/**
	 * Get the option's duration in seconds.
	 * Always returns the duration in seconds from getInterval()
	 *
	 * @return int The duration of the option in seconds
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function getDuration(): int {
		return $this->getInterval()->getSeconds();
	}

	/**
	 * Get the option's duration as an ISO 8601 string.
	 * Always returns the ISO duration from getInterval()
	 *
	 * @return string|null The ISO 8601 duration string of the option, or null if no valid duration is set
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function getIsoDuration(): ?string {
		return $this->getInterval()->getISO();
	}

	/***************************************************
	 * Duration setters
	 ***************************************************/

	/**
	 * Set the option's duration from a DateInterval object.
	 * Also updates the duration in seconds, isoDuration, and pollOptionText
	 * based on the new duration.
	 *
	 * Prefer this method over setDuration() and setIsoDuration()
	 *
	 * @param DateInterval $optionInterval The DateInterval object to set the option's duration from
	 * @return void
	 */
	public function setInterval(DateInterval $optionInterval): void {
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setDuration($optionInterval->getSeconds());
		/** @psalm-suppress UndefinedMagicMethod */
		$this->setIsoDuration($optionInterval->getISO());
		$this->setText($this->getDateTimePollOptionText());
	}

	/***************************************************
	 * misc private and public methods and helpers
	 ***************************************************/

	private function getDateTimePollOptionText(): string {
		$isoTimestamp = $this->getIsoTimestamp();
		if ($isoTimestamp === null) {
			return htmlspecialchars_decode($this->pollOptionText);
		}

		if ($this->getInterval()->isZeroDuration()) {
			return $isoTimestamp;
		}

		return $isoTimestamp
		. ' - '
		. ($this->getDateTime()->add($this->getInterval())->getISO() ?? '');
	}

	/**
	 * Shift the option's date and time by a specified amount of time.
	 *
	 * @param int $step The amount of time to shift the option by
	 * @param string $unit The unit of time to shift by (e.g. 'day', 'hour', 'minute')
	 * @param DateTimeZone $timeZone The time zone to use for the date and time calculations
	 * @return Option Returns the Option instance for method chaining
	 */
	public function shiftOption(int $step, string $unit, DateTimeZone $timeZone): self {
		$shifted = $this->getDateTime()
			->setTimeZone($timeZone)
			->modify($step . ' ' . $unit);
		if (!$shifted) {
			throw new InsufficientAttributesException('Failed to shift option, invalid step or unit');
		}
		$this->setDateTime($shifted);

		return $this;
	}

	/**
	 * Get the option's text for old options that only have a timestamp. If
	 * the option has a valid timestamp, the text will be returned as an
	 * ISO string of the timestamp. Otherwise, the regular option text will be
	 * returned.
	 *
	 * This is used in the migration to set the option text for old options that
	 * only have a timestamp, as the option text is now required and cannot be
	 * empty. This ensures that old options will have a valid option text after
	 * the migration, even if they only had a timestamp before. The option text
	 * will be set to the ISO string of the timestamp to ensure it is unique and
	 * does not conflict with any existing option texts.
	 *
	 * @return string The text of the option, either as an ISO string of the timestamp or the regular option text
	 */
	public function getPollOptionTextStart(): string {
		return $this->getIsoTimestamp() ?? htmlspecialchars_decode($this->pollOptionText);
	}

	/**
	 * Check if the option is locked for the current user. An option is considered locked if:
	 * - It is marked as deleted, or
	 * - The user did not vote yes for this option, and either the option limit or the vote limit has been reached.
	 *
	 * @return bool Returns true if the option is locked for the current user, false otherwise
	 */
	public function getIsLocked(): bool {
		return $this->getDeleted()
			|| ($this->getUserVoteAnswer() !== Vote::VOTE_YES
			&& $this->getUserVoteAnswer() !== Vote::VOTE_EVENTUALLY
			&& ($this->getIsLockedByOptionLimit() || $this->getIsLockedByVotesLimit()));
	}

	/**
	 * Check if this option is locked by the optionLimit:
	 * If an option limit is set
	 * and the user did not vote yes for this option
	 * and the count of yes votes for this option is EQUAL OR GREATER THAN the option limit,
	 * the option is locked for the current user
	 *
	 * @return bool Returns true, if this option is locked by the optionLimit and the user has not voted yes
	 */
	public function getIsLockedByOptionLimit(): bool {
		return $this->getOptionLimit() && $this->getVotesYes() >= $this->getOptionLimit() && $this->getUserVoteAnswer() !== Vote::VOTE_YES;
	}

	/**
	 * Check if this option is locked by the voteLimit:
	 * If a vote limit is set
	 * and the user did not vote yes for this option
	 * and the count of yes votes of the current user is EQUAL OR GREATER THAN the vote limit,
	 * the option is locked for the current user
	 *
	 * @return bool Returns true, if this option is locked by the voteLimit
	 */
	public function getIsLockedByVotesLimit(): bool {
		return $this->getVoteLimit() && $this->getUserCountYesVotes() >= $this->getVoteLimit();
	}

	/**
	 * Set option's text, date and time, and duration from a SimpleOption instance.
	 * This will also sync the option to update the order and hash.
	 *
	 * @param SimpleOption $simpleOption The SimpleOption instance to set the option's attributes from
	 * @param string $pollTypeHint The type of the poll to determine which attributes to set. If empty, all attributes will be set.
	 * @return Option Returns the Option instance for method chaining
	 */
	public function setFromSimpleOption(SimpleOption $simpleOption, string $pollTypeHint = ''): self {
		if ($pollTypeHint === Poll::TYPE_DATE) {
			$this->setDateTime($simpleOption->getDateTime());
			$this->setInterval($simpleOption->getInterval());
		} elseif ($pollTypeHint === Poll::TYPE_TEXT) {
			$this->setText($simpleOption->getText() ?? '');
		} else {
			// Without poll type hint, set all properties
			$this->setText($simpleOption->getText() ?? '');
			$this->setDateTime($simpleOption->getDateTime());
			$this->setInterval($simpleOption->getInterval());
		}
		return $this;
	}
}
