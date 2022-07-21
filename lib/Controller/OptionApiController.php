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

use OCA\Polls\Service\OptionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class OptionApiController extends BaseApiController {

	/** @var OptionService */
	private $optionService;

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
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->list($pollId)]);
	}

	/**
	 * Add a new option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => ['option' => $this->optionService->add($pollId, $timestamp, $pollOptionText, $duration)]);
	}


	/**
	 * Update option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function update(int $optionId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->update($optionId, $timestamp, $pollOptionText, $duration)]);
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(int $optionId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => ['option' => $this->optionService->delete($optionId)]);
	}

	/**
	 * Switch option confirmation
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function confirm(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)]);
	}

	/**
	 * Set order position for option
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function setOrder(int $optionId, int $order): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->setOrder($optionId, $order)]);
	}
}
