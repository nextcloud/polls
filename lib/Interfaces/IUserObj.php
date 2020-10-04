<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andrew Brown <andrew@casabrown.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Polls\Interfaces;

/**
 * IUserObj Interface
 */
interface IUserObj {

	/**
	 * getId
	 * @return string
	 */
	public function getId();

	/**
	 * getId
	 * @return string
	 */
	public function getUser();

	/**
	 * getType
	 * @return string
	 */
	public function getType();

	/**
	 * getLanguage
	 * @return string
	 */
	public function getLanguage();


	/**
	 * getDisplayName
	 * @return string
	 */
	public function getDisplayName();

	/**
	 * getDescription
	 * @return string
	 */
	public function getDescription();

	/**
	 * getIcon
	 * @return string
	 */
	public function getIcon();

	/**
	 * getOrganisation
	 * @return string
	 */
	public function getOrganisation();

	/**
	 * getEmailAddress
	 * @return string
	 */
	public function getEmailAddress();

	/**
	 * search
	 * @param string $query
	 * @param array $skip - list of items zu skip
	 * @return self[]
	 */
	public static function search($query);
}
