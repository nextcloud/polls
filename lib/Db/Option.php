<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Helper\DateHelper;
use OCA\Polls\Helper\Hash;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\Model\UserBase;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method int getConfirmed()
 * @method void setConfirmed(int $value)
 * @method int getOrder()
 * @method void setOrder(int $value)
 * @method int getTimestamp()
 * @method void setTimestamp(int $value)
 * @method int getDuration()
 * @method void setDuration(int $value)
 * @method ?string getIsoTimestamp()
 * @method void setIsoTimestamp(?string $value)
 * @method ?string getIsoDuration()
 * @method void setIsoDuration(?string $value)
 * @method string getOwner()
 * @method void setPollOptionText(?string $value)
 * @method string getPollOptionText()
 * @method void setOwner(string $value)
 * @method int getPollId()
 * @method void setPollId(int $value)
 * @method string getPollOptionHash()
 * @method void setPollOptionHash(string $value)
 * @method int getReleased()
 * @method void setReleased(int $value)
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
	protected ?DateInterval $optionInterval = null;
	protected ?DateTimeImmutable $optionDateTimeImmutable = null;

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

		//deprecated attributes
	}

	/**
	 * Clone this option and reset the id, pollId, deleted
	 * and confirmed status, as well as the owner and the hash.
	 *
	 * @return void
	 */
	public function __clone() {
		$this->setPollId(0);
		$this->setDeleted(0);
		$this->setConfirmed(0);
		$this->setOwner('');
		$this->updateHash();
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
	 * Get the owner user object. Returns null if the owner is not set or if the owner does not exist.
	 *
	 * @return UserBase|null The owner user object or null if not set or does not exist
	 */
	public function getOwnerUser(): ?UserBase {
		if ($this->getOwner() === '') {
			return null;
		}
		return parent::getUser();
	}

	/**
	 * alias of getOwner()
	 *
	 * @return string The user ID of the option's owner
	 */
	public function getUserId(): string {
		return $this->getOwner();
	}

	/**
	 * Get the option's text. This will decode any HTML entities in the text before returning it.
	 *
	 * @return string The text of the option
	 */
	public function getPollOptionText(): string {
		return htmlspecialchars_decode($this->pollOptionText);
	}

	/**
	 * Update the pollOptionHash based on the current pollId and pollOptionText
	 */
	public function updateHash(): void {
		$this->setPollOptionHash(Hash::getOptionHash(
			$this->pollId,
			$this->pollOptionText
		));
	}

	/**
	 * Get the option's date and time as a DateTimeImmutable object.
	 * Returns null if null or empty
	 * @return DateTimeImmutable|null
	 */
	public function getDateTime(): ?DateTimeImmutable {
		return DateHelper::getDateTimeImmutable($this->timestamp);
	}

	/**
	 * Get the option's duration as a DateInterval object.
	 * Returns null if no valid duration is set.
	 */
	public function getInterval(): ?DateInterval {
		$duration = $this->getIsoDuration();
		if ($duration !== null && $duration !== '') {
			return DateHelper::getDateInterval($duration);
		}
		$duration = $this->getDuration();
		if ($duration > 0) {
			return DateHelper::getDateInterval($duration);
		}
		return null;
	}

	/**
	 * Set the option's date and time using a DateTimeImmutable object.
	 * This will update the isoTimestamp, timestamp and pollOptionText, and sync the option.
	 *
	 * @param DateTimeImmutable|null $dateTime The date and time to set for the option, or null to unset the date and time
	 */
	public function setDateTime(?DateTimeImmutable $dateTime): void {
		if ($dateTime) {
			$this->setTimestamp($dateTime->getTimestamp());
			$this->setOrder($dateTime->getTimestamp());
			$this->setIsoTimestamp(DateHelper::getDateTimeIso($dateTime));
			$this->setPollOptionText(DateHelper::getDateString($dateTime, $this->getInterval()));
		} else {
			$this->setIsoTimestamp(null);
			$this->setTimestamp(0);
		}
		$this->updateHash();
	}

	/**
	 * Set the option's duration using a DateInterval object.
	 * This will update the isoDuration and sync the option.
	 *
	 * @param DateInterval|null $optionInterval The duration to set for the option, or null to unset the duration
	 * @throws InsufficientAttributesException if the option does not have a valid timestamp when trying to set an interval
	 */
	public function setInterval(?DateInterval $optionInterval): void {
		if ($optionInterval) {
			$dateTime = $this->getDateTime();

			if (!$dateTime) {
				throw new InsufficientAttributesException('Setting an interval requires a valid timestamp on the option');
			}

			$this->setIsoDuration(DateHelper::dateIntervalToIso($optionInterval), true);
			$this->setDuration(DateHelper::dateIntervalToSeconds($optionInterval));
			$this->setPollOptionText($this->getOptionTextFromTimestamp());
			$this->setPollOptionText(DateHelper::getDateString($dateTime, $optionInterval));
		} else {
			$this->setIsoDuration(null);
			$this->setDuration(0);
		}

		$this->updateHash();
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
		$dateTime = $this->getDateTime();
		if ($dateTime) {
			$shifted = $dateTime
				->setTimeZone($timeZone)
				->modify($step . ' ' . $unit);
			if (!$shifted) {
				throw new InsufficientAttributesException('Failed to shift option, invalid step or unit');
			}
			$this->setDateTime($shifted);
		}

		return $this;
	}

	private function getOptionTextFromTimestamp(): string {
		$datetime = $this->getDatetime();
		if (!$datetime) {
			throw new InsufficientAttributesException('Option has no valid timestamp');
		}

		return DateHelper::getDateString($datetime, $this->getInterval());
	}

	// only used in migration, to set the option text to the timestamp for old options
	public function getPollOptionTextStart(): string {
		if (!$this->optionDateTimeImmutable) {
			return htmlspecialchars_decode($this->pollOptionText);
		}
		return $this->optionDateTimeImmutable->format(DateTimeInterface::ATOM);
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

	public function getOrder(): int {
		// if ($this->optionDateTimeImmutable) {
		// 	return $this->optionDateTimeImmutable->getTimestamp();
		// }
		return $this->order;
	}

	/**
	 * Set option's text, date and time, and duration from a SimpleOption instance.
	 * This will also sync the option to update the order and hash.
	 * @param SimpleOption $simpleOption The SimpleOption instance to set the option's attributes from
	 * @param string $pollTypeHint The type of the poll to determine which attributes to set. If empty, all attributes will be set.
	 * @return Option Returns the Option instance for method chaining
	 */
	public function setFromSimpleOption(SimpleOption $simpleOption, string $pollTypeHint = ''): self {
		if ($pollTypeHint === Poll::TYPE_DATE) {
			$this->setDateTime($simpleOption->getDateTime());
			$this->setInterval($simpleOption->getInterval());
		} elseif ($pollTypeHint === Poll::TYPE_TEXT) {
			$this->setPollOptionText($simpleOption->getText() ?? '');
		} else {
			// Without poll type hint, set all properties
			$this->setPollOptionText($simpleOption->getText() ?? '');
			$this->setDateTime($simpleOption->getDateTime());
			$this->setInterval($simpleOption->getInterval());
		}
		return $this;
	}
}
