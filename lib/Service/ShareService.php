<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Event\ShareChangedDisplayNameEvent;
use OCA\Polls\Event\ShareChangedEmailEvent;
use OCA\Polls\Event\ShareChangedLabelEvent;
use OCA\Polls\Event\ShareChangedRegistrationConstraintEvent;
use OCA\Polls\Event\ShareCreateEvent;
use OCA\Polls\Event\ShareDeletedEvent;
use OCA\Polls\Event\ShareLockedEvent;
use OCA\Polls\Event\ShareRegistrationEvent;
use OCA\Polls\Event\ShareTypeChangedEvent;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\Model\SentResult;
use OCA\Polls\Model\UserBase;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\ISecureRandom;
use phpDocumentor\Reflection\Types\This;
use Psr\Log\LoggerInterface;

class ShareService {
	private const SHARES_TO_CONVERT_ON_ACCESS = [
		Share::TYPE_EMAIL,
		Share::TYPE_CONTACT,
	];

	/** @var Share[] * */
	private array $shares;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher,
		private ISecureRandom $secureRandom,
		private ShareMapper $shareMapper,
		private SystemService $systemService,
		private Share $share,
		private MailService $mailService,
		private Acl $acl,
		private NotificationService $notificationService,
		private PollMapper $pollMapper,
		private UserMapper $userMapper,
		private UserSession $userSession,
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
			$this->pollMapper->find($pollId)->request(Poll::PERMISSION_POLL_EDIT);
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
			$this->pollMapper->find($pollId)->request(Poll::PERMISSION_POLL_EDIT);
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
	 * Converts the public share to a personal share for hte current logged in user
	 * If user is not authorized for this poll, create a personal share
	 * for this user and return the created share instead of the public share
	 */
	private function convertPublicShareToPersonalShare(): void {
		try {
			$this->share = $this->createNewShare(
				$this->share->getPollId(),
				$this->userSession->getUser(),
				preventInvitation: true
			);
		} catch (ShareAlreadyExistsException $e) {
			// replace share by existing personal share
			$this->share = $e->getShare()
				?? $this->share = $this->shareMapper->findByPollAndUser($this->share->getPollId(), $this->userSession->getCurrentUserId());
			// remove the public token from session
			$this->userSession->setShareToken($this->share->getToken());
		}
	}
	/**
	 * Get share by token for accessing the poll
	 *
	 * @param string $token Token of share to get
	 */
	public function request(string $token): Share {

		$this->share = $this->shareMapper->findByToken($token);

		$this->validateShareType();

		// deletes the displayname, to avoid displayname preset in case of public polls
		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			$this->share->setDisplayName('');
		}

		// Exception: logged in user, accesses the poll via public share link
		if ($this->share->getType() === Share::TYPE_PUBLIC && $this->userSession->getIsLoggedIn()) {
			$this->convertPublicShareToPersonalShare();
		}

		// Exception for convertable (email and contact) shares
		if (in_array($this->share->getType(), Share::CONVERATABLE_PUBLIC_SHARES, true)) {
			$this->convertPersonalPublicShareToExternalShare();
		}

