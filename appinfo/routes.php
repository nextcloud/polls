<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		// REST-API calls
		['name' => 'base_api#preflighted_cors', 'url' => '/api/v1.0/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
	],
];
