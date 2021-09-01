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

use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\Polls\Model\Group;

class SettingsController extends Controller {

	/** @var IConfig */
	private $config;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	/**
	 * Read globals
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getAppSettings(): DataResponse {
		return $this->response(function (): array {
			$appSettings = json_decode($this->config->getAppValue('polls', 'globals'));

			// convert group ids to group objects
			foreach ($appSettings->allowPublicSharesGroups as &$group) {
				$group = new Group($group);
			}
			foreach ($appSettings->allowAllAccessGroups as &$group) {
				$group = new Group($group);
			}
			foreach ($appSettings->allowPollCreationGroups as &$group) {
				$group = new Group($group);
			}
			return ['appSettings' => $appSettings];
		});
	}

	/**
	 * Write globals
	 * @NoCSRFRequired
	 */
	public function writeAppSettings(array $appSettings): DataResponse {
		// reduce groups to their ids
		$appSettings['allowAllAccessGroups'] = array_column($appSettings['allowAllAccessGroups'], 'id');
		$appSettings['allowPublicSharesGroups'] = array_column($appSettings['allowPublicSharesGroups'], 'id');
		$appSettings['allowPollCreationGroups'] = array_column($appSettings['allowPollCreationGroups'], 'id');

		return $this->response(function () use ($appSettings) {
			$this->config->setAppValue('polls', 'globals', json_encode($appSettings));
			return ['appSettings' => $this->getAppSettings()];
		});
	}

}
