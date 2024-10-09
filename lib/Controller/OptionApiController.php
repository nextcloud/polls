<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\OptionService;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class OptionApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private OptionService $optionService,
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
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/poll/{pollId}/options', requirements: ['apiVersion' => '(v2)'])]
	public function list(int $pollId): DataResponse {
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
	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/poll/{pollId}/option', requirements: ['apiVersion' => '(v2)'])]
	public function add(int $pollId, int $timestamp = 0, string $pollOptionText = '', int $duration = 0): DataResponse {
		return $this->responseCreate(fn () => ['option' => $this->optionService->add($pollId, $timestamp, $pollOptionText, $duration)]);
	}


	/**
	 * Add mulitple new options
	 * @param int $pollId poll id
	 * @param string $text Options text for text poll
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/poll/{pollId}/options', requirements: ['apiVersion' => '(v2)'])]
	public function addBulk(int $pollId, string $text = ''): DataResponse {
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
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/option/{optionId}', requirements: ['apiVersion' => '(v2)'])]
	public function update(int $optionId, int $timestamp = 0, string $text = '', int $duration = 0): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->update($optionId, $timestamp, $text, $duration)]);
	}

	/**
	 * Delete option
	 * @param int $optionId option id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/{apiVersion}/option/{optionId}', requirements: ['apiVersion' => '(v2)'])]
	public function delete(int $optionId): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId)]);
	}

	/**
	 * Restore option
	 * @param int $optionId option id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/option/{optionId}/restore', requirements: ['apiVersion' => '(v2)'])]
	public function restore(int $optionId): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->delete($optionId, true)]);
	}

	/**
	 * Switch option confirmation
	 * @param int $optionId option id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/option/{optionId}/confirm', requirements: ['apiVersion' => '(v2)'])]
	public function confirm(int $optionId): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->confirm($optionId)]);
	}

	/**
	 * Set order position for option
	 * @param int $optionId option id
	 * @param int $order option's new position
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/option/{optionId}/order/{order}', requirements: ['apiVersion' => '(v2)'])]
	public function setOrder(int $optionId, int $order): DataResponse {
		return $this->response(fn () => ['option' => $this->optionService->setOrder($optionId, $order)]);
	}
}
