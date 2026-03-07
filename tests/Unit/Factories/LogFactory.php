<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$fm->define('OCA\Polls\Db\Log')->setDefinitions([
	'created' => function () {
		$date = new DateTime('yesterday');
		return $date->getTimestamp();
	},
	'processed' => 0,
	'userId' => function () {
		return bin2hex(random_bytes(8));
	},
	'displayName' => function () {
		return bin2hex(random_bytes(8));
	},
	'messageId' => 'addPoll',
]);
