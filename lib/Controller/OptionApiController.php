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

use \Exception;
use \Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCA\Polls\Service\OptionService;

class OptionApiController extends ApiController {

	/** @var OptionService */
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
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		try {
			return new DataResponse(['options' => $this->optionService->list($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}


	/**
	 * Add a new option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param string $pollOptionText
	 * @param int $timestamp
	 * @return DataResponse
	 */
	public function add($pollId, $timestamp = 0, $pollOptionText = '') {
		try {
			return new DataResponse(['option' => $this->optionService->add($pollId, $timestamp, $pollOptionText)], Http::STATUS_CREATED);
		} catch (UniqueConstraintViolationException $e) {
			return new DataResponse(['error' => 'Option exists'], Http::STATUS_CONFLICT);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}


	/**
	 * Update option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param array $option
	 * @return DataResponse
	 */
	public function update($optionId, $timestamp = 0, $pollOptionText = '') {
		try {
			return new DataResponse(['option' => $this->optionService->update($optionId, $timestamp, $pollOptionText)], Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $optionId
	 * @return DataResponse
	 */
	public function delete($optionId) {
		try {
			return new DataResponse(['option' => $this->optionService->delete($optionId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option does not exist'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Switch option confirmation
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $optionId
	 * @return DataResponse
	 */
	public function confirm($optionId) {
		try {
			return new DataResponse(['option' => $this->optionService->confirm($optionId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option does not exist'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Set order position for option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param array $option
	 * @return DataResponse
	 */
	public function setOrder($optionId, $order) {
		try {
			return new DataResponse(['option' => $this->optionService->setOrder($optionId, $order)], Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}
}
