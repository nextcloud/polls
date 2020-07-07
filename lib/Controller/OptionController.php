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

use Exception;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Exceptions\NotAuthorizedException;

use OCA\Polls\Service\OptionService;

class OptionController extends Controller {

	private $optionService;

	/**
	 * OptionController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param OptionService $optionService
	 */

	public function __construct(
		string $appName,
		IRequest $request,
		OptionService $optionService
	) {
		parent::__construct($appName, $request);
		$this->optionService = $optionService;
	}

	/**
	 * Get all options of given poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		return new DataResponse($this->optionService->list($pollId), Http::STATUS_OK);
	}


	/**
	 * getByToken
	 * Read all options of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {
		return new DataResponse($this->optionService->list(0, $token), Http::STATUS_OK);
	}

	/**
	 * Add a new Option to poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param array $option
	 * @return DataResponse
	 */
	public function add($option) {
		return new DataResponse($this->optionService->add($option), Http::STATUS_OK);
	}

	/**
	 * Update poll option
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param array $option
	 * @return DataResponse
	 */
	public function update($option) {
		return new DataResponse($this->optionService->update($option), Http::STATUS_OK);
	}

	/**
	 * Remove a single option
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Option $option
	 * @return DataResponse
	 */
	public function remove($option) {
		return new DataResponse($this->optionService->delete($option['id']), Http::STATUS_OK);
	}

	/**
	 * Set order by order of the given array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @param Array $options
	 * @return DataResponse
	 */
	public function reorder($pollId, $options) {
		return new DataResponse($this->optionService->reorder($pollId, $options), Http::STATUS_OK);
	}
}
