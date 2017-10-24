<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Kai Schröer <kai@schroeer.co>
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

use League\FactoryMuffin\Faker\Facade as Faker;

/**
 * General factory for the event model.
 */
$fm->define('OCA\Polls\Db\Event')->setDefinitions([
	'hash' => Faker::regexify('[A-Za-z0-9]{16}'),
	'type' => 0,
	'title' => Faker::sentence(10),
	'description' => Faker::sentence(20),
	'created' => Faker::date('Y-m-d H:i:s'),
	'access' => 'registered',
	'expire' => Faker::date('Y-m-d H:i:s'),
	'isAnonymous' => 0,
	'fullAnonymous' => 0
]);