		return $this->share;
	}

	/**
	 * Get share by token for accessing the poll
	 *
	 * @param string $token Token of share to get
	 */
	public function get(string $token): Share {
		return $this->share = $this->shareMapper->findByToken($token);
	}

	/**
	 * Change share type
	 */
	public function setType(string $token, string $type): Share {
		$this->share = $this->shareMapper->findByToken($token);
		$this->pollMapper->find($this->share->getPollId())->request(Poll::PERMISSION_POLL_EDIT);

		// ATM only type user can transform to type admin and vice versa
		if (($type === Share::TYPE_ADMIN && $this->share->getType() === Share::TYPE_USER)
		 || ($type === Share::TYPE_USER && $this->share->getType() === Share::TYPE_ADMIN)) {
			$this->share->setType($type);
			$this->share = $this->shareMapper->update($this->share);
			$this->eventDispatcher->dispatchTyped(new ShareTypeChangedEvent($this->share));
		}

		return $this->share;
	}

	/**
	 * Change share type
	 */
	public function setPublicPollEmail(string $token, string $value): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$this->pollMapper->find($this->share->getPollId())->request(Poll::PERMISSION_POLL_EDIT);
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
			MailService::validateEmailAddress($emailAddress, $emptyIsValid);
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
	public function setDisplayName(string $displayName, string $token): Share {
		$this->share = $this->shareMapper->findByToken($token);

		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$displayName = $this->systemService->validatePublicUsername($displayName, share: $this->share);
			$this->share->setDisplayName($displayName);
			$dispatchEvent = new ShareChangedDisplayNameEvent($this->share);

		} else {
			throw new InvalidShareTypeException('Displayname can only be set for external shares.');
		}
		
		$this->share = $this->shareMapper->update($this->share);
		$this->eventDispatcher->dispatchTyped($dispatchEvent);

		return $this->share;
	}

	/**
	 * Set label of public share
	 *
	 * @return Share
	 */
	public function setLabel(string $label, string $token): Share {
		$this->share = $this->shareMapper->findByToken($token);

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			$this->pollMapper->find($this->share->getPollId())->request(Poll::PERMISSION_POLL_EDIT);
			$this->share->setLabel($label);

			// overwrite any possible displayName
			// TODO: Remove afte rmigratiuon to label
			$this->share->setDisplayName('');

			$dispatchEvent = new ShareChangedLabelEvent($this->share);
		} else {
			throw new InvalidShareTypeException('Label can only be set for public shares.');
		}
		
		$this->share = $this->shareMapper->update($this->share);
		$this->eventDispatcher->dispatchTyped($dispatchEvent);

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
	 * Convert convertable public shares to external share and generates a unique user id
	 * @param string|null $userId
	 * @param string|null $displayName
	 * @param string|null $emailAddress
	 * @param string|null $timeZone
	 * @param string|null $language
	 */
	private function convertPersonalPublicShareToExternalShare(
		string|null $userId = null,
		string|null $displayName = null,
		string|null $emailAddress = null,
		string|null $timeZone = null,
		string|null $language = null,
	): void {

		// paranoia double check
		if (!in_array($this->share->getType(), Share::CONVERATABLE_PUBLIC_SHARES, true)) {
			return $this->share;
		}

		$this->share->setUserId($userId ?? $this->generatePublicUserId());
		$this->share->setDisplayName($displayName ?? $this->share->getDisplayName());
		$this->share->setTimeZoneName($timeZone ?? $this->share->getTimeZoneName());
		$this->share->setLanguage($language ?? $this->share->getLanguage());
		
		if ($emailAddress && $emailAddress !== $this->share->getEmailAddress()) {
			// reset invitation sent, if email address is changed
			$this->share->setInvitationSent(0);
		}

		$this->share->setEmailAddress($emailAddress ?? $this->share->getEmailAddress());

		// convert to type external
		$this->share->setType(Share::TYPE_EXTERNAL);

		// remove personal information from user id
		$this->share->setUserId($this->generatePublicUserId());
		$this->share = $this->shareMapper->update($this->share);
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the displayName
	 * @param string $publicShareToken
	 * @param string $displayName
	 * @param string $emailAddress
	 * @param string $timeZone
	 * @return Share
	 */
	public function register(
		string $publicShareToken,
		string $displayName,
		string $emailAddress = '',
		string $timeZone = ''
	): Share {
		$this->share = $this->get($publicShareToken);
		$displayName = $this->systemService->validatePublicUsername($displayName, share: $this->share);

		if ($this->share->getPublicPollEmail() !== Share::EMAIL_DISABLED) {
			MailService::validateEmailAddress($emailAddress, $this->share->getPublicPollEmail() !== Share::EMAIL_MANDATORY);
		}

		$language = $this->systemService->getGenericLanguage();
		$userId = $this->generatePublicUserId();
		$resetInvitation = false;

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			// Create new external share for user, who entered the poll via public link,
			// prevent invtation sending, when no email address is given
			$this->createNewShare(
				$this->share->getPollId(),
				$this->userMapper->getUserObject(Share::TYPE_EXTERNAL, $userId, $displayName, $emailAddress, $language, $language, $timeZone),
				!$emailAddress,
				$timeZone
			);
			$this->eventDispatcher->dispatchTyped(new ShareRegistrationEvent($this->share));

		} elseif (in_array($this->share->getType(), Share::CONVERATABLE_PUBLIC_SHARES, true)) {
			// Convert email and contact shares to external share, if user registers
			// this should be avoided by the actual use cases, but keep code in case of later changes
			$this->convertPersonalPublicShareToExternalShare($userId, $displayName, $emailAddress, $timeZone, $language);

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
	 * Delete or restore share by Token
	 * @param string $token Share of token to delete
	 * @param bool $restore Set true, if share is to be restored
	 */
	public function deleteByToken(string $token, bool $restore = false): Share {
		$share = $this->shareMapper->findByToken($token, true);
		return $this->delete($share, $restore);
	}

	/**
	 * Delete or restore share
	 * @param Share $share Share to delete or restore
	 * @param bool $restore Set true, if share is to be restored
	 */
	public function delete(Share $share, bool $restore = false): Share {
		$this->pollMapper->find($share->getPollId())->request(Poll::PERMISSION_POLL_EDIT);

		$share->setDeleted($restore ? 0 : time());
		$this->shareMapper->update($share);
		$this->eventDispatcher->dispatchTyped(new ShareDeletedEvent($share));
		return $share;
	}

	/**
	 * Lock or unlock share
	 * @param string $token Share of token to lock/unlock
	 * @param bool $unlock Set true, if share is to be unlocked
	 */
	public function lockByToken(string $token, bool $unlock = false): Share {
		$share = $this->shareMapper->findByToken($token, getDeleted: true);
		return $this->lock($share, unlock: $unlock);
	}

	/**
	 * Lock or unlock share
	 * @param Share $share Share to lock/unlock
	 * @param bool $unlock Set true, if share is to be unlocked
	 */
	private function lock(Share $share, bool $unlock = false): Share {
		$this->pollMapper->find($share->getPollId())->request(Poll::PERMISSION_POLL_EDIT);

		$share->setLocked($unlock ? 0 : time());
		$this->shareMapper->update($share);
		$this->eventDispatcher->dispatchTyped(new ShareLockedEvent($share));

		return $share;
	}

	public function sendAllInvitations(int $pollId): SentResult|null {
		$sentResult = new SentResult();

		// first resolve group shares
		$shares = $this->listNotInvited($pollId);
		foreach ($shares as $share) {
			if (in_array($share->getType(), Share::RESOLVABLE_SHARES)) {
				$this->resolveGroup($share);
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

	/**
	 * Resolve a group share into it's member shares and and replace the group share
	 * @param string $token Token of share to lock/unlock
	 */
	public function resolveGroupByToken(string $token): array {
		$share = $this->get($token);
		return $this->resolveGroup($share);

	}

	/**
	 * Resolve a group share into it's member shares and and replace the group share
	 * @param Share $share Share to lock/unlock
	 */
	private function resolveGroup(Share $share): array {
		$shares = [];
		if (!in_array($share->getType(), Share::RESOLVABLE_SHARES)) {
			throw new InvalidShareTypeException('Cannot resolve members from share type ' . $share->getType());
		}

		foreach ($this->userMapper->getUserObject($share->getType(), $share->getUserId())->getMembers() as $member) {
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
	 * @param Share $share
	 * @param SentResult $sentResult to collect results
	 */
	public function sendInvitation(Share $share, ?SentResult &$sentResult = null): SentResult|null {
		if (in_array($share->getType(), [Share::TYPE_USER, Share::TYPE_ADMIN], true)) {
			$this->notificationService->sendInvitation($share->getPollId(), $share->getUserId());
		} elseif ($share->getType() === Share::TYPE_GROUP) {
			foreach ($this->userMapper->getUserFromShare($share)->getMembers() as $member) {
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
				$publicUserId = $this->systemService->validatePublicUsername($publicUserId, share: $this->share);
			} catch (InvalidUsernameException $e) {
				$publicUserId = '';
			}
		}
		return $publicUserId;
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
		$this->pollMapper->find($pollId)->request(Poll::PERMISSION_POLL_EDIT);


		if ($type === UserBase::TYPE_PUBLIC) {
			$this->acl->request(Acl::PERMISSION_PUBLIC_SHARES);
		}

		$share = $this->createNewShare($pollId, $this->userMapper->getUserObject($type, $userId, $displayName, $emailAddress));
		$this->eventDispatcher->dispatchTyped(new ShareCreateEvent($share));

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
		
		$valid = match ($this->share->getType()) {
			Share::TYPE_PUBLIC,	Share::TYPE_EMAIL, Share::TYPE_EXTERNAL => true,
			Share::TYPE_USER => $this->share->getUserId() === $this->userSession->getCurrentUserId(),
			Share::TYPE_ADMIN => $this->share->getUserId() === $this->userSession->getCurrentUserId(),
			// Note: $this->share->getUserId() is actually the group name in case of Share::TYPE_GROUP
			Share::TYPE_GROUP => $this->userSession->getUser()->getIsInGroup($this->share->getUserId()),
			default => throw new ForbiddenException('Invalid share type ' . $this->share->getType()),
		};
		if (!$valid) {
			throw new ForbiddenException('User is not allowed to use this share for poll access (' . $this->share->getType() . ')');
		}
	}

	private function generateToken(): string {
		$token = null;
		$loopCounter = 0;
		while ($token === null) {
			$loopCounter++;
			$token = $this->secureRandom->generate(
				8,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			);
			try {
				$this->shareMapper->findByToken($token, true);
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
		return $token;
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

		// Generate a unique id
		$token = $this->generateToken();

		$this->share = new Share();
		$this->share->setToken($token);
		$this->share->setPollId($pollId);
		$this->share->setType($userGroup->getShareType());
		$this->share->setDisplayName($userGroup->getDisplayName());
		$this->share->setInvitationSent($preventInvitation ? time() : 0);
		$this->share->setEmailAddress($userGroup->getEmailAddress());
		$this->share->setUserId($userGroup->getShareUserId());

		// normal continuation
		// public share to create, set token as userId
		if ($userGroup->getType() === UserBase::TYPE_PUBLIC) {
			$this->share->setUserId($token);
		}

		// user is created from public share. Store locale information for
		// usage in server side actions, i.e. scheduled emails
		if ($userGroup->getType() === UserBase::TYPE_EXTERNAL) {
			$this->share->setLanguage($userGroup->getLanguageCode());
			$this->share->setTimeZoneName($timeZone);
		}
		
		try {
			$this->share = $this->shareMapper->insert($this->share);
			// return new created share
			return $this->share;
		} catch (Exception $e) {
			// TODO: Change exception catch to actual exception
			// Currently OC\DB\Exceptions\DbalException is thrown instead of
			// UniqueConstraintViolationException
			// since the exception is from private namespace, we check the type string
			if (get_class($e) === 'OC\DB\Exceptions\DbalException' && $e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {

				$share = $this->shareMapper->findByPollAndUser($pollId, $this->share->getUserId(), true);
				if ($share->getDeleted()) {
					// Deleted share exist, restore deleted share and generate new token
					$share->setDeleted(0);
					$share->setLocked(0);
					$share->setInvitationSent(0);
					$share->setToken($this->generateToken());
					if ($share->getType() === UserBase::TYPE_ADMIN) {
						$share->setType(UserBase::TYPE_USER);
					}
					$share = $this->shareMapper->update($share);
				}
				// return existing undeleted share
				throw new ShareAlreadyExistsException(existingShare: $share);
			}
			// other error
			throw $e;
		}
	}
}
