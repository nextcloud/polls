<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use OCA\Polls\Db\Share;

$fm->define('OCA\Polls\Db\Share')->setDefinitions([
	'type' => Share::TYPE_USER,
	'token' => function () {
		return bin2hex(random_bytes(16));
	},
	'userId' => function () {
		return bin2hex(random_bytes(8));
	},
	'emailAddress' => function () {
		return bin2hex(random_bytes(8)) . '@example.com';
	},
	'displayName' => function () {
		return bin2hex(random_bytes(8));
	},
	'invitationSent' => 0,
	'reminderSent' => 0,
	'deleted' => 0,
]);
