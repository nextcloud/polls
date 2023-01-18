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

use OCA\Polls\Service\PollService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\ISession;
use OCP\IRequest;
use OCP\IURLGenerator;

class AdminController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		private IURLGenerator $urlGenerator,
		private PollService $pollService
	) {
		parent::__construct($appName, $request, $session);
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		return new TemplateResponse('polls', 'polls.tmpl', ['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * Get list of polls for administrative purposes
	 */
	public function list(): JSONResponse {
		return $this->response(fn () => $this->pollService->listForAdmin());
	}

	/**
	 * Get list of polls for administrative purposes
	 */
	public function takeover(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->takeover($pollId));
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 */
	public function toggleArchive(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->toggleArchive($pollId));
	}

	/**
	 * Delete poll
	 */
	public function delete(int $pollId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => $this->pollService->delete($pollId));
	}
}
