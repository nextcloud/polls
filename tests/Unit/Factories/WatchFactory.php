<?php
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use OCA\Polls\Db\Watch;

$fm->define('OCA\Polls\Db\Watch')->setDefinitions([
	'table' => Watch::OBJECT_VOTES,
	'updated' => function () {
		return time();
	},
	'sessionId' => function () {
		return bin2hex(random_bytes(16));
	},
]);
