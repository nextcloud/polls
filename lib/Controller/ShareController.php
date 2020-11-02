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

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;


use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\DB\Share;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SystemService;
use OCA\Polls\Model\Circle;
use OCA\Polls\Model\ContactGroup;

class ShareController extends Controller {

	/** @var MailService */
	private $mailService;

	/** @var ShareService */
	private $shareService;

	/** @var SystemService */
	private $systemService;

	/**
	 * ShareController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param MailService $mailService
	 * @param ShareService $shareService
	 * @param SystemService $systemService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		MailService $mailService,
		ShareService $shareService,
		SystemService $systemService
	) {
		parent::__construct($appName, $request);
		$this->mailService = $mailService;
		$this->shareService = $shareService;
		$this->systemService = $systemService;
	}

	/**
	 * List shares
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		try {
			return new DataResponse(['shares' => $this->shareService->list($pollId)], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $type
	 * @param string $userId
	 * @return DataResponse
	 */
	public function add($pollId, $type, $userId = '') {
		try {
			return new DataResponse(['share' => $this->shareService->add($pollId, $type, $userId)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * Get share
	 * @NoAdminRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function get($token) {
		try {
			return new DataResponse(['share' => $this->shareService->get($token)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * Set email address
	 * @NoAdminRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param int $pollId
	 * @param string $type
	 * @param string $userId
	 * @param string $emailAddress
	 * @return DataResponse
	 */
	public function setEmailAddress($token, $emailAddress) {
		try {
			return new DataResponse(['share' => $this->shareService->setEmailAddress($token, $emailAddress)], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @PublicPage
	 * @param string $token
	 * @param string $userName
	 * @return DataResponse
	 */
	public function personal($token, $userName, $emailAddress = '') {
		try {
			return new DataResponse($this->shareService->personal($token, $userName, $emailAddress), Http::STATUS_CREATED);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_FORBIDDEN);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 * @param string $token
	 * @return DataResponse
	 */

	public function delete($token) {
		try {
			return new DataResponse($this->shareService->delete($token), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * Sent invitation mails for a share
	 * @NoAdminRequired
	 * @PublicPage
	 * @param string $token
	 * @return DataResponse
	 */
	public function sendInvitation($token) {
		try {
			$sentResult = $this->mailService->sendInvitationMail($token);
			$share = $this->shareService->get($token);
			return new DataResponse(['share' => $share, 'sentResult' => $sentResult], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e], Http::STATUS_CONFLICT);
		}
	}

	/**
	 * resolve contact group to individual shares
	 * @NoAdminRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function resolveGroup($token) {
		$shares = [];
		try {
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
			return new DataResponse(['shares' => $shares], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}
}
