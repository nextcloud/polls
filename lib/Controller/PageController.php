<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

use OCA\Polls\AppConstants;
use OCA\Polls\Service\NotificationService;
use OCP\AppFramework\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private NotificationService $notificationService,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		return new TemplateResponse(AppConstants::APP_ID, 'main');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function vote(int $id): TemplateResponse {
		$this->notificationService->removeNotification($id);
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		return new TemplateResponse(AppConstants::APP_ID, 'main');
	}
}
