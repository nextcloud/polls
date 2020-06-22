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

use Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;


use OCA\Polls\Service\OptionService;

class OptionApiController extends ApiController {

	private $optionService;

	/**
	 * OptionApiController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param OptionService $optionService
	 */

	public function __construct(
		string $appName,
		IRequest $request,
		OptionService $optionService
	) {
		parent::__construct($appName,
			$request,
			'POST, PUT, GET, DELETE',
            'Authorization, Content-Type, Accept',
            1728000);
		$this->optionService = $optionService;
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		try {
			return new DataResponse($this->optionService->list($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll with id ' . $pollId . ' not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}


	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @param string $pollOptionText
	 * @param integer $timestamp
	 * @return DataResponse
	 */
	public function add($pollId, $pollOptionText = '', $timestamp = 0) {
		$option = [
			'pollId' => $pollId,
			'pollOptionText' => $pollOptionText,
			'timestamp' => $timestamp
		];

		try {
			return new DataResponse($this->optionService->add($option), Http::STATUS_CREATED);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll with id ' . $pollId . ' not found'], Http::STATUS_NOT_FOUND);
		} catch (UniqueConstraintViolationException $e) {
			return new DataResponse(['error' => 'Option exists'], Http::STATUS_CONFLICT);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}


	/**
	 * Update poll option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param array $option
	 * @return DataResponse
	 */
	public function update($option) {
		try {
			return new DataResponse($this->optionService->update($option), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Remove a single option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param integer $optionId
	 * @return DataResponse
	 */
	public function delete($optionId) {
		try {
			return new DataResponse($this->optionService->delete($optionId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option does not exist'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}
}
