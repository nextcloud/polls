<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;
use OCA\Polls\AppConstants;
use OCA\Polls\Helper\Container;
use OCA\Polls\UserSession;
use OCP\IL10N;
use OCP\L10N\IFactory;

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

	protected IL10N $l10n;
	protected UserSession $userSession;
	protected IFactory $transFactory;

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
		$this->userSession = Container::queryClass(UserSession::class);
		$this->transFactory = Container::queryClass(IFactory::class);
		$this->userSession->getUser()->getLocaleCode();

		$languageCode = $this->userSession->getUser()->getLanguageCode() !== '' ? $this->userSession->getUser()->getLanguageCode() : $this->transFactory->findGenericLanguage();

		$this->l10n = $this->transFactory->get(
			AppConstants::APP_ID,
			$languageCode,
			$this->userSession->getUser()->getLocaleCode()
		);

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

	private function getAnswerTranslated(): string {
		switch ($this->getVoteAnswer()) {
			case self::VOTE_YES:
				return $this->l10n->t('Yes');
			case self::VOTE_NO:
				return $this->l10n->t('No');
			case self::VOTE_EVENTUALLY:
				return $this->l10n->t('Maybe');
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
			'answerTranslated' => $this->getAnswerTranslated(),
		];
	}
}
