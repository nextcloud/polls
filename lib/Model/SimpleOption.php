<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use DateInterval;
use DateTimeImmutable;
use JsonSerializable;
use OCA\Polls\Helper\DateHelper;

/**
 * @psalm-type SimpleOptionsArray = array{
 * 	 text?: string,
 * 	 timestamp?: int,
 * 	 isoDuration?:? string,
 * 	 duration?: int,
 * 	 isoTimestamp?: ?string,
 * }
 */
class SimpleOption implements JsonSerializable {
	protected ?string $text = null;
	protected ?DateTimeImmutable $dateTime = null;
	protected ?DateInterval $interval = null;

	public function jsonSerialize(): array {
		return [
			'text' => $this->getText(),
			'isoDuration' => $this->getIsoDuration(),
			'timestamp' => $this->getDateTime()?->getTimestamp() ?? 0,
		];
	}

	/**
	 * Set the timestamp of the option as DateTimeImmutable, int, string or null
	 *
	 * @param null|int|string|DateTimeImmutable $dateTime The timestamp for the datepoll
	 * @return self Return the SimpleOption instance for method chaining
	 */
	public function setDateTime(null|int|string|DateTimeImmutable $dateTime): self {
		$this->dateTime = DateHelper::getDateTimeImmutable($dateTime);
		return $this;
	}

	/**
	 * Set the duration of the option as DateInterval, int, string or null
	 *
	 * @param null|int|string|DateInterval $duration The duration of the option
	 * @return self Return the SimpleOption instance for method chaining
	 */
	public function setInterval(null|int|string|DateInterval $duration): self {
		$this->interval = DateHelper::getDateInterval($duration);
		return $this;
	}

	/**
	 * Set the text of the option
	 * @param string $text The text of the option
	 * @return self Return the SimpleOption instance for method chaining
	 */
	public function setText(string $text): self {
		$this->text = $text;
		return $this;
	}

	/**
	 * Get the timestamp of the option as DateTimeImmutable or null if not set
	 * @return DateTimeImmutable|null
	 */
	public function getDateTime(): ?DateTimeImmutable {
		return $this->dateTime;
	}

	/**
	 * Get the duration of the option as DateInterval or null if not set
	 * @return DateInterval|null
	 */
	public function getInterval(): ?DateInterval {
		return $this->interval;
	}

	/**
	 * Get the duration of the option as ISO 8601 string or null if not set
	 * @return string|null
	 */
	public function getIsoDuration(): ?string {
		return DateHelper::dateIntervalToIso($this->interval);
	}

	/**
	 * Get the text of the option
	 */
	public function getText(): ?string {
		return $this->text;
	}

	/**
	 * Create a SimpleOption from an array
	 * Note: timestamp takes precedence over isoTimestamp,
	 * isoDuration takes precedence over duration
	 *
	 * @param SimpleOptionsArray $option The array containing the option data
	 * @return SimpleOption The created SimpleOption
	 */
	public static function fromArray(array $option): self {
		$simpleOption = new self();

		if (isset($option['text'])) {
			$simpleOption->text = $option['text'];
		}
		if (isset($option['timestamp'])) {
			$simpleOption->setDateTime($option['timestamp']);
		} elseif (isset($option['isoTimestamp'])) {
			$simpleOption->setDateTime($option['isoTimestamp']);
		}

		if (isset($option['isoDuration'])) {
			$simpleOption->setInterval($option['isoDuration']);
		} elseif (isset($option['duration'])) {
			$simpleOption->setInterval($option['duration']);
		}

		return $simpleOption;
	}
}
