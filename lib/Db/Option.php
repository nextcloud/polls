<?php
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
 * @method int getReleased()
 * @method void setReleased(int $value)
 * @method int getTimestamp()
 * @method void setTimestamp(integer $value)
 */
class Option extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_options';

	/** @var int $pollId */
	protected $pollId = 0;

	/** @var string $owner */
	protected $owner = '';

	/** @var int $released */
	protected $released = 0;

	/** @var string $pollOptionText */
	protected $pollOptionText = '';

	/** @var int $timestamp */
	protected $timestamp = 0;

	/** @var int $order */
	protected $order = 0;

	/** @var int $confirmed */
	protected $confirmed = 0;

	/** @var int $duration */
	protected $duration = 0;

	// public variables, not in the db
	/** @var int $rank */
	public $rank = 0;

	/** @var int $yes */
	public $yes = 0;

	/** @var int $no */
	public $no = 0;

	/** @var int $maybe */
	public $maybe = 0;

	/** @var int $realNo */
	public $realNo = 0;

	/** @var int $votes */
	public $votes = 0;

	/** @var bool $isBookedUp */
	public $isBookedUp = false;

	public function __construct() {
		$this->addType('released', 'int');
		$this->addType('pollId', 'int');
		$this->addType('timestamp', 'int');
		$this->addType('order', 'int');
		$this->addType('confirmed', 'int');
		$this->addType('duration', 'int');
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
			'order' => $this->getOrder(),
			'confirmed' => $this->getConfirmed(),
			'duration' => $this->getDuration(),
			'computed' => [
				'rank' => $this->rank,
				'no' => $this->no,
				'yes' => $this->yes,
				'maybe' => $this->maybe,
				'realNo' => $this->realNo,
				'votes' => $this->votes,
				'isBookedUp' => $this->isBookedUp,
			],
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

	public function getOrder(): int {
		if ($this->timestamp) {
			return $this->getTimestamp();
		}
		return $this->order;
	}

	// alias of getOwner()
	public function getUserId() : ?string {
		return $this->getOwner();
	}

	// alias of setOwner($value)
	public function setUserId(string $userId) : void {
		$this->setOwner($userId);
	}

	public function getDateStringLocalized(DateTimeZone $timeZone, IL10N $l10n) {
		$mutableFrom = DateTime::createFromImmutable($this->getDateObjectFrom($timeZone));
		$mutableTo = DateTime::createFromImmutable($this->getDateObjectTo($timeZone));
		$dayLongSecond = new DateInterval('PT1S');
		$sameDay = $this->getDateObjectFrom($timeZone)->format('Y-m-d') === $this->getDateObjectTo($timeZone)->format('Y-m-d');

		// If duration is zero, the option represents a moment with day and time
		if ($this->getDuration() === 0) {
			return $l10n->l('datetime', $mutableFrom);
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
				return $dateTimeFrom;
			}
		}

		if ($sameDay) {
			$dateTimeTo = $dateTimeTo = $l10n->l('time', $mutableTo);
		}

		return $dateTimeFrom . ' - ' . $dateTimeTo;
	}

	// private function getOwnerIsNoUser(): bool {
	// 	return !$this->userManager->get($this->getOwner()) instanceof IUser;
	// }

	/**
	 * Check, if the date option spans one or more whole days (from 00:00 to 24:00)
	 */
	private function getDaylong(DateTimeZone $timeZone = null): bool {
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
