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
use OCA\Polls\Exceptions\InvalidShareTypeException;


use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

use OCA\Polls\Db\Share;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SystemService;
use OCA\Polls\Model\UserGroup\UserBase;

class ShareController extends Controller {
	use ResponseHandle;

	/** @var MailService */
	private $mailService;

	/** @var ShareService */
	private $shareService;

	/** @var SystemService */
	private $systemService;

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
	 *
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['shares' => $this->shareService->list($pollId)]);
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 */
	public function add(int $pollId, string $type, string $userId = '', string $displayName = ''): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->add($pollId, $type, $userId, $displayName)]);
	}

	/**
	 * Convert user to poll admin
	 * @NoAdminRequired
	 */
	public function userToAdmin(string $token): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->setType($token, Share::TYPE_ADMIN)]);
	}

	/**
	 * Convert user to poll admin
	 * @NoAdminRequired
	 */
	public function setPublicPollEmail(string $token, string $value): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->setPublicPollEmail($token, $value)]);
	}

	/**
	 * Convert poll admin to user
	 * @NoAdminRequired
	 */
	public function adminToUser(string $token): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->setType($token, Share::TYPE_USER)]);
	}

	/**
	 * Set email address
	 * @NoAdminRequired
	 */
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->setEmailAddress($token, $emailAddress)]);
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 */
	public function register(string $token, string $userName, string $emailAddress = ''): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->register($token, $userName, $emailAddress)]);
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 */

	public function delete(string $token): JSONResponse {
		return $this->responseDeleteTolerant(fn () => ['share' => $this->shareService->delete($token)]);
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @NoAdminRequired
	 */
	public function sendInvitation(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->get($token),
			'sentResult' => $this->shareService->sendInvitation($token),
		]);
	}

	/**
	 * resolve contact group to individual shares
	 * @NoAdminRequired
	 */
	public function resolveGroup(string $token): JSONResponse {
		return $this->response(function () use ($token) {
			$shares = [];
			$share = $this->shareService->get($token);
			$resolvableShares = [
				Share::TYPE_CIRCLE,
				Share::TYPE_CONTACTGROUP
			];

			if (!in_array($share->getType(), $resolvableShares)) {
				throw new InvalidShareTypeException('Cannot resolve members from share type ' . $share->getType());
			}

			foreach (UserBase::getUserGroupChild($share->getType(), $share->getUserId())->getMembers() as $member) {
				try {
					$newShare = $this->shareService->add($share->getPollId(), $member->getType(), $member->getId());
					$shares[] = $newShare;
				} catch (ShareAlreadyExistsException $e) {
					continue;
				}
			}

			$this->shareService->delete($token);
			return ['shares' => $shares];
		});
	}
}
