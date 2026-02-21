<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use DateInterval;
use DateTimeImmutable;
use OCA\Polls\Model\Sequence;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\Service\CalendarService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type SimpleOptionsArray from SimpleOption
 * @psalm-import-type SequenceArray from Sequence
 */
class OptionController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private OptionService $optionService,
		private CalendarService $calendarService,
		private VoteService $voteService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all options of given poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/options')]
	public function list(int $pollId): JSONResponse {
		return $this->response(function () use ($pollId) {
			return ['options' => $this->optionService->list($pollId)];
		});
	}

	/**
	 * Add a new option
	 * @param int $pollId poll id
	 * @param SimpleOptionsArray $option Options text for text poll
	 * @param bool $voteYes vote yes
	 * @param SequenceArray $sequence sequence of new options
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/option')]
	public function add(
		int $pollId,
		array $option,
		bool $voteYes = false,
		?array $sequence = null,
	): JSONResponse {
		return $this->response(fn () => array_merge(
			$this->optionService->addWithSequenceAndAutoVote(
				$pollId,
				SimpleOption::fromArray($option),
				$voteYes,
				Sequence::fromArray($sequence),
			),
			['options' => $this->optionService->list($pollId)],
			['votes' => $this->voteService->list($pollId)],
		),
			Http::STATUS_CREATED);
	}

	/**
	 * Add mulitple new options
	 * @param int $pollId poll id
	 * @param string $text Options text for text poll
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/option/bulk')]
	public function addBulk(int $pollId, string $text = ''): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->addBulk($pollId, $text)], Http::STATUS_CREATED);
	}

	/**
	 * Update option
	 * @param int $optionId option id
	 * @param string $isoTimestamp ISO 8601 timestamp for date poll
	 * @param string $text Option text for text poll
	 * @param string $isoDuration ISO 8601 duration for date poll
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/option/{optionId}')]
	public function update(int $optionId, string $isoTimestamp, string $text, string $isoDuration): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->update(
			$optionId,
			$text,
			new DateTimeImmutable($isoTimestamp),
			new DateInterval($isoDuration),
		)]);
	}

	/**
	 * Delete option
	 * @param int $optionId option id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/option/{optionId}')]
	public function delete(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId)]);
	}

	/**
	 * Restore option
	 * @param int $optionId option id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/option/{optionId}/restore')]
	public function restore(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId, true)]);
	}

	/**
	 * Switch option confirmation
	 * @param int $optionId option id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/option/{optionId}/confirm')]
	public function confirm(int $optionId): JSONResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)]);
	}

	/**
	 * Reorder options
	 * @param int $pollId option id
	 * @param array $options options in new order
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/options/reorder')]
	public function reorder(int $pollId, array $options): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->reorder($pollId, $options)]);
	}

	/**
	 * clone options in date poll
	 * @param int $optionId clone template
	 * @param array $sequence sequence of new options
	 * @param bool $voteYes vote yes
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/option/{optionId}/sequence')]
	public function sequence(int $optionId, array $sequence, bool $voteYes = false): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->sequence($optionId, Sequence::fromArray($sequence), $voteYes)]);
	}

	/**
	 * Shift options
	 * @param int $pollId poll id
	 * @param int $step step width
	 * @param string $unit Unit for shift steps
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/shift')]
	public function shift(int $pollId, int $step, string $unit): JSONResponse {
		return $this->response(fn () => ['options' => $this->optionService->shift($pollId, $step, $unit)]);
	}

	/**
	 * findCalendarEvents
	 * @param int $optionId option id
	 * @param $tz Timezone to use
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/option/{optionId}/events')]
	public function findCalendarEvents(int $optionId): JSONResponse {
		return $this->response(fn () => ['events' => $this->calendarService->getEvents($optionId)]);
	}
}
