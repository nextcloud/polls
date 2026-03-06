<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use League\FactoryMuffin\Faker\Facade as Faker;
use OCA\Polls\Db\Share;

$fm->define('OCA\Polls\Db\Share')->setDefinitions([
	'type' => Share::TYPE_USER,
	'token' => function () {
		return bin2hex(random_bytes(16));
	},
	'userId' => Faker::firstNameMale(),
	'emailAddress' => Faker::safeEmail(),
	'displayName' => Faker::lastName(),
	'invitationSent' => 0,
	'reminderSent' => 0,
	'deleted' => 0,
]);
