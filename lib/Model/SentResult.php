<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

class SentResult implements \JsonSerializable {
	public const INVALID_EMAIL_ADDRESS = 'InvalidMail';
	public const UNHANDELED_REASON = 'UnknownError';

	private array $sentMails = [];
	private array $abortedMails = [];

	public function AddSentMail(UserBase $recipient): void {
		array_push($this->sentMails, [
			'emailAddress' => $recipient->getEmailAddress(),
			'displayName' => $recipient->getDisplayName(),
		]);
	}

	public function AddAbortedMail(UserBase $recipient, string $reason = self::UNHANDELED_REASON): void {
		array_push($this->abortedMails, [
			'emailAddress' => $recipient->getEmailAddress(),
			'displayName' => $recipient->getDisplayName(),
			'reason' => $reason,
		]);
	}

	/** @psalm-suppress PossiblyUnusedMethod */
	public function jsonSerialize(): array {
		return	[
			'sentMails' => $this->sentMails,
			'abortedMails' => $this->abortedMails,
			'countSentMails' => count($this->sentMails),
			'countAbortedMails' => count($this->abortedMails),
		];
	}
}
