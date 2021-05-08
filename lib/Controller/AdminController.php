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
use OCP\IURLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;

use OCA\Polls\Db\Poll;
use OCA\Polls\Service\PollService;

class AdminController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var PollService */
	private $pollService;

	/** @var Poll */
	private $poll;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		PollService $pollService,
		Poll $poll
	) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->pollService = $pollService;
		$this->poll = $poll;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		return new TemplateResponse('polls', 'polls.tmpl',
		['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * Get list of polls for administrative purposes
	 */
	public function list(): DataResponse {
		return $this->response(function () {
			return $this->pollService->listForAdmin();
		});
	}

	/**
	 * Get list of polls for administrative purposes
	 */
	public function takeover(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return $this->pollService->takeover($pollId);
		});
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 */
	public function switchDeleted(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return $this->pollService->switchDeleted($pollId);
		});
	}

	/**
	 * Delete poll
	 */
	public function delete(int $pollId): DataResponse {
		return $this->responseDeleteTolerant(function () use ($pollId) {
			return $this->pollService->delete($pollId);
		});
	}
}
