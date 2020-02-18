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

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\IRequest;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCP\ILogger;

class SystemController extends Controller {

	private $userId;
	private $logger;
	private $systemConfig;
	private $groupManager;
	private $userManager;
	private $voteMapper;
	private $shareMapper;


	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param $userId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param IConfig $systemConfig
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param VoteMapper $voteMapper
	 * @param ShareMapper $shareMapper
	 */
	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		ILogger $logger,
		IConfig $systemConfig,
		IGroupManager $groupManager,
		IUserManager $userManager,
		VoteMapper $voteMapper,
		ShareMapper $shareMapper
	) {
		parent::__construct($appName, $request);
		$this->voteMapper = $voteMapper;
		$this->shareMapper = $shareMapper;
		$this->logger = $logger;
		$this->userId = $UserId;
		$this->systemConfig = $systemConfig;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
	}

	/**
	 * Get a list of NC users, groups and contacts
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @param string $query
	 * @param bool $getGroups - search in groups
	 * @param bool $getUsers - search in site users
	 * @param bool $getContacts - search in contacs
	 * @param array $skipGroups - group names to skip in return array
	 * @param array $skipUsers - user names to skip in return array
	 * @return DataResponse
	 */
	public function getSiteUsersAndGroups($query = '', $getGroups = true, $getUsers = true, $getContacts = true, $skipGroups = array(), $skipUsers = array()) {
		$list = array();
		if ($getGroups) {
			$groups = $this->groupManager->search($query);
			foreach ($groups as $group) {
				if (!in_array($group->getGID(), $skipGroups)) {
					$list[] = [
						'id' => $group->getGID(),
						'user' => $group->getGID(),
						'organisation' => '',
						'displayName' => $group->getGID(),
						'emailAddress' => '',
						'desc' => 'Group',
						'type' => 'group',
						'icon' => 'icon-group',
						'avatarURL' => '',
						'avatar' => '',
						'lastLogin' => '',
						'cloudId' => ''

					];
				}
			}
		}

		if ($getUsers) {
			$users = $this->userManager->searchDisplayName($query);
			foreach ($users as $user) {
				if (!in_array($user->getUID(), $skipUsers)) {
					$list[] = [
						'id' => $user->getUID(),
						'user' => $user->getUID(),
						'displayName' => $user->getDisplayName(),
						'organisation' => '',
						'emailAddress' => $user->getEMailAddress(),
						'desc' => 'User',
						'type' => 'user',
						'icon' => 'icon-user',
						'avatarURL' => '',
						'avatar' => '',
						'lastLogin' => $user->getLastLogin(),
						'cloudId' => $user->getCloudId()
					];
				}
			}
		}

		$contactsManager = \OC::$server->getContactsManager();


		if ($getContacts && $contactsManager->isEnabled()) {
			$contacts = $contactsManager->search($query, array('FN', 'EMAIL', 'ORG', 'CATEGORIES'));

			foreach ($contacts as $contact) {
				if (!array_key_exists('isLocalSystemBook', $contact) && array_key_exists('EMAIL', $contact)) {

					$emailAdresses = $contact['EMAIL'];

					if (!is_array($emailAdresses)) {
						$emailAdresses = array($emailAdresses);
					} else {
						// take the first eMail address for now
						$emailAdresses = array($emailAdresses[0]);
					}

					foreach ($emailAdresses as $emailAddress) {
						$list[] = [
							'id' => $contact['UID'],
							'user' => $contact['FN'],
							'displayName' => $contact['FN'],
							'organisation' => isset($contact['ORG']) ? $contact['ORG'] : '',
							'emailAddress' => $emailAddress,
							'desc' => 'Contact',
							'type' => 'contact',
							'icon' => 'icon-mail',
							'avatarURL' => '',
							'avatar' => isset($contact['PHOTO']) ? $contact['PHOTO'] : '',
							'lastLogin' => '',
							'cloudId' => ''
						];
					}

				}
			}

		}

		return new DataResponse([
			'siteusers' => $list
		], Http::STATUS_OK);
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @PublicPage
	 * @return DataResponse
	 */
	public function validatePublicUsername($pollId, $userName) {
		$list = array();

		$groups = $this->groupManager->search('');
		foreach ($groups as $group) {
			$list[] = [
				'id' => $group->getGID(),
				'user' => $group->getGID(),
				'type' => 'group',
				'displayName' => $group->getGID(),
			];
		}

		$users = $this->userManager->searchDisplayName('');
		foreach ($users as $user) {
			$list[] = [
				'id' => $user->getUID(),
				'user' => $user->getUID(),
				'type' => 'user',
				'displayName' => $user->getDisplayName(),
			];
		}

		$votes = $this->voteMapper->findParticipantsByPoll($pollId);
		foreach ($votes as $vote) {
			if ($vote->getUserId() !== '' && $vote->getUserId() !== null) {
				$list[] = [
					'id' => $vote->getUserId(),
					'user' => $vote->getUserId(),
					'type' => 'participant',
					'displayName' => $vote->getUserId(),
				];
			}
		}

		$shares = $this->shareMapper->findByPoll($pollId);
		foreach ($shares as $share) {
			if ($share->getUserId() !== '' && $share->getUserId() !== null) {
				$list[] = [
					'id' => $share->getUserId(),
					'user' => $share->getUserId(),
					'type' => 'share',
					'displayName' => $share->getUserId(),
				];
			}
		}

		foreach ($list as $element) {
			if (strtolower(trim($userName)) === strtolower(trim($element['id'])) || strtolower(trim($userName)) === strtolower(trim($element['displayName']))) {
				return new DataResponse([
					'result' => false
				], Http::STATUS_FORBIDDEN);
			}
		}

		return new DataResponse([
			'result' => true,
			'list' => $list
		], Http::STATUS_OK);
	}

	public function getDisplayName() {
		$this->userManager = \OC::$server->getUserManager();

		if (\OC::$server->getUserManager()->get($this->userId) instanceof IUser) {
			return \OC::$server->getUserManager()->get($this->userId)->getDisplayName();
		} else {
			return $this->userId;
		}
	}



	//
	//
	// /**
	//  * Get some system informations
	//  * @NoAdminRequired
	//  * @return DataResponse
	//  */
	// public function getSystem() {
	// 	$data = array();
	//
	// 	$data['system'] = [
	// 		'versionArray' => \OCP\Util::getVersion(),
	// 		'version' => implode('.', \OCP\Util::getVersion()),
	// 		'vendor' => $this->getVendor(),
	// 		'language' => $this->systemConfig->getUserValue($this->userId, 'core', 'lang')
	// 	];
	//
	// 	return new DataResponse($data, Http::STATUS_OK);
	// }
}
