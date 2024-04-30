<?php

declare(strict_types=1);
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

use OCA\Polls\Model\Acl;
use OCA\Polls\Model\Settings\AppSettings;

use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Server;

/**
 * @psalm-api
 */
class PollController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private MailService $mailService,
		private OptionService $optionService,
		private PollService $pollService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 * @NoAdminRequired
	 */
	public function list(): JSONResponse {
		return $this->response(function () {
			$appSettings = Server::get(AppSettings::class);
			return [
				'list' => $this->pollService->list(),
				'pollCreationAllowed' => $appSettings->getPollCreationAllowed(),
				'comboAllowed' => $appSettings->getComboAllowed(),
			];
		});
	}

	/**
	 * get complete poll
	 * @NoAdminRequired
	 */
	public function get(int $pollId): JSONResponse {
		$poll = $this->pollService->get($pollId);
		$this->acl->setPollId($pollId);
		return $this->response(fn () => [
			'acl' => $this->acl,
			'poll' => $poll,
		]);
	}

	/**
	 * Add poll
	 * @NoAdminRequired
	 */
	public function add(string $type, string $title): JSONResponse {
		return $this->responseCreate(fn () => $this->pollService->add($type, $title));
	}

	/**
	 * Update poll configuration
	 * @NoAdminRequired
	 */
	public function update(int $pollId, array $poll): JSONResponse {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
		return $this->response(fn () => [
			'poll' => $this->pollService->update($pollId, $poll),
			'acl' => $this->acl->setPollId($pollId),
		]);
	}

	/**
	 * Send confirmation mails
	 * @NoAdminRequired
	 */
	public function sendConfirmation(int $pollId): JSONResponse {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
		return $this->response(fn () => [
			'confirmations' => $this->mailService->sendConfirmations($pollId),
		]);
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @NoAdminRequired
	 */
	public function toggleArchive(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->toggleArchive($pollId));
	}

	/**
	 * Delete poll
	 * @NoAdminRequired
	 */

	public function delete(int $pollId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => $this->pollService->delete($pollId));
	}

	/**
	 * Close poll
	 * @NoAdminRequired
	 */
	public function close(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->close($pollId),
			'acl' => $this->acl->setPollId($pollId),
		]);
	}

	/**
	 * Reopen poll
	 * @NoAdminRequired
	 */
	public function reopen(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->reopen($pollId),
			'acl' => $this->acl->setPollId($pollId),
		]);
	}

	/**
	 * Clone poll
	 * @NoAdminRequired
	 */
	public function clone(int $pollId): JSONResponse {
		return $this->response(fn () => $this->clonePoll($pollId));
	}

	private function clonePoll(int $pollId): JSONResponse {
		$poll = $this->pollService->clone($pollId);
		$this->optionService->clone($pollId, $poll->getId());
		return $this->get($pollId);
	}

	/**
	 * Transfer polls between users
	 */
	public function transferPolls(string $sourceUser, string $targetUser): JSONResponse {
		return $this->response(fn () => $this->pollService->transferPolls($sourceUser, $targetUser));
	}

	/**
	 * Collect email addresses from particitipants
	 * @NoAdminRequired
	 */
	public function getParticipantsEmailAddresses(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->getParticipantsEmailAddresses($pollId));
	}
}
