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

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

use OCA\Polls\Db\Poll;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\Settings\AppSettings;

class PollController extends Controller {

	/** @var Acl */
	private $acl;

	/** @var OptionService */
	private $optionService;

	/** @var PollService */
	private $pollService;

	/** @var Poll */
	private $poll;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		Acl $acl,
		OptionService $optionService,
		PollService $pollService,
		Poll $poll
	) {
		parent::__construct($appName, $request);
		$this->acl = $acl;
		$this->optionService = $optionService;
		$this->pollService = $pollService;
		$this->poll = $poll;
	}

	/**
	 * Get list of polls
	 * @NoAdminRequired
	 */
	public function list(): JSONResponse {
		return $this->response(function () {
			$appSettings = new AppSettings;
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
		$this->acl->setPollId($pollId);
		return $this->response(fn () => [
			'acl' => $this->acl,
			'poll' => $this->acl->getPoll(),
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
	 * Clone poll
	 * @NoAdminRequired
	 */
	public function clone(int $pollId): JSONResponse {
		$poll = $this->pollService->clone($pollId);
		$this->optionService->clone($pollId, $poll->getId());
		return $this->get($pollId);
	}

	/**
	 * Collect email addresses from particitipants
	 * @NoAdminRequired
	 */
	public function getParticipantsEmailAddresses(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->getParticipantsEmailAddresses($pollId));
	}
}
