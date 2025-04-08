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
	 */
	public function __construct(
		protected string $text,
		protected int $timestamp,
		protected int $duration = 0,
		protected int $order = 0,
	) {
	}

	public function jsonSerialize(): array {
		return [
			'text' => $this->text,
			'timestamp' => $this->timestamp,
			'duration' => $this->duration,
			'order' => $this->order,
		];
	}

	public function getText(): string {
		return $this->text;
	}

	public function getTimestamp(): int {
		return $this->timestamp;
	}

	public function getDuration(): int {
		return $this->duration;
	}

	public function getOrder(): int {
		return $this->order;
	}

	public function setOrder(int $order): void {
		$this->order = $order;
	}

	public static function fromArray(array $option): SimpleOption {
		return new SimpleOption(
			$option['text'],
			$option['timestamp'],
			$option['duration'],
			$option['order'] ?? 0,
		);
	}
}
