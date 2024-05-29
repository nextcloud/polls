<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use League\FactoryMuffin\Faker\Facade as Faker;

/**
 * General factory for the comment model.
 */
$fm->define('OCA\Polls\Db\Log')->setDefinitions([
	'created' => function () {
		$date = new DateTime('yesterday');
		return $date->getTimestamp();
	},
	'processed' => 0,
	'userId' => Faker::firstNameMale(),
	'displayName' => Faker::lastName(),
	'messageId' => 'addPoll'
]);
