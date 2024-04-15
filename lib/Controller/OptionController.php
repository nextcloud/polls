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

use OCA\Polls\Service\CalendarService;
use OCA\Polls\Service\OptionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class OptionController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private OptionService $optionService,
		private CalendarService $calendarService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 */
	public function list(int $pollId): JSONResponse {
		return $this->response(function () use ($pollId) {
			return ['options' => $this->optionService->list($pollId)];
		});
	}

	/**
	 * Add a new option
	 * @NoAdminRequired
	 */
	public function add(int $pollId, int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => ['option' => $this->optionService->add($pollId, $timestamp, $text, $duration)]);
	}
	
	/**
	 * Add mulitple new option
	 * @NoAdminRequired
	 */
	public function addBulk(int $pollId, string $text = ''): JSONResponse {
		return $this->responseCreate(fn () => ['options' => $this->optionService->addBulk($pollId, $text)]);
	}

	/**
	 * Update option
	 * @NoAdminRequired
	 */
	public function update(int $optionId, int $timestamp, string $text, int $duration): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->update($optionId, $timestamp, $text, $duration)]);
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 */
	public function delete(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId)]);
	}

	/**
	 * Restore option
	 * @NoAdminRequired
	 */
	public function restore(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId, true)]);
	}

	/**
	 * Switch option confirmation
	 * @NoAdminRequired
	 */
	public function confirm(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)]);
	}

	/**
	 * Reorder options
	 * @NoAdminRequired
	 */
	public function reorder(int $pollId, array $options): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->reorder($pollId, $options)]);
	}

	/**
	 * Reorder options
	 * @NoAdminRequired
	 */
	public function sequence(int $optionId, int $step, string $unit, int $amount): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->sequence($optionId, $step, $unit, $amount)]);
	}

	/**
	 * Reorder options
	 * @NoAdminRequired
	 */
	public function shift(int $pollId, int $step, string $unit): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->shift($pollId, $step, $unit)]);
	}

	/**
	 * findCalendarEvents
	 * @NoAdminRequired
	 */
	public function findCalendarEvents(int $optionId, string $tz): JSONResponse {
		return $this->response(fn () => ['events' => $this->calendarService->getEvents($optionId)]);
	}
}
