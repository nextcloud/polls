<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * General factory for the poll model.
 */
$fm->define('OCA\Polls\Db\Preferences')->setDefinitions([
	'userId' => function () {
		return 'test_user_' . bin2hex(random_bytes(4));
	},
	'timestamp' => function () {
		$date = new DateTime('today');
		return $date->getTimestamp();
	},
	'preferences' => '{"someJSON":0}',
]);
