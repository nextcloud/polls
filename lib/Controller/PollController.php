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
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Poll;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Model\Acl;

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

	public function list(): DataResponse {
		return $this->response(function () {
			return $this->pollService->list();
		});
	}

	/**
	 * get complete poll
	 * @NoAdminRequired
	 */
	public function get(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			$this->poll = $this->pollService->get($pollId);
			$this->acl->setPoll($this->poll)->request(Acl::PERMISSION_POLL_VIEW);
			return [
				'acl' => $this->acl,
				'poll' => $this->poll,
			];
		});
	}

	/**
	 * Add poll
	 * @NoAdminRequired
	 */

	public function add(string $type, string $title): DataResponse {
		return $this->responseCreate(function () use ($type, $title) {
			return $this->pollService->add($type, $title);
		});
	}

	/**
	 * Update poll configuration
	 * @NoAdminRequired
	 */

	public function update(int $pollId, array $poll): DataResponse {
		return $this->response(function () use ($pollId, $poll) {
			return $this->pollService->update($pollId, $poll);
		});
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @NoAdminRequired
	 */

	public function switchDeleted(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return $this->pollService->switchDeleted($pollId);
		});
	}

	/**
	 * Delete poll
	 * @NoAdminRequired
	 */

	public function delete(int $pollId): DataResponse {
		return $this->responseDeleteTolerant(function () use ($pollId) {
			return $this->pollService->delete($pollId);
		});
	}

	/**
	 * Clone poll
	 * @NoAdminRequired
	 */
	public function clone(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			$poll = $this->pollService->clone($pollId);
			$this->optionService->clone($pollId, $poll->getId());

			return $poll;
		});
	}

	/**
	 * Collect email addresses from particitipants
	 * @NoAdminRequired
	 */

	public function getParticipantsEmailAddresses(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return $this->pollService->getParticipantsEmailAddresses($pollId);
		});
	}
}
