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

namespace OCA\Polls\Controller;

use OCA\Polls\Exceptions\ShareAlreadyExistsException;


use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Share;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\NotificationService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SystemService;
use OCA\Polls\Model\User;
use OCA\Polls\Model\Circle;
use OCA\Polls\Model\ContactGroup;

class ShareController extends Controller {
	use ResponseHandle;

	/** @var MailService */
	private $mailService;

	/** @var ShareService */
	private $shareService;

	/** @var SystemService */
	private $systemService;

	/** @var NotificationService */
	private $notificationService;

	public function __construct(
		string $appName,
		IRequest $request,
		MailService $mailService,
		ShareService $shareService,
		SystemService $systemService,
		NotificationService $notificationService
	) {
		parent::__construct($appName, $request);
		$this->mailService = $mailService;
		$this->shareService = $shareService;
		$this->systemService = $systemService;
		$this->notificationService = $notificationService;
	}

	/**
	 * 	 * List shares
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function list($pollId): DataResponse {
		return $this->response(function () use ($pollId): array {
			return ['shares' => $this->shareService->list($pollId)];
		});
	}

	/**
	 * 	 * Get share
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function get($token): DataResponse {
		return $this->response(function () use ($token): array {
			return ['share' => $this->shareService->get($token, true)];
		});
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 */
	public function add($pollId, $type, $userId = ''): DataResponse {
		return $this->responseCreate(function () use ($pollId, $type, $userId) {
			return ['share' => $this->shareService->add($pollId, $type, $userId)];
		});
	}

	/**
	 * Set email address
	 * @NoAdminRequired
	 */
	public function setEmailAddress($token, $emailAddress): DataResponse {
		return $this->response(function () use ($token, $emailAddress) {
			return ['share' => $this->shareService->setEmailAddress($token, $emailAddress)];
		});
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 */
	public function personal($token, $userName, $emailAddress = ''): DataResponse {
		return $this->responseCreate(function () use ($token, $userName, $emailAddress) {
			return ['share' => $this->shareService->personal($token, $userName, $emailAddress)];
		});
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 */

	public function delete($token): DataResponse {
		return $this->responseDeleteTolerant(function () use ($token) {
			return ['share' => $this->shareService->delete($token)];
		});
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @NoAdminRequired
	 */
	public function sendInvitation($token): DataResponse {
		return $this->response(function () use ($token) {
			$share = $this->shareService->get($token);
			if ($share->getType() === Share::TYPE_USER) {
				$this->notificationService->sendInvitation($share->getPollId(), $share->getUserId());
			// TODO: skip this atm, to send invitations as mail too, if user is a site user
				// $sentResult = ['sentMails' => [new User($share->getuserId())]];
				// $this->shareService->setInvitationSent($token);
			} elseif ($share->getType() === Share::TYPE_GROUP) {
				foreach ($share->getMembers() as $member) {
					$this->notificationService->sendInvitation($share->getPollId(), $member->getId());
				}
			}
			$sentResult = $this->mailService->sendInvitation($token);
			return [
				'share' => $share,
				'sentResult' => $sentResult
			];
		});
	}

	/**
	 * resolve contact group to individual shares
	 * @NoAdminRequired
	 */
	public function resolveGroup($token): DataResponse {
		return $this->response(function () use ($token) {
			$shares = [];
			$share = $this->shareService->get($token);
			if ($share->getType() === Share::TYPE_CIRCLE) {
				foreach ((new Circle($share->getUserId()))->getMembers() as $member) {
					try {
						$newShare = $this->shareService->add($share->getPollId(), $member->getType(), $member->getId());
						$shares[] = $newShare;
					} catch (ShareAlreadyExistsException $e) {
						continue;
					}
				}
			} elseif ($share->getType() === Share::TYPE_CONTACTGROUP) {
				foreach ((new ContactGroup($share->getUserId()))->getMembers() as $contact) {
					try {
						$newShare = $this->shareService->add($share->getPollId(), Share::TYPE_CONTACT, $contact->getId());
						$shares[] = $newShare;
					} catch (ShareAlreadyExistsException $e) {
						continue;
					}
				}
			}
			$this->shareService->delete($token);
			return ['shares' => $shares];
		});
	}
}
