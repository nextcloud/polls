<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use League\FactoryMuffin\Faker\Facade as Faker;

/**
 * General factory for the text model.
 */
$fm->define('OCA\Polls\Db\Option')->setDefinitions([
	'owner' => Faker::firstNameMale(),
	'released' => function () {
		$date = new DateTime('now');
		return $date->getTimestamp();
	},
	'pollOptionText' => Faker::text(255),
	'timestamp' => 0,
	'isoTimeStamp' => null,
	'isoDuration' => null,
	'order' => 0,
	'confirmed' => 0,
	'duration' => 0,
]);
