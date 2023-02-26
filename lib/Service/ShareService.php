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

use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Event\ShareChangedDisplayNameEvent;
use OCA\Polls\Event\ShareCreateEvent;
use OCA\Polls\Event\ShareTypeChangedEvent;
use OCA\Polls\Event\ShareChangedEmailEvent;
use OCA\Polls\Event\ShareChangedRegistrationConstraintEvent;
use OCA\Polls\Event\ShareDeletedEvent;
use OCA\Polls\Event\ShareRegistrationEvent;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\UserBase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class ShareService {
	/** @var Share[] **/
	private array $shares;
	
	public function __construct(
		private LoggerInterface $logger,
		private ?string $userId,
		private IEventDispatcher $eventDispatcher,
		private IGroupManager $groupManager,
		private ISecureRandom $secureRandom,
		private IUserSession $userSession,
		private ShareMapper $shareMapper,
		private SystemService $systemService,
		private Share $share,
		private MailService $mailService,
		private Acl $acl,
		private NotificationService $notificationService,
		private UserService $userService,
	) {
		$this->shares = [];
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

	/**
	 * Get share by token for accessing the poll
	 */
	public function get(string $token, bool $validateShareType = false): Share {
		$this->share = $this->shareMapper->findByToken($token);
		if ($validateShareType) {
			$this->validateShareType();
		}

		// Exception: logged in user accesses the poll via public share link
		if ($this->share->getType() === Share::TYPE_PUBLIC && $this->userSession->isLoggedIn()) {
			try {
				// Check, if he is already authorized for this poll
				$this->acl->setPollId($this->share->getPollId());
			} catch (NotAuthorizedException $e) {
				// If he is not authorized for this poll, create a personal share
				// for this user and return the created share instead of the public share
				return $this->createNewShare(
					$this->share->getPollId(),
					$this->userService->getUser(Share::TYPE_USER, $this->userSession->getUser()->getUID()),
					true
				);
			}
		}
		return $this->share;
	}

	/**
	 * Flag invitation of this share as sent
	 */
	public function setInvitationSent(string $token): Share {
		$share = $this->shareMapper->findByToken($token);
		$share->setInvitationSent(time());
		return $this->shareMapper->update($share);
	}

	/**
	 * Change share type
	 */
	public function setType(string $token, string $type): Share {
		$this->share = $this->shareMapper->findByToken($token);
		$this->acl->setPollId($this->share->getPollId(), Acl::PERMISSION_POLL_EDIT);
		$this->share->setType($type);
		$this->share = $this->shareMapper->update($this->share);
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
		} catch (ShareNotFoundException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}
		$this->eventDispatcher->dispatchTyped(new ShareChangedRegistrationConstraintEvent($this->share));

		return $this->share;
	}

	/**
	 * Set emailAddress of personal public share
	 *
	 * @return Share
	 */
	public function setEmailAddress(Share $share, string $emailAddress, bool $emptyIsValid = false): Share {
		if ($share->getType() === Share::TYPE_EXTERNAL) {
			$this->systemService->validateEmailAddress($emailAddress, $emptyIsValid);
			$share->setEmailAddress($emailAddress);
			// TODO: Send confirmation
			$share = $this->shareMapper->update($share);
		} else {
			throw new InvalidShareTypeException('Email address can only be set in external shares.');
		}

		$this->eventDispatcher->dispatchTyped(new ShareChangedEmailEvent($share));

		return $share;
	}

	/**
	 * Set displayName of personal share
	 *
	 * @return Share
	 */
	public function setDisplayName(Share $share, string $displayName): Share {
		$this->share = $share;

		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$this->systemService->validatePublicUsername($displayName, $this->share);
			$this->share->setDisplayName($displayName);
			// TODO: Send confirmation
			$this->share = $this->shareMapper->update($this->share);
		} else {
			throw new InvalidShareTypeException('Displayname can only be changed in external shares.');
		}

		$this->eventDispatcher->dispatchTyped(new ShareChangedDisplayNameEvent($this->share));

		return $this->share;
	}

	/**
	 * Delete emailAddress of the personal share
	 */
	public function deleteEmailAddress(Share $share): Share {
		if ($share->getType() === Share::TYPE_EXTERNAL) {
			$share->setEmailAddress('');
			return $this->shareMapper->update($share);
		} else {
			throw new InvalidShareTypeException('Email address can only be set in external shares.');
		}
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 */
	public function register(
		Share $share,
		string $userName,
		string $emailAddress = '',
		string $timeZone = ''
	): Share {
		$this->share = $share;
		$this->systemService->validatePublicUsername($userName, $this->share);
		
		if ($this->share->getPublicPollEmail() !== Share::EMAIL_DISABLED) {
			$this->systemService->validateEmailAddress($emailAddress, $this->share->getPublicPollEmail() !== Share::EMAIL_MANDATORY);
		}

		$language = $this->userService->getGenericLanguage();
		$userId = $this->generatePublicUserId();

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			// Create new external share for user, who entered the poll via public link,
			// prevent invtation sending, when no email address is given
			$this->createNewShare(
				$this->share->getPollId(),
				$this->userService->getUser(Share::TYPE_EXTERNAL, $userId, $userName, $emailAddress, $language, $language, $timeZone),
				!$emailAddress,
				$timeZone
			);
			$this->eventDispatcher->dispatchTyped(new ShareRegistrationEvent($this->share));
		} elseif ($this->share->getType() === Share::TYPE_EMAIL
				|| $this->share->getType() === Share::TYPE_CONTACT) {
			// Convert email and contact shares to external share, if user registers
			$this->share->setType(Share::TYPE_EXTERNAL);
			$this->share->setUserId($userId);
			$this->share->setDisplayName($userName);
			$this->share->setTimeZoneName($timeZone);
			$this->share->setLanguage($language);

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
		} catch (ShareNotFoundException $e) {
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
			foreach ($this->userService->getUserFromShare($share)->getMembers() as $member) {
				$this->notificationService->sendInvitation($share->getPollId(), $member->getId());
			}
		}

		return $this->mailService->sendInvitation($token);
	}

	private function generatePublicUserId(string $prefix = 'ex_'): string {
		$publicUserId = '';

		while ($publicUserId === '') {
			$publicUserId = $prefix . $this->secureRandom->generate(
				8,
				ISecureRandom::CHAR_DIGITS .
					ISecureRandom::CHAR_LOWER .
					ISecureRandom::CHAR_UPPER
			);

			try {
				// make sure, the user id is unique
				$this->systemService->validatePublicUsername($publicUserId, $this->share);
			} catch (InvalidUsernameException $th) {
				$publicUserId = '';
			}
		}

		return $publicUserId;
	}

	/**
	 * convert this share to personal public (aka external) share
	 */
	private function convertToExternal(
		UserBase $userGroup
	): void {
		$this->share->setType(Share::TYPE_EXTERNAL);
		$this->share->setUserId($userGroup->getPublicId());
		$this->share->setDisplayName($userGroup->getDisplayName());
		$this->share->setTimeZoneName($userGroup->getTimeZoneName());
		$this->share->setLanguage($userGroup->getLanguageCode());

		// prepare for resending invitation to new email address
		if ($userGroup->getEmailAddress() !== $this->share->getEmailAddress()) {
			$this->share->setInvitationSent(0);
		}
		$this->share->setEmailAddress($userGroup->getEmailAddress());
	}

	/**
	 * Add share
	 *
	 * @return Share
	 */
	public function add(
		int $pollId,
		string $type,
		string $userId = '',
		string $displayName = '',
		string $emailAddress = ''
	): Share {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);

		if ($type === UserBase::TYPE_PUBLIC) {
			$this->acl->request(Acl::PERMISSION_PUBLIC_SHARES);
		} else {
			try {
				$this->shareMapper->findByPollAndUser($pollId, $userId);
				throw new ShareAlreadyExistsException;
			} catch (MultipleObjectsReturnedException $e) {
				throw new ShareAlreadyExistsException;
			} catch (ShareNotFoundException $e) {
				// continue
			}
		}

		$this->createNewShare($pollId, $this->userService->getUser($type, $userId, $displayName, $emailAddress));

		$this->eventDispatcher->dispatchTyped(new ShareCreateEvent($this->share));

		return $this->share;
	}

	private function sortByCategory(): void {
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
	 * Validate if share type is allowed to be used in a public poll
	 * or is accessibale for use by the current user
	 */
	private function validateShareType(): void {
		$currentUser = $this->userService->getCurrentUser();

		match ($this->share->getType()) {
			Share::TYPE_PUBLIC,	Share::TYPE_EMAIL, Share::TYPE_EXTERNAL => true,
			Share::TYPE_USER => $this->share->getUserId() === $currentUser->getId() ? true : throw new NotAuthorizedException,
			Share::TYPE_ADMIN => $this->share->getUserId() === $currentUser->getId() ? true : throw new NotAuthorizedException,
			Share::TYPE_GROUP => $currentUser->getIsLoggedIn() || $this->groupManager->isInGroup($this->share->getUserId(), $this->userId) ? true : throw new NotAuthorizedException,
			default => throw new NotAuthorizedException('Invalid share type ' . $this->share->getType()),
		};
	}

	/**
	 * crate a new share
	 */
	private function createNewShare(
		int $pollId,
		UserBase $userGroup,
		bool $preventInvitation = false,
		string $timeZone = ''
	): Share {
		$preventInvitation = $userGroup->getType() === UserBase::TYPE_PUBLIC ?: $preventInvitation;
		$token = $this->secureRandom->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
		);

		$this->share = new Share();
		$this->share->setToken($token);
		$this->share->setPollId($pollId);
		$this->share->setType($userGroup->getType());
		$this->share->setDisplayName($userGroup->getDisplayName());
		$this->share->setInvitationSent($preventInvitation ? time() : 0);
		$this->share->setEmailAddress($userGroup->getEmailAddress());
		$this->share->setUserId($userGroup->getPublicId());

		if (
			$userGroup->getType() === UserBase::TYPE_USER
			|| $userGroup->getType() === UserBase::TYPE_ADMIN
		) {
			$this->share = $this->shareMapper->insert($this->share);
			return $this->share;
		}

		if ($userGroup->getType() === UserBase::TYPE_PUBLIC) {
			$this->share->setUserId($token);

			$this->share = $this->shareMapper->insert($this->share);
			return $this->share;
		}

		// Convert user type contact to share type email
		if ($userGroup->getType() === UserBase::TYPE_CONTACT) {
			$this->share->setType(Share::TYPE_EMAIL);
			$this->share->setUserId($userGroup->getEmailAddress());
			$this->share = $this->shareMapper->insert($this->share);
			return $this->share;
		}

		// user is created from public share. Store locale information for
		// usage in server side actions, i.e. scheduled emails
		if ($userGroup->getType() === UserBase::TYPE_EXTERNAL) {
			$this->share->setLanguage($userGroup->getLanguageCode());
			$this->share->setTimeZoneName($timeZone);
		}

		$this->share = $this->shareMapper->insert($this->share);
		return $this->share;
	}
}
