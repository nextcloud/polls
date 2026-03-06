<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$fm->define('OCA\Polls\Db\Comment')->setDefinitions([
	'userId' => function () {
		return bin2hex(random_bytes(8));
	},
	'timestamp' => function () {
		$date = new DateTime('today');
		return $date->getTimestamp();
	},
	'comment' => function () {
		return bin2hex(random_bytes(64));
	},
]);
