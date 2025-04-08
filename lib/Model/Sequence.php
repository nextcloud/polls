<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use DateTime;
use DateTimeZone;
use JsonSerializable;

class Sequence implements JsonSerializable {
	public const REPETITION_YEAR = 'year';
	public const REPETITION_MONTH = 'month';
	public const REPETITION_WEEK = 'week';
	public const REPETITION_DAY = 'day';
	public const REPETITION_HOUR = 'hour';
	public const REPETITION_MINUTE = 'minute';

	protected DateTimeZone $timeZone;
	protected ?int $baseTimeStamp = null;
	/**
	 * @param SequenceUnit $unit The unit of time for the sequence
	 * @param int $stepWidth The step width for the sequence according to the unit
	 * @param int $repetitions The number of repetitions for the sequence
	 */
	public function __construct(
		protected SequenceUnit $unit,
		protected int $stepWidth,
		protected int $repetitions,
	) {
		$this->timeZone = new DateTimeZone(date_default_timezone_get());
	}

	public function jsonSerialize(): array {
		return [
			'unit' => $this->unit,
			'stepWidth' => $this->stepWidth,
			'repetitions' => $this->repetitions,
		];
	}

	public function getUnit(): SequenceUnit {
		return $this->unit;
	}

	public function getStepWidth(): int {
		return $this->stepWidth;
	}

	public function getRepetitions(): int {
		return $this->repetitions;
	}

	public function setTimeZone(DateTimeZone $timeZone): void {
		$this->timeZone = $timeZone;
	}

	private function getTimeZone(): DateTimeZone {
		return $this->timeZone;
	}

	public function setBaseTimeStamp(int $baseTimeStamp): void {
		$this->baseTimeStamp = $baseTimeStamp;
	}

	private function getBaseTimeStamp(): int {
		if ($this->baseTimeStamp === null) {
			throw new \Exception('Base timestamp not set');
		}
		return $this->baseTimeStamp;
	}

	public function getOccurence(int $index): int {
		return ((new DateTime())
			->setTimestamp($this->getBaseTimeStamp())
			->setTimezone($this->getTimeZone())
			->modify($this->getStepWidth() * $index . ' ' . $this->getUnit()->getId()))
			->getTimestamp();
	}

	/**
	 * @param array $sequence The sequence to create
	 */
	public static function fromArray(?array $sequence): Sequence {
		if ($sequence === null) {
			return new Sequence(new SequenceUnit(SequenceUnit::REPETITION_DAY), 1, 0);
		}
		return new Sequence(
			new SequenceUnit($sequence['unit']['id'] ?? SequenceUnit::REPETITION_DAY),
			$sequence['stepWidth'] ?? 1,
			$sequence['repetitions'] ?? 0,
		);
	}
}
