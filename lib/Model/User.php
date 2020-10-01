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


namespace OCA\Polls\Model;
use OCP\IL10N;

class User implements \JsonSerializable {

	const TYPE_USER = 'user';
	const TYPE_GROUP = 'group';
	const TYPE_CONTACTGROUP = 'contactGroup';
	const TYPE_CONTACT = 'contact';
	const TYPE_EMAIL = 'email';
	const TYPE_CIRCLE = 'circle';
	const TYPE_EXTERNAL = 'external';

	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $userId;

	/** @var string */
	private $type;

	/** @var string */
	private $displayName = '';

	/** @var string */
	private $desc = '';

	/** @var string */
	private $emailAddress = '';

	private $contact;
	private $circlesEnabled = false;
	private $contactsEnabled = false;

	/**
	 * User constructor.
	 * @param $type
	 * @param $userId
	 * @param $emailAddress
	 * @param $displayName
	 */
	public function __construct(
		$type,
		$userId,
		$emailAddress = '',
		$displayName = ''
	) {
		$this->l10n = \OC::$server->getL10N('polls');
		$this->type = $type;
		$this->userId = $userId;
		$this->emailAddress = $emailAddress;
		$this->displayName = $displayName;
		$this->loadContact();
		$this->circlesEnabled = \OC::$server->getAppManager()->isEnabledForUser('circles') &&
			(version_compare(\OC::$server->getAppManager()->getAppVersion('circles'), '0.17.1') >= 0);
		$this->contactsEnabled = \OC::$server->getContactsManager()->isEnabled();
	}


	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
	}

	public function setEmailAddress($emailAddress) {
		$this->emailAddress = $emailAddress;
	}

	public function getUserId() {
		return $this->userId;
	}

	public function getType() {
		return $this->type;
	}

	public function getLanguage() {
		if ($this->type === self::TYPE_USER) {
			// Variant: $this->config->getUserValue($this->userId, 'core', 'lang')
			return \OC::$server->getConfig()->getUserValue($this->userId, 'core', 'lang');
		} else {
			return '';
		}
	}

	public function getDisplayName() {
		if ($this->type === self::TYPE_USER) {
			return \OC::$server->getUserManager()->get($this->userId)->getDisplayName();

		} elseif ($this->type === self::TYPE_GROUP) {
			try {
				// since NC19
				return \OC::$server->getGroupManager()->get($this->userId)->getDisplayName();
			} catch (\Exception $e) {
				// until NC18
				return $this->userId;
			}

		} elseif ($this->type === self::TYPE_CONTACTGROUP && $this->contactsEnabled) {
			return $this->userId;

		} elseif ($this->type === self::TYPE_CIRCLE && $this->circlesEnabled) {
			return \OCA\Circles\Api\v1\Circles::detailsCircle($this->userId)->getName();

		} elseif ($this->type === self::TYPE_CONTACT && $this->contactsEnabled) {
			return isset($this->contact['FN']) ? $this->contact['FN'] : '';

		} elseif ($this->displayName) {
			return $this->displayName;

		} else {
			return $this->userId;
		}
	}

	public function getOrganisation() {
		if ($this->type === self::TYPE_CONTACT && $this->contactsEnabled) {
			return isset($this->contact['ORG']) ? $this->contact['ORG'] : '';
		} else {
			return '';
		}
	}

	public function getEmailAddress() {
		if ($this->type === self::TYPE_USER) {
			// Variant: \OC::$server->getConfig()->getUserValue($this->userId, 'settings', 'email'),
			return \OC::$server->getUserManager()->get($this->userId)->getEMailAddress();

		} elseif ($this->type === self::TYPE_CONTACT && $this->contactsEnabled) {
			return isset($this->contact['EMAIL'][0]) ? $this->contact['EMAIL'][0] : '';

		} elseif ($this->type === self::TYPE_EMAIL) {
			return $this->userId;

		} else {
			return $this->emailAddress;
		}
	}

	public function getDesc() {
		if ($this->type === self::TYPE_USER) {
			return $this->l10n->t('User');

		} elseif ($this->type === self::TYPE_GROUP) {
			return $this->l10n->t('Group');

		} elseif ($this->type === self::TYPE_CONTACT && $this->contactsEnabled) {
			$this->desc = $this->l10n->t('Contact');
			if (isset($this->contact['ORG'])) {
				// Add organisation to description
				$this->desc = $this->contact['ORG'];
			}

			if (isset($this->contact['CATEGORIES'])) {
				// Add contact groups to description
				// Add aspace before each comma
				if ($this->desc === $this->l10n->t('Contact')) {
					$this->desc = $this->contact['CATEGORIES'];
				} else {
					$this->desc = $this->desc . ', ' . $this->contact['CATEGORIES'];
				}
			}
			return $this->desc;
		} elseif ($this->type === self::TYPE_CONTACTGROUP && $this->contactsEnabled) {
			return $this->l10n->t('Contact group');

		} elseif ($this->type === self::TYPE_CIRCLE && $this->circlesEnabled) {
			return \OCA\Circles\Api\v1\Circles::detailsCircle($this->userId)->gettypeLongString();

		} elseif ($this->type === self::TYPE_EMAIL) {
			return $this->l10n->t('External email');

		} else {
			return '';
		}
	}

	public function getIcon() {
		if ($this->type === self::TYPE_USER) {
			return 'icon-user';

		} elseif ($this->type === self::TYPE_GROUP) {
			return 'icon-group';

		} elseif ($this->type === self::TYPE_CONTACT && $this->contactsEnabled) {
			return 'icon-mail';

		} elseif ($this->type === self::TYPE_EMAIL) {
			return 'icon-mail';

		} elseif ($this->type === self::TYPE_CONTACTGROUP && $this->contactsEnabled) {
			return 'icon-group';

		} elseif ($this->type === self::TYPE_CIRCLE && $this->circlesEnabled) {
			return 'icon-circle';

		} else {
			return '';
		}
	}

	private function loadContact() {
		if ($this->type === self::TYPE_CONTACT && \OC::$server->getContactsManager()->isEnabled()) {
			// TODO: remove FN in a later version than 1.5
			$contacts = \OC::$server->getContactsManager()->search($this->userId, ['UID', 'FN']);
			if (!$contacts) {
				$this->contact = [];
			} else {
				$this->contact = $contacts[0];
			}
		}
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'userId'        => $this->userId,
			'type'       	=> $this->type,
			'user'          => $this->userId,
			'displayName'	=> $this->getDisplayName(),
			'Organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'desc' 			=> $this->getDesc(),
			'icon'			=> $this->getIcon(),
			'contact'		=> $this->contact,
		];
	}
}
