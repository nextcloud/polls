<?php
/**
 * @copyright Copyright (c] 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option] any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

		['name' => 'page#vote_poll', 'url' => '/vote/{pollId}', 'verb' => 'GET'],
		['name' => 'page#vote_public', 'url' => '/s/{pollId}', 'verb' => 'GET'],
		['name' => 'PublicAPI#get', 'url' => '/api/{token}', 'verb' => 'GET' ],

		['name' => 'notification#get', 'url' => '/get/notification/{pollId}', 'verb' => 'GET'],
		['name' => 'notification#set', 'url' => '/set/notification/', 'verb' => 'POST'],

		['name' => 'comment#get', 'url' => '/get/comments/{pollId}', 'verb' => 'GET'],
		['name' => 'comment#test', 'url' => '/get/test/{pollId}', 'verb' => 'GET'],
		['name' => 'comment#write', 'url' => '/write/comment', 'verb' => 'POST'],
		['name' => 'comment#getByToken', 'url' => '/get/comments/s/{token}', 'verb' => 'GET'],

		['name' => 'vote#get', 'url' => '/get/votes/{pollId}', 'verb' => 'GET'],
		['name' => 'vote#write', 'url' => '/write/vote/', 'verb' => 'POST'],
		['name' => 'vote#set', 'url' => '/set/vote/', 'verb' => 'POST'],
		['name' => 'vote#getByToken', 'url' => '/get/votes/s/{token}', 'verb' => 'GET'],

		['name' => 'option#get', 'url' => '/get/options/{pollId}', 'verb' => 'GET'],
		['name' => 'option#add', 'url' => '/add/option/', 'verb' => 'POST'],
		['name' => 'option#update', 'url' => '/update/option/', 'verb' => 'POST'],
		['name' => 'option#remove', 'url' => '/remove/option/', 'verb' => 'POST'],
		['name' => 'option#getByToken', 'url' => '/get/options/s/{token}', 'verb' => 'GET'],

		['name' => 'event#list', 'url' => '/get/events', 'verb' => 'GET'],
		['name' => 'event#get', 'url' => '/get/event/{pollId}', 'verb' => 'GET'],
		['name' => 'event#write', 'url' => '/write/event/', 'verb' => 'POST'],
		['name' => 'event#add', 'url' => '/add/event/', 'verb' => 'POST'],
		['name' => 'event#getByToken', 'url' => '/get/event/s/{token}', 'verb' => 'GET'],

		['name' => 'share#getShares', 'url' => '/get/shares/{pollId}', 'verb' => 'GET'],
		['name' => 'share#write', 'url' => '/write/share', 'verb' => 'POST'],
		['name' => 'share#writeFromUser', 'url' => '/write/share/s', 'verb' => 'POST'],
		['name' => 'share#remove', 'url' => '/remove/share', 'verb' => 'POST'],
		['name' => 'share#get', 'url' => '/get/share/{token}', 'verb' => 'GET'],

		['name' => 'acl#getByToken', 'url' => '/get/aclbytoken/{token}', 'verb' => 'GET'],
		['name' => 'acl#get', 'url' => '/get/acl/{id}', 'verb' => 'GET'],

		['name' => 'system#get_site_users_and_groups', 'url' => '/get/siteusers', 'verb' => 'POST'],
		['name' => 'system#get_system', 'url' => '/get/system', 'verb' => 'GET'],
		['name' => 'system#validate_public_username', 'url' => '/check/username', 'verb' => 'POST']
	]
];
