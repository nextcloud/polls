<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$fm->define('OCA\Polls\Db\Subscription')->setDefinitions([
	'userId' => function () {
		return bin2hex(random_bytes(8));
	},
]);
