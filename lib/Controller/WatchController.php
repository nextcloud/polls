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
use OCA\Polls\Exceptions\NoUpdatesException;
use OCA\Polls\Service\WatchService;

class WatchController extends Controller {

	/** @var WatchService */
	private $watchService;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		WatchService $watchService
	) {
		parent::__construct($appName, $request);
		$this->watchService = $watchService;
	}

	/**
	 * Watch poll for updates
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function watchPoll(int $pollId, ?int $offset): DataResponse {
		return $this->responseLong(function () use ($pollId, $offset) {
			$start = time();
			$timeout = 30;
			$offset = $offset ?? $start;
			while (empty($updates) && time() <= $start + $timeout) {
				sleep(1);
				$updates = $this->watchService->getUpdates($pollId, $offset);
			}
			if (empty($updates)) {
				throw new NoUpdatesException;
			}
			return ['updates' => $updates];
		});
	}
}
