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

use OCA\Polls\Exceptions\Exception;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\Poll;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Model\Acl;

class PollController extends Controller {

	/** @var Acl */
	private $acl;

	/** @var CommentService */
	private $commentService;

	/** @var OptionService */
	private $optionService;

	/** @var PollService */
	private $pollService;

	/** @var Poll */
	private $poll;

	/** @var ShareService */
	private $shareService;

	/** @var Share */
	private $share;

	/** @var VoteService */
	private $voteService;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		Acl $acl,
		CommentService $commentService,
		OptionService $optionService,
		PollService $pollService,
		Poll $poll,
		ShareService $shareService,
		Share $share,
		VoteService $voteService
	) {
		parent::__construct($appName, $request);
		$this->acl = $acl;
		$this->commentService = $commentService;
		$this->optionService = $optionService;
		$this->pollService = $pollService;
		$this->poll = $poll;
		$this->shareService = $shareService;
		$this->share = $share;
		$this->voteService = $voteService;
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
			$this->share = null;
			$this->poll = $this->pollService->get($pollId);
			$this->acl->setPoll($this->poll)->requestView();
			return $this->build();
		});
	}

	/**
	 * get complete poll
	 * @NoAdminRequired
	 */
	private function build(): array {
		// try {
		// 	$comments = $this->commentService->list($this->poll->getId());
		// } catch (Exception $e) {
		// 	$comments = [];
		// }
		//
		// try {
		// 	$options = $this->optionService->list($this->poll->getId());
		// } catch (Exception $e) {
		// 	$options = [];
		// }

		try {
			$votes = $this->voteService->list($this->poll->getId());
		} catch (Exception $e) {
			$votes = [];
		}

		try {
			$shares = $this->shareService->list($this->poll->getId());
		} catch (Exception $e) {
			$shares = [];
		}

		return [
			'acl' => $this->acl,
			'poll' => $this->poll,
			// 'comments' => $comments,
			// 'options' => $options,
			'share' => $this->share,
			'shares' => $shares,
			'votes' => $votes,
		];
	}

	/**
	 * Add poll
	 * @NoAdminRequired
	 */

	public function add($type, $title): DataResponse {
		return $this->responseCreate(function () use ($type, $title) {
			return $this->pollService->add($type, $title);
		});
	}

	/**
	 * Update poll configuration
	 * @NoAdminRequired
	 */

	public function update($pollId, $poll): DataResponse {
		return $this->response(function () use ($pollId, $poll) {
			return $this->pollService->update($pollId, $poll);
		});
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @NoAdminRequired
	 */

	public function switchDeleted($pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return $this->pollService->switchDeleted($pollId);
		});
	}

	/**
	 * Delete poll
	 * @NoAdminRequired
	 */

	public function delete($pollId): DataResponse {
		return $this->responseDeleteTolerant(function () use ($pollId) {
			return $this->pollService->delete($pollId);
		});
	}

	/**
	 * Clone poll
	 * @NoAdminRequired
	 */
	public function clone($pollId): DataResponse {
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

	public function getParticipantsEmailAddresses($pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return $this->pollService->getParticipantsEmailAddresses($pollId);
		});
	}
}
