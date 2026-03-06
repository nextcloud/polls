<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$fm->define('OCA\Polls\Db\Option')->setDefinitions([
	'owner' => function () {
		return bin2hex(random_bytes(8));
	},
	'released' => function () {
		$date = new DateTime('now');
		return $date->getTimestamp();
	},
	'pollOptionText' => function () {
		return bin2hex(random_bytes(64));
	},
	'timestamp' => 0,
	'isoTimestamp' => null,
	'isoDuration' => null,
	'order' => 0,
	'confirmed' => 0,
	'duration' => 0,
]);
