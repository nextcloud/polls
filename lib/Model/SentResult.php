<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

/**
 * @psalm-import-type PollsSentMailInfo from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsAbortedMailInfo from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsSentResult from \OCA\Polls\ResponseDefinitions
 */
class SentResult implements \JsonSerializable {
	public const INVALID_EMAIL_ADDRESS = 'InvalidMail';
	public const UNHANDELED_REASON = 'UnknownError';

	/** @var list<PollsSentMailInfo> */
	private array $sentMails = [];
	/** @var list<PollsAbortedMailInfo> */
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

	/** @return PollsSentResult */
	public function jsonSerialize(): array {
		return	[
			'sentMails' => $this->sentMails,
			'abortedMails' => $this->abortedMails,
			'countSentMails' => count($this->sentMails),
			'countAbortedMails' => count($this->abortedMails),
		];
	}
}
