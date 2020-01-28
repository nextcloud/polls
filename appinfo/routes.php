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
		['name' => 'page#index', 'url' => '/not-found', 'verb' => 'GET', 'postfix' => 'notfound'],
		['name' => 'page#index', 'url' => '/list/{id}', 'verb' => 'GET', 'postfix' => 'list'],
		['name' => 'page#index', 'url' => '/vote/{id}', 'verb' => 'GET', 'postfix' => 'vote'],
		['name' => 'page#vote_public', 'url' => '/s/{token}', 'verb' => 'GET', 'postfix' => 'public'],
		['name' => 'page#vote_public', 'url' => '/poll/{token}', 'verb' => 'GET', 'postfix' => 'oldpublic'],

		['name' => 'subscription#get', 'url' => '/subscription/get/{pollId}', 'verb' => 'GET'],
		['name' => 'subscription#set', 'url' => '/subscription/set/', 'verb' => 'POST'],

		['name' => 'comment#getByToken', 'url' => '/comments/get/s/{token}', 'verb' => 'GET'],
		['name' => 'comment#writeByToken', 'url' => '/comment/write/s/', 'verb' => 'POST'],
		['name' => 'comment#get', 'url' => '/comments/get/{pollId}', 'verb' => 'GET'],
		['name' => 'comment#write', 'url' => '/comment/write/', 'verb' => 'POST'],
		['name' => 'comment#delete', 'url' => '/comment/delete/', 'verb' => 'POST'],
		['name' => 'comment#deleteByToken', 'url' => '/comment/delete/s/', 'verb' => 'POST'],

		['name' => 'vote#getByToken', 'url' => '/votes/get/s/{token}', 'verb' => 'GET'],
		['name' => 'vote#setByToken', 'url' => '/vote/set/s/', 'verb' => 'POST'],
		['name' => 'vote#get', 'url' => '/votes/get/{pollId}', 'verb' => 'GET'],
		['name' => 'vote#set', 'url' => '/vote/set/', 'verb' => 'POST'],
		['name' => 'vote#write', 'url' => '/vote/write/', 'verb' => 'POST'],

		['name' => 'option#get', 'url' => '/options/get/{pollId}', 'verb' => 'GET'],
		['name' => 'option#add', 'url' => '/option/add/', 'verb' => 'POST'],
		['name' => 'option#update', 'url' => '/option/update/', 'verb' => 'POST'],
		['name' => 'option#remove', 'url' => '/option/remove/', 'verb' => 'POST'],
		['name' => 'option#getByToken', 'url' => '/options/get/s/{token}', 'verb' => 'GET'],

		['name' => 'poll#list', 'url' => '/polls/list/', 'verb' => 'GET'],
		['name' => 'poll#get', 'url' => '/polls/get/{pollId}', 'verb' => 'GET'],
		['name' => 'poll#delete', 'url' => '/polls/delete/{pollId}', 'verb' => 'GET'],
		['name' => 'poll#write', 'url' => '/polls/write/', 'verb' => 'POST'],
		['name' => 'poll#clone', 'url' => '/polls/clone/{pollId}', 'verb' => 'get'],
		['name' => 'poll#getByToken', 'url' => '/polls/get/s/{token}', 'verb' => 'GET'],

		['name' => 'share#getShares', 'url' => '/shares/get/{pollId}', 'verb' => 'GET'],
		['name' => 'share#write', 'url' => '/share/write/', 'verb' => 'POST'],
		['name' => 'share#createPersonalShare', 'url' => '/share/create/s/', 'verb' => 'POST'],
		['name' => 'share#remove', 'url' => '/share/remove/', 'verb' => 'POST'],
		['name' => 'share#get', 'url' => '/share/get/{token}', 'verb' => 'GET'],

		['name' => 'acl#getByToken', 'url' => '/acl/get/s/{token}', 'verb' => 'GET'],
		['name' => 'acl#get', 'url' => '/acl/get/{id}', 'verb' => 'GET'],

		['name' => 'system#get_site_users_and_groups', 'url' => '/siteusers/get/', 'verb' => 'POST'],
		['name' => 'system#validate_public_username', 'url' => '/check/username', 'verb' => 'POST']
	]
];
