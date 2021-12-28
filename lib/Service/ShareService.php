<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
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

namespace OCA\Polls\Service;

use Psr\Log\LoggerInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Exceptions\NotFoundException;

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Event\ShareCreateEvent;
use OCA\Polls\Event\ShareTypeChangedEvent;
use OCA\Polls\Event\ShareChangedEmailEvent;
use OCA\Polls\Event\ShareChangedRegistrationConstraintEvent;
use OCA\Polls\Event\ShareDeletedEvent;
use OCA\Polls\Event\ShareRegistrationEvent;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\UserGroup\UserBase;

class ShareService {

	/** @var LoggerInterface */
	private $logger;

	/** @var string */
	private $appName;

	/** @var string|null */
	private $userId;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var IGroupManager */
	private $groupManager;

	/** @var SystemService */
	private $systemService;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var Share */
	private $share;

	/** @var array */
	private $shares = [];

	/** @var MailService */
	private $mailService;

	/** @var Acl */
	private $acl;

	/** @var NotificationService */
	private $notificationService;

	public function __construct(
		string $AppName,
		LoggerInterface $logger,
		?string $UserId,
		IEventDispatcher $eventDispatcher,
		IGroupManager $groupManager,
		SystemService $systemService,
		ShareMapper $shareMapper,
		PollMapper $pollMapper,
		Share $share,
		MailService $mailService,
		Acl $acl,
		NotificationService $notificationService
	) {
		$this->appName = $AppName;
		$this->logger = $logger;
		$this->userId = $UserId;
		$this->eventDispatcher = $eventDispatcher;
		$this->groupManager = $groupManager;
		$this->systemService = $systemService;
		$this->shareMapper = $shareMapper;
		$this->pollMapper = $pollMapper;
		$this->share = $share;
		$this->mailService = $mailService;
		$this->acl = $acl;
		$this->notificationService = $notificationService;
	}

	/**
	 * Read all shares of a poll based on the poll id and return list as array
	 *
	 * @return Share[]
	 *
	 * @psalm-return array<array-key, Share>
	 */
	public function list(int $pollId): array {
		try {
			$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
			$this->shares = $this->shareMapper->findByPoll($pollId);
		} catch (NotAuthorizedException $e) {
			return [];
		} catch (DoesNotExistException $e) {
			return [];
		}
		$this->sortByCategory();
		return $this->shares;
	}

	private function sortByCategory() : void {
		$sortedShares = [];
		foreach (Share::TYPE_SORT_ARRAY as $shareType) {
			$filteredShares = array_filter($this->shares, function ($share) use ($shareType) {
				return $share->getType() === $shareType;
			});
			$sortedShares = array_merge($sortedShares, $filteredShares);
		}
		$this->shares = $sortedShares;
	}

	/**
	 * Validate share
	 */
	private function validate() : void {
		switch ($this->share->getType()) {
			case Share::TYPE_PUBLIC:
				// public shares are always valid
				break;
			case Share::TYPE_USER:
				if ($this->share->getUserId() !== $this->userId) {
					// share is not valid for user
					throw new NotAuthorizedException;
				}
				break;
			case Share::TYPE_ADMIN:
				if ($this->share->getUserId() !== $this->userId) {
					// share is not valid for user
					throw new NotAuthorizedException;
				}
				break;
			case Share::TYPE_GROUP:
				if (!\OC::$server->getUserSession()->isLoggedIn()) {
					throw new NotAuthorizedException;
				}

				if (!$this->groupManager->isInGroup($this->share->getUserId(), $this->userId)) {
					throw new NotAuthorizedException;
				}
				break;
			case Share::TYPE_EMAIL:
				break;
			case Share::TYPE_EXTERNAL:
				break;
			default:
				$this->logger->alert(json_encode('invalid share type ' . $this->share->getType()));
				throw new NotAuthorizedException;
		}
	}

