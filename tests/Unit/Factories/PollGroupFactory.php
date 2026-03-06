<?php
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use League\FactoryMuffin\Faker\Facade as Faker;

$fm->define('OCA\Polls\Db\PollGroup')->setDefinitions([
	'title' => Faker::text(50),
	'description' => Faker::text(255),
]);
