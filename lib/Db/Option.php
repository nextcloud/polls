<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Db;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use OCP\IL10N;

/**
 * @method int getId()
 * @method void setId(integer $value)
 * @method int getConfirmed()
 * @method void setConfirmed(integer $value)
 * @method int getDuration()
 * @method void setDuration(integer $value)
 * @method int getOrder()
 * @method void setOrder(integer $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getPollOptionText()
 * @method void setPollOptionText(string $value)
 * @method string getPollOptionHash()
 * @method void setPollOptionHash(string $value)
 * @method int getReleased()
 * @method void setReleased(int $value)
 * @method int getTimestamp()
 * @method void setTimestamp(integer $value)
 * @method int getDeleted()
 * @method void setDeleted(integer $value)
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
 */
class Option extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_options';

	public $id = null;
	protected int $pollId = 0;
	protected string $owner = '';
	protected int $released = 0;
	protected string $pollOptionText = '';
	protected string $pollOptionHash = '';
	protected int $timestamp = 0;
	protected int $order = 0;
	protected int $confirmed = 0;
	protected int $duration = 0;
	protected int $deleted = 0;

	// joined Attributes
	protected ?string $userVoteAnswer = '';
	protected int $optionLimit = 0;
	protected int $voteLimit = 0;
	protected int $userCountYesVotes = 0;
	protected int $countOptionVotes = 0;
	protected int $votesYes = 0;
	protected int $votesNo = 0;
	protected int $votesMaybe = 0;

	public function __construct() {
		$this->addType('released', 'int');
		$this->addType('pollId', 'int');
		$this->addType('timestamp', 'int');
		$this->addType('order', 'int');
		$this->addType('confirmed', 'int');
		$this->addType('duration', 'int');
		$this->addType('deleted', 'int');

		// joined Attributes
		$this->addType('optionLimit', 'int');
		$this->addType('voteLimit', 'int');
		$this->addType('userCountYesVotes', 'int');
		$this->addType('countOptionVotes', 'int');
		$this->addType('votesYes', 'int');
		$this->addType('votesNo', 'int');
		$this->addType('votesMaybe', 'int');
	}

	/**
	 * @return array
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
			'no' => $this->getVotesNo(),
			'yes' => $this->getVotesYes(),
			'maybe' => $this->getVotesMaybe(),
			'owner' => $this->getUser(),
		];
	}

	public function getPollOptionText(): string {
		if ($this->getTimestamp() && $this->getDuration()) {
			return date('c', $this->getTimestamp()) . ' - ' . date('c', $this->getTimestamp() + $this->getDuration());
		} elseif ($this->getTimestamp() && !$this->getDuration()) {
			return date('c', $this->getTimestamp());
		}
		return htmlspecialchars_decode($this->pollOptionText);
	}

	public function updatePollOptionText(): void {
		$this->setPollOptionText($this->getPollOptionText());
	}

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
			||($this->getUserVoteAnswer() !== Vote::VOTE_YES 
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
	public function getUserId(): ?string {
		return $this->getOwner();
	}

	// alias of setOwner($value)
	public function setUserId(string $userId): void {
		$this->setOwner($userId);
	}

	public function getDateStringLocalized(DateTimeZone $timeZone, IL10N $l10n): string {
		$mutableFrom = DateTime::createFromImmutable($this->getDateObjectFrom($timeZone));
		$mutableTo = DateTime::createFromImmutable($this->getDateObjectTo($timeZone));
		$dayLongSecond = new DateInterval('PT1S');
		$sameDay = $this->getDateObjectFrom($timeZone)->format('Y-m-d') === $this->getDateObjectTo($timeZone)->format('Y-m-d');

		// If duration is zero, the option represents a moment with day and time
		if ($this->getDuration() === 0) {
			return (string) $l10n->l('datetime', $mutableFrom);
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
				return (string) $dateTimeFrom;
			}
		}

		if ($sameDay) {
			$dateTimeTo = $l10n->l('time', $mutableTo);
		}

		return (string) $dateTimeFrom . ' - ' . (string) $dateTimeTo;
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
