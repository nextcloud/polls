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
use OCA\Polls\Exception\Exception;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Service\PreferencesService;
use OCA\Polls\Service\CalendarService;

class PreferencesController extends Controller {
	private $preferencesService;
	private $calendarService;

	/**
	 * PreferencesController constructor.
	 * @param string $appName
	 * @param PreferencesService $preferencesService
	 * @param CalendarService $calendarService
	 */

	public function __construct(
		string $appName,
		IRequest $request,
		PreferencesService $preferencesService,
		CalendarService $calendarService
	) {
		parent::__construct($appName, $request);
		$this->preferencesService = $preferencesService;
		$this->calendarService = $calendarService;
	}

	/**
	 * get
	 * Read all preferences
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function get() {
		return new DataResponse($this->preferencesService->get(), Http::STATUS_OK);
	}

	/**
	 * write
	 * Write wreferences
	 * @NoAdminRequired
	 * @param array $settings
	 * @return DataResponse
	 */
	public function write($settings) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse([], Http::STATUS_OK);
		}

		return new DataResponse($this->preferencesService->write($settings), Http::STATUS_OK);
	}

	/**
	 * get
	 * Read all preferences
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function getCalendars() {
		return new DataResponse(['calendars' => $this->calendarService->getCalendars()], Http::STATUS_OK);
	}
}
