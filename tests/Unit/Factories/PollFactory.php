<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\Polls\Db\Poll;

$fm->define('OCA\Polls\Db\Poll')->setDefinitions([
	'type' => 'textPoll',
	'title' => function () {
		return bin2hex(random_bytes(16));
	},
	'votingVariant' => Poll::VARIANT_SIMPLE,
	'description' => function () {
		return bin2hex(random_bytes(64));
	},
	'owner' => function () {
		return bin2hex(random_bytes(8));
	},
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
	'useNo' => 1,
	'misc_settings' => '',
]);
