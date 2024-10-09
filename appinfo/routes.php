<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		// REST-API calls
		['name' => 'baseApiV1#preflighted_cors', 'url' => '/api/v1.0/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
	],
	// 'ocs' => [
	// 	// CORS Preflight
	// 	['name' => 'api#preflightedCors', 'url' => $apiBase . '{path}', 'verb' => 'OPTIONS', 'requirements' => [
	// 		'path' => '.+',
	// 		'apiVersion' => 'v2'
	// 	]],
	// ],
];
