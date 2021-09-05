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
use OCA\Polls\Model\Group;
use OCA\Polls\Service\SettingsService;

class SettingsController extends Controller {

	/** @var SettingsService */
	private $settingsService;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		SettingsService $settingsService

	) {
		parent::__construct($appName, $request);
		$this->settingsService = $settingsService;
	}

	/**
	 * Read app settings
	 * @NoAdminRequired
	 */
	public function getAppSettings(): DataResponse {
		return $this->response(function (): array {
			return ['appSettings' => $this->settingsService->getAppSettings()];
		});
	}

	/**
	 * Write app settings
	 */
	public function writeAppSettings(array $appSettings): DataResponse {
		$this->settingsService->writeAppSettings($appSettings);
		return $this->response(function (): array {
			return ['appSettings' => $this->settingsService->getAppSettings()];
		});
	}
	/**
	 * Read user preferences
	 * @NoAdminRequired
	 */
	public function getUserSettings(): DataResponse {
		return $this->response(function (): array {
			return ['appSettings' => $this->settingsService->getUserSettings()];
		});
	}

	/**
	 * Write user preferences
	 * @NoAdminRequired
	 */
	public function writeUserSettings(array $appSettings): DataResponse {
		$this->settingsService->writeUserSettings($appSettings);
		return $this->response(function (): array {
			return ['appSettings' => $this->settingsService->getAppSettings()];
		});
	}
}
