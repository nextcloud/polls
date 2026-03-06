<?php
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$fm->define('OCA\Polls\Db\PollGroup')->setDefinitions([
	'title' => function () {
		return bin2hex(random_bytes(16));
	},
	'description' => function () {
		return bin2hex(random_bytes(64));
	},
]);
