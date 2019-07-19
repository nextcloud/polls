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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Db\DoesNotExistException;

use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;



class CommentsController extends Controller {

	private $groupManager;
	private $userManager;
	private $eventMapper;
	private $commentMapper;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param IGroupManager $groupManager
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param string $userId
	 * @param EventMapper $eventMapper
	 * @param CommentMapper $commentMapper
	 */
	public function __construct(
		$appName,
		IGroupManager $groupManager,
		IRequest $request,
		IUserManager $userManager,
		$userId,
		EventMapper $eventMapper,
		CommentMapper $commentMapper
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->eventMapper = $eventMapper;
		$this->commentMapper = $commentMapper;
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

		$comments = $this->commentMapper->findByPoll($pollId);
		foreach ($comments as $element) {
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
		$comments = $this->commentMapper->findByPoll($pollId);
		$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		$i = 0;

		for ($i = 0; $i < count($array); ++$i) {
			if ($array[$i][$anomizeField] !== \OC::$server->getUserSession()->getUser()->getUID()) {
				$array[$i][$anomizeField] = $anonList[$array[$i][$anomizeField]];
			}
		}

		return $array;
	}

	/**
	 * read
	 * Read all comments of a poll based on the poll id
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return Array
	 */
	public function read($pollId) {
		$commentsList = array();

		try {
			$event = $this->eventMapper->find($pollId)->read();
			$comments = $this->commentMapper->findByPoll($pollId);
		} catch (DoesNotExistException $e) {
			// return silently
		} finally {
			foreach ($comments as $comment) {
				$commentsList[] = $comment->read();
			}

			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
			if (($event['fullAnonymous'] || ($event['isAnonymous'] && $event['owner'] !== $currentUser))) {
				return $this->anonymize($commentsList, $pollId);
			} else {
				return $commentsList;
			}
		}

	}

	/**
	 * write
	 * Write a new coimment to the db
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $currentUser
	 * @param string $commentContent
	 * @return DataResponse
	 */
	public function write($pollId, $currentUser, $commentContent) {
		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
			$AdminAccess = $this->groupManager->isAdmin($currentUser);
		}

		$comment = new Comment();
		$comment->setPollId($pollId);
		$comment->setUserId($currentUser);
		$comment->setComment($commentContent);
		$comment->setDt(date('Y-m-d H:i:s'));
		$this->commentMapper->insert($comment);
		return new DataResponse(array('result' => 'saved'), Http::STATUS_OK);

	}
}
