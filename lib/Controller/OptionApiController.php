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

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\Polls\Service\OptionService;

class OptionApiController extends ApiController {

	/** @var OptionService */
	private $optionService;

	use ResponseHandle;

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
	 */
	public function list(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return ['options' => $this->optionService->list($pollId)];
		});
	}

	/**
	 * Add a new option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = ''): DataResponse {
		return $this->responseCreate(function () use ($pollId, $timestamp, $pollOptionText) {
			return ['option' => $this->optionService->add($pollId, $timestamp, $pollOptionText)];
		});
	}


	/**
	 * Update option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function update(int $optionId, int $timestamp = 0, string $pollOptionText = ''): DataResponse {
		return $this->response(function () use ($optionId, $timestamp, $pollOptionText) {
			return ['option' => $this->optionService->update($optionId, $timestamp, $pollOptionText)];
		});
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(int $optionId): DataResponse {
		return $this->responseDeleteTolerant(function () use ($optionId) {
			return ['option' => $this->optionService->delete($optionId)];
		});
	}

	/**
	 * Switch option confirmation
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function confirm(int $optionId): DataResponse {
		return $this->response(function () use ($optionId) {
			return ['option' => $this->optionService->confirm($optionId)];
		});
	}

	/**
	 * Set order position for option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function setOrder(int $optionId, int $order): DataResponse {
		return $this->response(function () use ($optionId, $order) {
			return ['option' => $this->optionService->setOrder($optionId, $order)];
		});
	}
}
