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

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\Exception;

trait ResponseHandle {

	/**
	 * response
	 * @NoAdminRequired
	 * @param Closure $callback
	 * @return DataResponse
	 */
	protected function response(Closure $callback) {
		try {
			return new DataResponse($callback(), Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * responseCreate
	 * @NoAdminRequired
	 * @param Closure $callback
	 * @return DataResponse
	 */
	protected function responseCreate(Closure $callback) {
		try {
			return new DataResponse($callback(), Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * responseDeleteTolerant
	 * @NoAdminRequired
	 * @param Closure $callback
	 * @return DataResponse
	 */
	protected function responseDeleteTolerant(Closure $callback) {
		try {
			return new DataResponse($callback(), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['message' => 'Not found, assume already deleted'], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
