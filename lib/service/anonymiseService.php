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


namespace OCA\Polls\Service;


use OCA\Social\AP;
use OCA\Social\Db\ActorsRequest;
use OCA\Social\Db\CacheDocumentsRequest;
use OCA\Social\Exceptions\CacheContentException;
use OCA\Social\Exceptions\CacheContentMimeTypeException;
use OCA\Social\Exceptions\CacheDocumentDoesNotExistException;
use OCA\Social\Exceptions\ItemUnknownException;
use OCA\Social\Exceptions\RequestContentException;
use OCA\Social\Exceptions\RequestNetworkException;
use OCA\Social\Exceptions\RequestResultSizeException;
use OCA\Social\Exceptions\RequestServerException;
use OCA\Social\Exceptions\SocialAppConfigException;
use OCA\Social\Exceptions\UrlCloudException;
use OCP\IURLGenerator;


class DocumentService {


	const ERROR_SIZE = 1;
	const ERROR_MIMETYPE = 2;
	const ERROR_PERMISSION = 3;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var CacheDocumentsRequest */
	private $cacheDocumentsRequest;

	/** @var ActorsRequest */
	private $actorRequest;


	/** @var CacheDocumentService */
	private $cacheService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * DocumentInterface constructor.
	 *
	 * @param IUrlGenerator $urlGenerator
	 * @param CacheDocumentsRequest $cacheDocumentsRequest
	 * @param ActorsRequest $actorRequest
	 * @param CacheDocumentService $cacheService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IUrlGenerator $urlGenerator, CacheDocumentsRequest $cacheDocumentsRequest,
		ActorsRequest $actorRequest,
		CacheDocumentService $cacheService,
		ConfigService $configService, MiscService $miscService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->cacheDocumentsRequest = $cacheDocumentsRequest;
		$this->actorRequest = $actorRequest;
		$this->configService = $configService;
		$this->cacheService = $cacheService;
		$this->miscService = $miscService;
	}


	/**
	 * Read all votes of a poll based on the poll id
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return Array
	 */
	private function anonMapper($pollId) {
		$anonList = array();
		$votes = $this->voteMapper->findByPoll($pollId);
		$i = 0;

		foreach ($votes as $element) {
			if (!array_key_exists($element->getUserId(), $anonList)) {
				$anonList[$element->getUserId()] = 'Anonymous ' . ++$i ;
			}
		}

		$votes = $this->voteMapper->findByPoll($pollId);
		foreach ($votes as $element) {
			if (!array_key_exists($element->getUserId(), $anonList)) {
				$anonList[$element->getUserId()] = 'Anonymous ' . ++$i;
			}
		}
		return $anonList;
	}

	/**
	 * Read all votes of a poll based on the poll id
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return Array
	 */
	private function anonymize($array, $pollId, $anomizeField = 'userId') {
		$anonList = $this->anonMapper($pollId);
		$votes = $this->voteMapper->findByPoll($pollId);
		$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		$i = 0;

		for ($i = 0; $i < count($array); ++$i) {
			if ($array[$i][$anomizeField] !== \OC::$server->getUserSession()->getUser()->getUID()) {
				$array[$i][$anomizeField] = $anonList[$array[$i][$anomizeField]];
			}
		}

		return $array;
	}


}
