<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use JsonSerializable;

class SequenceUnit implements JsonSerializable {
	public const REPETITION_YEAR = 'year';
	public const REPETITION_MONTH = 'month';
	public const REPETITION_WEEK = 'week';
	public const REPETITION_DAY = 'day';
	public const REPETITION_HOUR = 'hour';
	public const REPETITION_MINUTE = 'minute';

	public const SEQUENCE_UNIT = [
		self::REPETITION_YEAR => [
			'luxonUnit' => 'years',
			'name' => 'Year',
		],
		self::REPETITION_MONTH => [
			'luxonUnit' => 'months',
			'name' => 'Month',
		],
		self::REPETITION_WEEK => [
			'luxonUnit' => 'weeks',
			'name' => 'Week',
		],
		self::REPETITION_DAY => [
			'luxonUnit' => 'days',
			'name' => 'Day',
		],

		self::REPETITION_HOUR => [
			'luxonUnit' => 'hours',
			'name' => 'Hour',
		],
		self::REPETITION_MINUTE => [
			'luxonUnit' => 'minutes',
			'name' => 'Minute',
		],
	];
	/**
	 * @param string $id Id of the sequence unit
	 */
	public function __construct(
		protected string $id,
	) {
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'luxonUnit' => self::SEQUENCE_UNIT[$this->id]['luxonUnit'],
			'name' => self::SEQUENCE_UNIT[$this->id]['name'],
		];
	}
	public function getId(): string {
		return $this->id;
	}
}
