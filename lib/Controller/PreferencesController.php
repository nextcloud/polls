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

use OCA\Polls\Db\UserMapper;
use OCA\Polls\Service\CalendarService;
use OCA\Polls\Service\PreferencesService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class PreferencesController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private PreferencesService $preferencesService,
		private CalendarService $calendarService,
		private UserMapper $userMapper,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read all preferences
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(): JSONResponse {
		return $this->response(fn () => $this->preferencesService->get());
	}

	/**
	 * Write preferences
	 */
	#[NoAdminRequired]
	public function write(array $preferences): JSONResponse {
		if (!$this->userMapper->getCurrentUser()->getIsLoggedIn()) {
			return new JSONResponse([], Http::STATUS_OK);
		}
		return $this->response(fn () => $this->preferencesService->write($preferences));
	}

	/**
	 * Read all preferences
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getCalendars(): JSONResponse {
		return new JSONResponse(['calendars' => $this->calendarService->getCalendars()], Http::STATUS_OK);
	}
}
