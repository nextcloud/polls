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

use OCA\Polls\Service\SettingsService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;

class SettingsController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		private SettingsService $settingsService
	) {
		parent::__construct($appName, $request, $session);
	}

	/**
	 * Read app settings
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getAppSettings(): JSONResponse {
		return $this->response(fn () => ['appSettings' => $this->settingsService->getAppSettings()]);
	}

	/**
	 * Write app settings
	 */
	public function writeAppSettings(array $appSettings): JSONResponse {
		$this->settingsService->writeAppSettings($appSettings);
		return $this->response(fn () => ['appSettings' => $this->settingsService->getAppSettings()]);
	}
}
