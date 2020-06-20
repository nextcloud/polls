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

		['name' => 'comment#getByToken', 'url' => '/comments/s/{token}', 'verb' => 'GET'],
		['name' => 'comment#get', 'url' => '/comments/{pollId}', 'verb' => 'GET'],
		['name' => 'comment#add', 'url' => '/comment/add', 'verb' => 'POST'],
		['name' => 'comment#delete', 'url' => '/comment/delete', 'verb' => 'POST'],

		['name' => 'vote#getByToken', 'url' => '/votes/get/s/{token}', 'verb' => 'GET'],
		['name' => 'vote#setByToken', 'url' => '/vote/set/s/', 'verb' => 'POST'],
		['name' => 'vote#get', 'url' => '/votes/get/{pollId}', 'verb' => 'GET'],
		['name' => 'vote#set', 'url' => '/vote/set/', 'verb' => 'POST'],
		['name' => 'vote#write', 'url' => '/vote/write/', 'verb' => 'POST'],
		['name' => 'vote#delete', 'url' => '/votes/delete/', 'verb' => 'POST'],

		['name' => 'option#get', 'url' => '/options/get/{pollId}', 'verb' => 'GET'],
		['name' => 'option#add', 'url' => '/option/add/', 'verb' => 'POST'],
		['name' => 'option#update', 'url' => '/option/update/', 'verb' => 'POST'],
		['name' => 'option#reorder', 'url' => '/option/reorder/', 'verb' => 'POST'],
		['name' => 'option#remove', 'url' => '/option/remove/', 'verb' => 'POST'],
		['name' => 'option#getByToken', 'url' => '/options/get/s/{token}', 'verb' => 'GET'],

		['name' => 'poll#get', 'url' => '/polls/get/{pollId}', 'verb' => 'GET', 'postfix' => 'auth'],
		['name' => 'poll#get', 'url' => '/polls/get/s/{token}', 'verb' => 'GET', 'postfix' => 'public'],
		['name' => 'poll#add', 'url' => '/polls/add', 'verb' => 'POST'],
		['name' => 'poll#update', 'url' => '/polls/update/{pollId}', 'verb' => 'PUT'],

		['name' => 'poll#list', 'url' => '/polls/list/', 'verb' => 'GET'],
		['name' => 'poll#delete', 'url' => '/polls/delete/{pollId}', 'verb' => 'GET'],
		['name' => 'poll#deletePermanently', 'url' => '/polls/delete/permanent/{pollId}', 'verb' => 'GET'],
		['name' => 'poll#clone', 'url' => '/polls/clone/{pollId}', 'verb' => 'GET'],

		['name' => 'share#getShares', 'url' => '/shares/get/{pollId}', 'verb' => 'GET'],
		['name' => 'share#write', 'url' => '/share/write', 'verb' => 'POST'],
		['name' => 'share#createPersonalShare', 'url' => '/share/create/s/', 'verb' => 'POST'],
		['name' => 'share#remove', 'url' => '/share/remove', 'verb' => 'POST'],
		['name' => 'share#get', 'url' => '/share/get/{token}', 'verb' => 'GET'],

		['name' => 'acl#getByToken', 'url' => '/acl/get/s/{token}', 'verb' => 'GET'],
		['name' => 'acl#get', 'url' => '/acl/get/{id}', 'verb' => 'GET'],

		['name' => 'system#get_site_users_and_groups', 'url' => '/siteusers/get/', 'verb' => 'POST'],
		['name' => 'system#validate_public_username', 'url' => '/check/username', 'verb' => 'POST'],

		// REST-API calls
		['name' => 'poll_api#enum', 'url' => '/api/v1.0/enum', 'verb' => 'GET'],
		['name' => 'poll_api#list', 'url' => '/api/v1.0/polls', 'verb' => 'GET'],
		['name' => 'poll_api#add', 'url' => '/api/v1.0/poll', 'verb' => 'POST'],
		['name' => 'poll_api#get', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'GET'],
		['name' => 'poll_api#delete', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'DELETE'],
		['name' => 'poll_api#update', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'PUT'],

		['name' => 'vote_api#list', 'url' => '/api/v1.0/poll/{pollId}/votes', 'verb' => 'GET'],
		['name' => 'comment_api#list', 'url' => '/api/v1.0/poll/{pollId}/comments', 'verb' => 'GET'],
		['name' => 'option_api#list', 'url' => '/api/v1.0/poll/{pollId}/options', 'verb' => 'GET'],
		['name' => 'share_api#list', 'url' => '/api/v1.0/poll/{pollId}/shares', 'verb' => 'GET'],

		['name' => 'comment_api#preflighted_cors', 'url' => '/api/v1.0/comments', 'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')],
		['name' => 'comment_api#add', 'url' => '/api/v1.0/comment', 'verb' => 'POST'],
		['name' => 'comment_api#delete', 'url' => '/api/v1.0/comment/{commentId}', 'verb' => 'DELETE'],

		['name' => 'option_api#preflighted_cors', 'url' => '/api/v1.0/option', 'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')],
		['name' => 'option_api#add', 'url' => '/api/v1.0/option', 'verb' => 'POST'],
		['name' => 'option_api#update', 'url' => '/api/v1.0/option', 'verb' => 'PUT'],
		['name' => 'option_api#delete', 'url' => '/api/v1.0/option/{optionId}', 'verb' => 'DELETE'],

		['name' => 'share_api#get', 'url' => '/api/v1.0/share/{token}', 'verb' => 'GET'],
		['name' => 'share_api#add', 'url' => '/api/v1.0/share', 'verb' => 'POST'],
		['name' => 'share_api#delete', 'url' => '/api/v1.0/share/{token}', 'verb' => 'DELETE'],

		['name' => 'subscription_api#get', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'GET'],
		['name' => 'subscription_api#subscribe', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'PUT'],
		['name' => 'subscription_api#unsubscribe', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'DELETE'],

		['name' => 'vote_api#set', 'url' => '/api/v1.0/vote', 'verb' => 'POST'],
		['name' => 'vote_api#delete', 'url' => '/api/v1.0/vote/removeuser', 'verb' => 'POST'],
	]
];
