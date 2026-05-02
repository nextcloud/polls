<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\DateInterval;
use OCA\Polls\Model\DateTimeImmutable;
use OCA\Polls\Model\Sequence;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type PollsOption from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsVote from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsSimpleOption from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsSequence from \OCA\Polls\ResponseDefinitions
 */
class OptionApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private OptionService $optionService,
		private VoteService $voteService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all options of a poll
	 * 200: Returns list of options
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{options: list<PollsOption>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/options')]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['options' => array_values(array_map(fn ($o) => $o->jsonSerialize(), $this->optionService->list($pollId)))]);
	}

	/**
	 * Add a new option
	 * 201: Option added
	 * @param int $pollId Poll id
	 * @param PollsSimpleOption $option The array containing the option data
	 * @param bool $voteYes Vote yes for this option and for all generated sequence
	 * @param PollsSequence|null $sequence Sequence of the option
	 * @return DataResponse<Http::STATUS_CREATED, array{option: PollsOption, repetitions: list<PollsOption>, options: list<PollsOption>, votes: list<PollsVote>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/option')]
	public function add(
		int $pollId,
		array $option,
		bool $voteYes = false,
		?array $sequence = null,
	): DataResponse {
		return $this->response(function () use ($pollId, $option, $voteYes, $sequence): array {
			$result = $this->optionService->addWithSequenceAndAutoVote(
				$pollId,
				SimpleOption::fromArray($option),
				$voteYes,
				Sequence::fromArray($sequence),
			);
			return [
				'option' => $result['option']->jsonSerialize(),
				'repetitions' => array_map(fn ($o) => $o->jsonSerialize(), $result['repetitions']),
				'options' => array_values(array_map(fn ($o) => $o->jsonSerialize(), $this->optionService->list($pollId))),
				'votes' => array_values(array_map(fn ($v) => $v->jsonSerialize(), $this->voteService->list($pollId))),
			];
		}, Http::STATUS_CREATED);
	}

	/**
	 * Add multiple new options from text
	 * 201: Options added
	 * @param int $pollId Poll id
	 * @param string $text Options text (newline-separated) for text poll
	 * @return DataResponse<Http::STATUS_CREATED, array{options: list<PollsOption>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/options')]
	public function addBulk(int $pollId, string $text = ''): DataResponse {
		return $this->response(fn () => ['options' => array_values(array_map(fn ($o) => $o->jsonSerialize(), $this->optionService->addBulk($pollId, $text)))], Http::STATUS_CREATED);
	}

	/**
	 * Update option
	 * 200: Option updated
	 * @param int $optionId Option id
	 * @param int $timestamp Unix timestamp for date poll
	 * @param string $text Option text for text poll
	 * @param int $duration Duration of option in seconds
	 * @param string|null $isoTimestamo ISO 8601 timestamp
	 * @param string|null $isoDuration ISO 8601 duration
	 * @return DataResponse<Http::STATUS_OK, array{option: PollsOption}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/option/{optionId}')]
	public function update(int $optionId, int $timestamp = 0, string $text = '', int $duration = 0, ?string $isoTimestamo = null, ?string $isoDuration = null): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->update(
			$optionId,
			$text,
			new DateTimeImmutable($isoTimestamo ?? $timestamp),
			new DateInterval($isoDuration ?? $duration),
		)->jsonSerialize()]);
	}

	/**
	 * Delete option
	 * 200: Option deleted
	 * @param int $optionId Option id
	 * @return DataResponse<Http::STATUS_OK, array{option: PollsOption}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/option/{optionId}')]
	public function delete(int $optionId): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId)->jsonSerialize()]);
	}

	/**
	 * Restore option
	 * 200: Option restored
	 * @param int $optionId Option id
	 * @return DataResponse<Http::STATUS_OK, array{option: PollsOption}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/option/{optionId}/restore')]
	public function restore(int $optionId): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId, true)->jsonSerialize()]);
	}

	/**
	 * Switch option confirmation
	 * 200: Option confirmation toggled
	 * @param int $optionId Option id
	 * @return DataResponse<Http::STATUS_OK, array{option: PollsOption}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/option/{optionId}/confirm')]
	public function confirm(int $optionId): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)->jsonSerialize()]);
	}

	/**
	 * Set order position for option
	 * 200: Option order updated
	 * @param int $optionId Option id
	 * @param int $order Option's new position
	 * @return DataResponse<Http::STATUS_OK, array{options: list<PollsOption>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/option/{optionId}/order/{order}')]
	public function setOrder(int $optionId, int $order): DataResponse {
		return $this->response(fn () => ['options' => array_map(fn ($o) => $o->jsonSerialize(), $this->optionService->setOrder($optionId, $order))]);
	}
}
