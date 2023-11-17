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

use Closure;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;

class BaseController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISession $session,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * response
	 */
	#[NoAdminRequired]
	protected function response(Closure $callback, string $token = ''): JSONResponse {
		if ($token) {
			$this->session->set('ncPollsPublicToken', $token);
		}

		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * response
	 */
	#[NoAdminRequired]
	protected function responseLong(Closure $callback, string $token = ''): JSONResponse {
		if ($token) {
			$this->session->set('ncPollsPublicToken', $token);
		}

		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (NoUpdatesException $e) {
			return new JSONResponse([], Http::STATUS_NOT_MODIFIED);
		}
	}

	/**
	 * responseCreate
	 */
	#[NoAdminRequired]
	protected function responseCreate(Closure $callback, string $token = ''): JSONResponse {
		if ($token) {
			$this->session->set('ncPollsPublicToken', $token);
		}

		try {
			return new JSONResponse($callback(), Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * responseDeleteTolerant
	 */
	#[NoAdminRequired]
	protected function responseDeleteTolerant(Closure $callback, string $token = ''): JSONResponse {
		if ($token) {
			$this->session->set('ncPollsPublicToken', $token);
		}

		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['message' => 'Not found, assume already deleted'], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
