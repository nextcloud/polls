<?php
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
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
 * General factory for the poll model.
 */
$fm->define('OCA\Polls\Db\Poll')->setDefinitions([
	'type' => 'textPoll',
	'title' => Faker::text(124),
	'description' => Faker::text(255),
	'owner' => Faker::firstNameMale(),
	'created' => function () {
		$date = new DateTime('today');
		return $date->getTimestamp();
	},
	'expire' => function () {
		$date = new DateTime('tomorrow');
		return $date->getTimestamp();
	},
	'deleted' => function () {
		$date = new DateTime('+1 month');
		return $date->getTimestamp();
	},
	'access' => 'public',
	'anonymous' => 0,
	'fullAnonymous' => 0,
	'allowMaybe' => 1,
	'options' => '["yes","no","maybe"]',
	'settings' => '{"someJSON":0}',
	'voteLimit' => 0,
	'showResults' => 'always',
	'adminAccess' => 0,
	'important' => 0
]);
