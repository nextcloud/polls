<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 René Gieling <github@dartcafe.de>
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

use Closure;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCA\Polls\Model\Acl;
use OCA\Polls\UserSession;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class BasePublicController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected Acl $acl,
		protected ShareMapper $shareMapper,
		protected UserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function response(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseLong(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (NoUpdatesException $e) {
			return new JSONResponse([], Http::STATUS_NOT_MODIFIED);
		}
	}
	/**
	 * responseCreate
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseCreate(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback(), Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
