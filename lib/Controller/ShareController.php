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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\InvalidShareType;
use OCA\Polls\Exceptions\ShareAlreadyExists;


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
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $type
	 * @param string $userId
	 * @param string $userEmail
	 * @return DataResponse
	 */
	public function add($pollId, $type, $userId = '') {
		try {
			return new DataResponse(['share' => $this->shareService->add($pollId, $type, $userId)], Http::STATUS_CREATED);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (ShareAlreadyExists $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
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
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
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
	 * @param string $userEmail
	 * @return DataResponse
	 */
	public function setEmailAddress($token, $userEmail) {
		try {
			return new DataResponse(['share' => $this->shareService->setEmailAddress($token, $userEmail)], Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (InvalidShareType $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
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
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (InvalidUsernameException $e) {
			return new DataResponse(['error' => $userName . ' is not valid'], Http::STATUS_CONFLICT);
		} catch (DoesNotExistException $e) {
			// return forbidden in all not catched error cases
			return new DataResponse($e, Http::STATUS_FORBIDDEN);
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
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
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
					} catch (ShareAlreadyExists $e) {
						continue;
					}
				}
			} elseif ($share->getType() === Share::TYPE_CONTACTGROUP) {
				foreach ((new ContactGroup($share->getUserId()))->getMembers() as $contact) {
					try {
						$newShare = $this->shareService->add($share->getPollId(), Share::TYPE_CONTACT, $contact->getId());
						$shares[] = $newShare;
					} catch (ShareAlreadyExists $e) {
						continue;
					}
				}
			}
			$this->shareService->delete($token);
			return new DataResponse(['shares' => $shares], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e], Http::STATUS_CONFLICT);
		}
	}
}
