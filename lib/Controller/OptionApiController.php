<?php

declare(strict_types=1);
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
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class OptionApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private OptionService $optionService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all options of given poll
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->list($pollId)]);
	}

	/**
	 * Add a new option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => ['option' => $this->optionService->add($pollId, $timestamp, $pollOptionText, $duration)]);
	}


	/**
	 * Update option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function update(int $optionId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->update($optionId, $timestamp, $pollOptionText, $duration)]);
	}

	/**
	 * Delete option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function delete(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId)]);
	}

	/**
	 * Restore option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function restore(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId, restore: true)]);
	}

	/**
	 * Switch option confirmation
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function confirm(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)]);
	}

	/**
	 * Set order position for option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function setOrder(int $optionId, int $order): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->setOrder($optionId, $order)]);
	}
}
