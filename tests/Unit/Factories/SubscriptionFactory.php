<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use League\FactoryMuffin\Faker\Facade as Faker;

/**
 * General factory for the Subscription model.
 */
$fm->define('OCA\Polls\Db\Subscription')->setDefinitions([
	'userId' => Faker::firstNameMale()
]);