	/**
	 * Get share by token
	 */
	public function get(string $token, bool $validate = false): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			if ($validate) {
				$this->validate();
			}
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}
		// Allow users entering the poll with a public share access

		if ($this->share->getType() === Share::TYPE_PUBLIC && \OC::$server->getUserSession()->isLoggedIn()) {
			try {
				// Test if the user has already access.
				$this->acl->setPollId($this->share->getPollId());
			} catch (NotAuthorizedException $e) {
				// If he is not authorized until now, create a new personal share for this user.
				// Return the created share
				return $this->create(
					$this->share->getPollId(),
					UserBase::getUserGroupChild(Share::TYPE_USER, \OC::$server->getUserSession()->getUser()->getUID()),
					true
				);
			}
		}
		return $this->share;
	}

	/**
	 * Get share by token
	 *
	 * @return Share
	 */
	public function setInvitationSent(string $token): Share {
		$share = $this->shareMapper->findByToken($token);
		$share->setInvitationSent(time());
		return $this->shareMapper->update($share);
	}

	/**
	 * crate share - MUST BE PRIVATE!
	 *
	 * @return Share
	 */
	private function create(int $pollId, UserBase $userGroup, bool $preventInvitation = false): Share {
		$preventInvitation = $userGroup->getType() === UserBase::TYPE_PUBLIC ?: $preventInvitation;
		$token = \OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$this->share = new Share();
		$this->share->setToken($token);
		$this->share->setPollId($pollId);

		// Convert user type contact to share type email
		if ($userGroup->getType() === UserBase::TYPE_CONTACT) {
			$this->share->setType(Share::TYPE_EMAIL);
			$this->share->setUserId($userGroup->getEmailAddress());
		} else {
			$this->share->setType($userGroup->getType());
			$this->share->setUserId($userGroup->getType() === UserBase::TYPE_PUBLIC ? $token : $userGroup->getPublicId());
		}

		$this->share->setInvitationSent($preventInvitation ? time() : 0);
		$this->share->setDisplayName($userGroup->getDisplayName());
		$this->share->setEmailAddress($userGroup->getEmailAddress());

		$this->share = $this->shareMapper->insert($this->share);


		return $this->share;
	}

	/**
	 * Add share
	 *
	 * @return Share
	 */
	public function add(int $pollId, string $type, string $userId = ''): Share {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);

		if ($type === UserBase::TYPE_PUBLIC) {
			$this->acl->request(ACL::PERMISSION_PUBLIC_SHARES);
		} else {
			try {
				$this->shareMapper->findByPollAndUser($pollId, $userId);
				throw new ShareAlreadyExistsException;
			} catch (MultipleObjectsReturnedException $e) {
				throw new ShareAlreadyExistsException;
			} catch (DoesNotExistException $e) {
				// continue
			}
		}

		$this->create($pollId, UserBase::getUserGroupChild($type, $userId));

		$this->eventDispatcher->dispatchTyped(new ShareCreateEvent($this->share));

		return $this->share;
	}

	/**
	 * Change share type
	 */
	public function setType(string $token, string $type): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$this->acl->setPollId($this->share->getPollId(), Acl::PERMISSION_POLL_EDIT);
			$this->share->setType($type);
			$this->share = $this->shareMapper->update($this->share);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		$this->eventDispatcher->dispatchTyped(new ShareTypeChangedEvent($this->share));

		return $this->share;
	}

	/**
	 * Change share type
	 */
	public function setPublicPollEmail(string $token, string $value): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$this->acl->setPollId($this->share->getPollId(), Acl::PERMISSION_POLL_EDIT);
			$this->share->setPublicPollEmail($value);
			$this->share = $this->shareMapper->update($this->share);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}
		$this->eventDispatcher->dispatchTyped(new ShareChangedRegistrationConstraintEvent($this->share));

		return $this->share;
	}

	/**
	 * Set emailAddress to personal share
	 * or update an email share with the username
	 *
	 * @return Share
	 */
	public function setEmailAddress(string $token, string $emailAddress, bool $emptyIsValid = false): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$this->systemService->validateEmailAddress($emailAddress, $emptyIsValid);
			$this->share->setEmailAddress($emailAddress);
			// TODO: Send confirmation
			$this->share = $this->shareMapper->update($this->share);
		} else {
			throw new InvalidShareTypeException('Email address can only be set in external shares.');
		}

		$this->eventDispatcher->dispatchTyped(new ShareChangedEmailEvent($this->share));

		return $this->share;
	}

	/**
	 * Delete emailAddress of personal share
	 *
	 * @return Share
	 */
	public function deleteEmailAddress(string $token): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$this->share->setEmailAddress('');
			return $this->shareMapper->update($this->share);
		} else {
			throw new InvalidShareTypeException('Email address can only be set in external shares.');
		}
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 *
	 * @return Share
	 */
	public function register(string $token, string $userName, string $emailAddress = ''): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$poll = $this->pollMapper->find($this->share->getPollId());
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		$this->systemService->validatePublicUsername($userName, $token);
		$this->systemService->validateEmailAddress($emailAddress, $poll->getPublicPollEmail() !== 'mandatory');

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			// Create new external share for user, who entered the poll via public link,
			// prevent invtation sending, when no email address is given
			$this->create(
				$this->share->getPollId(),
				UserBase::getUserGroupChild(Share::TYPE_EXTERNAL, $userName, $userName, $emailAddress),
				!$emailAddress
			);
			$this->eventDispatcher->dispatchTyped(new ShareRegistrationEvent($this->share));
		} elseif ($this->share->getType() === Share::TYPE_EMAIL
				|| $this->share->getType() === Share::TYPE_CONTACT) {

			// Convert email and contact shares to external share, if user registers
			$this->share->setType(Share::TYPE_EXTERNAL);
			$this->share->setUserId($userName);
			$this->share->setDisplayName($userName);

			// prepare for resending invitation to new email address
			if ($emailAddress !== $this->share->getEmailAddress()) {
				$this->share->setInvitationSent(0);
			}
			$this->share->setEmailAddress($emailAddress);
			$this->shareMapper->update($this->share);
		} else {
			throw new NotAuthorizedException;
		}

		// send invitation mail, if invitationSent has no timestamp
		try {
			if (!$this->share->getInvitationSent()) {
				$this->mailService->resendInvitation($this->share->getToken());
			}
		} catch (\Exception $e) {
			$this->logger->error('Error sending Mail to ' . $this->share->getEmailAddress());
		}

		return $this->share;
	}

	/**
	 * Delete share
	 */
	public function delete(string $token): string {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$this->acl->setPollId($this->share->getPollId(), Acl::PERMISSION_POLL_EDIT);
			$this->shareMapper->delete($this->share);
		} catch (DoesNotExistException $e) {
			// silently catch
		}

		$this->eventDispatcher->dispatchTyped(new ShareDeletedEvent($this->share));

		return $token;
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 */
	public function sendInvitation(string $token): array {
		$share = $this->get($token);
		if (in_array($share->getType(), [Share::TYPE_USER, Share::TYPE_ADMIN], true)) {
			$this->notificationService->sendInvitation($share->getPollId(), $share->getUserId());

		// TODO: skip this atm, to send invitations as mail too, if user is a site user
			// $sentResult = ['sentMails' => [new User($share->getuserId())]];
			// $this->shareService->setInvitationSent($token);
		} elseif ($share->getType() === Share::TYPE_GROUP) {
			foreach ($share->getUserObject()->getMembers() as $member) {
				$this->notificationService->sendInvitation($share->getPollId(), $member->getId());
			}
		}

		return $this->mailService->sendInvitation($token);
	}
}
