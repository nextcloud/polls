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

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Event\ShareChangedDisplayNameEvent;
use OCA\Polls\Event\ShareChangedEmailEvent;
use OCA\Polls\Event\ShareChangedRegistrationConstraintEvent;
use OCA\Polls\Event\ShareCreateEvent;
use OCA\Polls\Event\ShareDeletedEvent;
use OCA\Polls\Event\ShareLockedEvent;
use OCA\Polls\Event\ShareRegistrationEvent;
use OCA\Polls\Event\ShareTypeChangedEvent;
use OCA\Polls\Event\ShareUnlockedEvent;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\SentResult;
use OCA\Polls\Model\UserBase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class ShareService {
	/** @var Share[] **/
	private array $shares;
	private string $userId;

	public function __construct(
		private LoggerInterface $logger,
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
		$this->userId = $this->userSession->getUser()?->getUID() ?? '';
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
		} catch (ForbiddenException $e) {
			return [];
		} catch (DoesNotExistException $e) {
			return [];
		}
		$this->sortByCategory();
		return $this->shares;
	}

	/**
	 * Read all univited shares of a poll based on the poll id and return list as array
	 *
	 * @return Share[]
	 *
	 * @psalm-return array<array-key, Share>
	 */
	public function listNotInvited(int $pollId): array {
		try {
			$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
			$this->shares = $this->shareMapper->findByPollNotInvited($pollId);
		} catch (ForbiddenException $e) {
			return [];
		} catch (DoesNotExistException $e) {
			return [];
		}
		$this->sortByCategory();
		return $this->shares;
	}

	/**
	 * Get share by token for accessing the poll
	 *
	 * @param string $token             Token of share to get
	 * @param bool   $validateShareType Set true, if the share should be validated for usage
	 * @param bool   $publicRequest     Set true, to avoid preset displayname of public shares
	 */
	public function get(string $token, bool $validateShareType = false, bool $publicRequest = false): Share {
		$this->share = $this->shareMapper->findByToken($token);
		
		if ($validateShareType) {
			$this->validateShareType();
		}

		// deletes the displayname, to avoid displayname preset in case of public polls
		if ($this->share->getType() === Share::TYPE_PUBLIC && $publicRequest) {
			$this->share->setDisplayName('');
		}

		// Exception: logged in user accesses the poll via public share link
		if ($this->share->getType() === Share::TYPE_PUBLIC && $this->userSession->isLoggedIn()) {
			try {
				// Check, if he is already authorized for this poll
				$this->acl->setPollId($this->share->getPollId());
			} catch (ForbiddenException $e) {
				// If user is not authorized for this poll, create a personal share
				// for this user and return the created share instead of the public share
				return $this->createNewShare(
					$this->share->getPollId(),
					$this->userService->getUser(Share::TYPE_USER, $this->userId),
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
	 * Set displayName of personal share or label of a public share
	 *
	 * @return Share
	 */
	public function setDisplayName(Share $share, string $displayName): Share {
		$this->share = $share;

		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$this->systemService->validatePublicUsername($displayName, $this->share);
		} elseif ($this->share->getType() === Share::TYPE_PUBLIC) {
			$this->acl->setPollId($share->getPollId())->request(Acl::PERMISSION_POLL_EDIT);
		} else {
			throw new InvalidShareTypeException('Displayname can only be changed in external or public shares.');
		}
		
		$this->share->setDisplayName($displayName);
		$this->share = $this->shareMapper->update($this->share);

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
		Share $publicShare,
		string $userName,
		string $emailAddress = '',
		string $timeZone = ''
	): Share {
		$this->share = $publicShare;
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
			throw new ForbiddenException('Share does not allow registering for poll');
		}

		// send invitation mail, if invitationSent has no timestamp
		try {
			if (!$this->share->getInvitationSent()) {
				$this->mailService->sendInvitation($this->share);
			}
		} catch (\Exception $e) {
			$this->logger->error('Error sending Mail to ' . $this->share->getEmailAddress());
		}

		return $this->share;
	}

	/**
	 * Delete share
	 */
	public function delete(?Share $share = null, ?string $token = null): string {
		try {
			if ($token) {
				$share = $this->shareMapper->findByToken($token);
			}
			$this->acl->setPollId($share->getPollId(), Acl::PERMISSION_POLL_EDIT);
			$this->shareMapper->delete($share);
		} catch (ShareNotFoundException $e) {
			// silently catch
		}

		$this->eventDispatcher->dispatchTyped(new ShareDeletedEvent($share));

		return $share->getToken();
	}

	/**
	 * Lock share
	 */
	public function lock(?Share $share = null, ?string $token = null): string {
		if ($token) {
			$share = $this->shareMapper->findByToken($token);
		}
		$this->acl->setPollId($share->getPollId(), Acl::PERMISSION_POLL_EDIT);

		$share->setLocked(time());
		$this->shareMapper->update($share);
		$this->eventDispatcher->dispatchTyped(new ShareLockedEvent($share));

		return $share->getToken();
	}

	/**
	 * Unlock share
	 */
	public function unlock(?Share $share = null, ?string $token = null): string {
		if ($token) {
			$share = $this->shareMapper->findByToken($token);
		}
		$this->acl->setPollId($share->getPollId(), Acl::PERMISSION_POLL_EDIT);

		$share->setLocked(0);
		$this->shareMapper->update($share);
		$this->eventDispatcher->dispatchTyped(new ShareUnlockedEvent($share));

		return $share->getToken();
	}

	public function sendAllInvitations(int $pollId): SentResult|null {
		$sentResult = new SentResult();

		// first resolve group shares
		$shares = $this->listNotInvited($pollId);
		foreach ($shares as $share) {
			if (in_array($share->getType(), Share::RESOLVABLE_SHARES)) {
				$this->resolveGroup(share: $share);
			}
		}

		// finally send invitation for all not already invited sharees
		$shares = $this->listNotInvited($pollId);
		foreach ($shares as $share) {
			if (!in_array($share->getType(), Share::RESOLVABLE_SHARES)) {
				$this->sendInvitation($share, $sentResult);
			}
		}
		return $sentResult;
	}

	public function resolveGroup(?string $token = null, ?Share $share = null): array {
		if ($token) {
			$share = $this->get($token);
		}

		if (!in_array($share->getType(), Share::RESOLVABLE_SHARES)) {
			throw new InvalidShareTypeException('Cannot resolve members from share type ' . $share->getType());
		}

		foreach ($this->userService->getUser($share->getType(), $share->getUserId())->getMembers() as $member) {
			try {
				$newShare = $this->add($share->getPollId(), $member->getType(), $member->getId());
				$shares[] = $newShare;
			} catch (ShareAlreadyExistsException $e) {
				continue;
			}
		}

		$this->delete($share);
		return $shares;
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 */
	public function sendInvitation(?Share $share = null, ?SentResult &$sentResult = null, ?string $token = null): SentResult|null {
		if ($token) {
			$share = $this->get($token);
		}

		if (in_array($share->getType(), [Share::TYPE_USER, Share::TYPE_ADMIN], true)) {
			$this->notificationService->sendInvitation($share->getPollId(), $share->getUserId());
		} elseif ($share->getType() === Share::TYPE_GROUP) {
			foreach ($this->userService->getUserFromShare($share)->getMembers() as $member) {
				$this->notificationService->sendInvitation($share->getPollId(), $member->getId());
			}
		}

		return $this->mailService->sendInvitation($share, $sentResult);
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
		}

		try {
			$share = $this->createNewShare($pollId, $this->userService->getUser($type, $userId, $displayName, $emailAddress));
			$this->eventDispatcher->dispatchTyped(new ShareCreateEvent($share));
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new ShareAlreadyExistsException;
			}
			
			throw $e;
		}
		
		return $share;
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
		$declineMessage = 'User is not allowed to use this share for poll access (' . $this->share->getType() . ')';

		match ($this->share->getType()) {
			Share::TYPE_PUBLIC,	Share::TYPE_EMAIL, Share::TYPE_EXTERNAL => true,
			Share::TYPE_USER => $this->share->getUserId() === $currentUser->getId() ? true : throw new ForbiddenException($declineMessage),
			Share::TYPE_ADMIN => $this->share->getUserId() === $currentUser->getId() ? true : throw new ForbiddenException($declineMessage),
			Share::TYPE_GROUP => $currentUser->getIsLoggedIn() || $this->groupManager->isInGroup($this->share->getUserId(), $this->userId) ? true : throw new ForbiddenException($declineMessage),
			default => throw new ForbiddenException('Invalid share type ' . $this->share->getType()),
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

		$token = null;
		$loopCounter = 0;
		// Generate a unique id
		while (!$token) {
			$loopCounter++;
			$token = $this->secureRandom->generate(
				8,
				ISecureRandom::CHAR_DIGITS .
					ISecureRandom::CHAR_LOWER .
					ISecureRandom::CHAR_UPPER
			);
			try {
				$this->shareMapper->findByToken($token);
				// reset token, if it already exists
				$token = null;
			} catch (ShareNotFoundException) {
				$loopCounter = 0;
			}
			if ($loopCounter > 10) {
				// In case of uninspected situations, avoid an endless loop
				throw new \Exception('Unexpected loop count while trying to create a token for a new share');
			}
		}

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
