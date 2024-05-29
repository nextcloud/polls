<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use League\FactoryMuffin\Faker\Facade as Faker;

/**
 * General factory for the vote model.
 */
$fm->define('OCA\Polls\Db\Vote')->setDefinitions([
	'userId' => Faker::firstNameMale(),
	'voteOptionText' => Faker::text(255),
	'voteAnswer' => 'yes',
]);
