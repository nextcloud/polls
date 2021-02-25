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

use DateTime;
use DateInterval;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\CalendarService;

class OptionController extends Controller {

	/** @var OptionService */
	private $optionService;

	/** @var CalendarService */
	private $calendarService;

	use ResponseHandle;

	public function __construct(
		string $appName,
		IRequest $request,
		OptionService $optionService,
		CalendarService $calendarService
	) {
		parent::__construct($appName, $request);
		$this->optionService = $optionService;
		$this->calendarService = $calendarService;
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 */
	public function list($pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return ['options' => $this->optionService->list($pollId)];
		});
	}

	/**
	 * Add a new option
	 * @NoAdminRequired
	 */
	public function add($pollId, $timestamp = 0, $pollOptionText = '', $duration = 0): DataResponse {
		return $this->responseCreate(function () use ($pollId, $timestamp, $pollOptionText, $duration) {
			return ['option' => $this->optionService->add($pollId, $timestamp, $pollOptionText, $duration)];
		});
	}

	/**
	 * Update option
	 * @NoAdminRequired
	 */
	public function update($optionId, $timestamp, $pollOptionText, $duration): DataResponse {
		return $this->response(function () use ($optionId, $timestamp, $pollOptionText, $duration) {
			return ['option' => $this->optionService->update($optionId, $timestamp, $pollOptionText, $duration)];
		});
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 */
	public function delete($optionId): DataResponse {
		return $this->responseDeleteTolerant(function () use ($optionId) {
			return ['option' => $this->optionService->delete($optionId)];
		});
	}

	/**
	 * Switch option confirmation
	 * @NoAdminRequired
	 */
	public function confirm($optionId): DataResponse {
		return $this->response(function () use ($optionId) {
			return ['option' => $this->optionService->confirm($optionId)];
		});
	}

	/**
	 * Reorder options
	 * @NoAdminRequired
	 */
	public function reorder($pollId, $options): DataResponse {
		return $this->response(function () use ($pollId, $options) {
			return ['options' => $this->optionService->reorder($pollId, $options)];
		});
	}

	/**
	 * Reorder options
	 * @NoAdminRequired
	 */
	public function sequence($optionId, $step, $unit, $amount): DataResponse {
		return $this->response(function () use ($optionId, $step, $unit, $amount) {
			return ['options' => $this->optionService->sequence($optionId, $step, $unit, $amount)];
		});
	}

	/**
	 * findCalendarEvents
	 * @NoAdminRequired
	 */
	public function findCalendarEvents($optionId): DataResponse {
		return $this->response(function () use ($optionId) {
			$option = $this->optionService->get($optionId);
			$searchFrom = new DateTime();
			$searchTo = new DateTime();
			// Search calendar entries which end inside one hour before option start time
			$searchFrom = $searchFrom->setTimestamp($option->getTimestamp())->sub(new DateInterval('PT1H'));
			// Search calendar entries which start inside one hour after option end time
			$searchTo = $searchTo->setTimestamp($option->getTimestamp() + $option->getDuration())->add(new DateInterval('PT1H'));
			$events = $this->calendarService->getEvents($searchFrom, $searchTo);
			return ['events' => $events];
		});
	}
}
