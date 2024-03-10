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
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->list($pollId)]);
	}

	/**
	 * Add a new option
	 * @param int $pollId poll id
	 * @param int $timestamp timestamp for datepoll
	 * @param string $pollOptionText Option text for text poll
	 * @param int duration duration of option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => ['option' => $this->optionService->addForPoll($pollId, $timestamp, $pollOptionText, $duration)]);
	}


	/**
	 * Add mulitple new options
	 * @param int $pollId poll id
	 * @param string $text Options text for text poll
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function addBulk(int $pollId, string $text = ''): JSONResponse {
		return $this->responseCreate(fn () => ['options' => $this->optionService->addBulk($pollId, $text)]);
	}

	/**
	 * Update option
	 * @param int $optionId Share token
	 * @param int $timestamp timestamp for datepoll
	 * @param string $text Option text for text poll
	 * @param int duration duration of option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function update(int $optionId, int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->update($optionId, $timestamp, $text, $duration)]);
	}

	/**
	 * Delete option
	 * @param int $optionId option id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function delete(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId)]);
	}

	/**
	 * Restore option
	 * @param int $optionId option id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function restore(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId, true)]);
	}

	/**
	 * Switch option confirmation
	 * @param int $optionId option id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function confirm(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)]);
	}

	/**
	 * Set order position for option
	 * @param int $optionId option id
	 * @param int $order place option
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function setOrder(int $optionId, int $order): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->setOrder($optionId, $order)]);
	}
}
