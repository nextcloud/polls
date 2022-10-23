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

use JsonSerializable;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Helper\Container;
use OCP\IUser;
use OCP\IUserManager;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method int getVoteOptionId()
 * @method void setVoteOptionId(integer $value)
 * @method string getVoteOptionText()
 * @method void setVoteOptionText(string $value)
 * @method string getVoteAnswer()
 * @method void setVoteAnswer(string $value)
 */
class Vote extends Entity implements JsonSerializable {
	public const TABLE = 'polls_votes';

	/** @var int $pollId */
	protected $pollId;

	/** @var string $userId */
	protected $userId;

	/** @var string $publicUserId */
	protected $publicUserId = '';

	/** @var int $voteOptionId */
	protected $voteOptionId;

	/** @var string $voteOptionText */
	protected $voteOptionText;

	/** @var string $voteAnswer */
	protected $voteAnswer;

	/** @var IUserManager */
	private $userManager;

	/** @var ShareMapper */
	private $shareMapper;

	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('pollId', 'int');
		$this->addType('voteOptionId', 'int');
		$this->userManager = Container::queryClass(IUserManager::class);
		$this->shareMapper = Container::queryClass(ShareMapper::class);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'optionText' => $this->getVoteOptionText(),
			'answer' => $this->getVoteAnswer(),
			'user' => [
				'userId' => $this->getPublicUserId(),
				'displayName' => $this->getDisplayName(),
				'isNoUser' => $this->getIsNoUser()
			],
		];
	}

	public function getDisplayName(): string {
		if ($this->getIsNoUser()) {
			// get displayName from share
			try {
				$share = $this->shareMapper->findByPollAndUser($this->getPollId(), $this->userId);
			} catch (ShareNotFoundException $e) {
				// use fake share
				$share = $e->getReplacement();
			}
			return $share->getDisplayName();
		}

		return $this->userManager->get($this->userId)->getDisplayName();
	}

	public function getIsNoUser(): bool {
		return !($this->userManager->get($this->userId) instanceof IUser);
	}

	private function getPublicUserId() {
		if (!$this->getUserId()) {
			return '';
		}

		if ($this->publicUserId) {
			return $this->publicUserId;
		}

		return $this->getUserId();
	}

	public function generateHashedUserId() {
		$this->publicUserId = hash('md5', $this->getUserId());
	}

}

