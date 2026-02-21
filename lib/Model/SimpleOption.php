<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use JsonSerializable;

class SimpleOption implements JsonSerializable {
	/**
	 * @param string $text The text of the option
	 * @param int $timestamp The timestamp for the datepoll
	 * @param int $duration The duration of the option
	 * @param int $order The order of the option
	 */
	public function __construct(
		protected string $text,
		protected ?int $timestamp,
		protected ?int $duration = 0,
		protected ?int $order = 0,
		protected string $isoTimestamp = '',
		protected string $isoDuration = '',
	) {
	}

	public function jsonSerialize(): array {
		return [
			'text' => $this->text,
			'timestamp' => $this->timestamp ?? 0,
			'duration' => $this->duration ?? 0,
			'order' => $this->order ?? 0,
			'isoDate' => $this->isoTimestamp,
			'isoDuration' => $this->isoDuration,
		];
	}

	public function getText(): string {
		return $this->text;
	}

	public function getTimestamp(): int {
		return $this->timestamp ?? 0;
	}

	public function getIsoTimestamp(): string {
		return $this->isoTimestamp;
	}

	public function getDuration(): int {
		return $this->duration ?? 0;
	}

	public function getIsoDuration(): string {
		return $this->isoDuration;
	}

	public function getOrder(): int {
		return $this->order ?? 0;
	}

	public function setOrder(int $order): void {
		$this->order = $order;
	}

	public static function fromArray(array $option): SimpleOption {
		return new SimpleOption(
			$option['text'],
			$option['timestamp'] ?? 0,
			$option['duration'] ?? 0,
			$option['order'] ?? 0,
			$option['isoTimestamp'] ?? '',
			$option['isoDuration'] ?? '',
		);
	}
}
