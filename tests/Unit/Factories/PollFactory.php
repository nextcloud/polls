<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use League\FactoryMuffin\Faker\Facade as Faker;
use OCA\Polls\Db\Poll;
/**
 * General factory for the poll model.
 */
$fm->define('OCA\Polls\Db\Poll')->setDefinitions([
	'type' => 'textPoll',
	'title' => Faker::text(124),
	'votingVariant' => Poll::VARIANT_SIMPLE,
	'description' => Faker::text(255),
	'owner' => Faker::firstNameMale(),
	'created' => function () {
		$date = new DateTime('today');
		return $date->getTimestamp();
	},
	'lastInteraction' => function () {
		$date = new DateTime('today');
		return $date->getTimestamp();
	},
	'expire' => function () {
		$date = new DateTime('tomorrow');
		return $date->getTimestamp();
	},
	'deleted' => 0,
	'access' => Poll::ACCESS_OPEN,
	'anonymous' => 0,
	'allowComment' => 1,
	'allowMaybe' => 1,
	'allowProposals' => 1,
	'proposalsExpire' => function () {
		$date = new DateTime('tomorrow');
		return $date->getTimestamp();
	},
	'voteLimit' => 0,
	'optionLimit' => 0,
	'showResults' => Poll::SHOW_RESULTS_ALWAYS,
	'adminAccess' => 0,
	'hideBookedUp' => 0,
	'useNo' => 0,
	'misc_settings' => '',
]);
