<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method int getPollId()
 * @method void setPollId(int $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method int getVoteOptionId()
 * @method void setVoteOptionId(int $value)
 * @method string getVoteOptionText()
 * @method void setVoteOptionText(string $value)
 * @method string getVoteOptionHash()
 * @method void setVoteOptionHash(string $value)
 * @method string getVoteAnswer()
 * @method void setVoteAnswer(string $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 *
 * Joined Attributes
 * @method string getOptionId()
 */
class Vote extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_votes';
	public const VOTE_YES = 'yes';
	public const VOTE_NO = 'no';
	public const VOTE_EVENTUALLY = 'maybe';

	// schema columns
	public $id = null;
	protected int $pollId = 0;
	protected string $userId = '';
	protected int $voteOptionId = 0;
	protected string $voteOptionText = '';
	protected string $voteOptionHash = '';
	protected string $voteAnswer = '';
	protected int $deleted = 0;

	// joined columns
	protected ?int $optionId = null;

	public function __construct(
	) {
		$this->addType('id', 'integer');
		$this->addType('pollId', 'integer');
		$this->addType('voteOptionId', 'integer');
		$this->addType('deleted', 'integer');
	}

	private function getAnswerSymbol(): string {
		switch ($this->getVoteAnswer()) {
			case self::VOTE_YES:
				return '✔';
			case self::VOTE_NO:
				return '❌';
			case self::VOTE_EVENTUALLY:
				return '❔';
			default:
				return '';
		}
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'optionText' => $this->getVoteOptionText(),
			'answer' => $this->getVoteAnswer(),
			'deleted' => $this->getDeleted(),
			'optionId' => $this->getOptionId(),
			'user' => $this->getUser(),
			'answerSymbol' => $this->getAnswerSymbol(),
		];
	}
}
