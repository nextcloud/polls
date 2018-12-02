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
		['name' => 'page#goto_poll', 'url' => '/poll/{hash}', 'verb' => 'GET'],
		['name' => 'page#edit_poll', 'url' => '/edit/{hash}', 'verb' => 'GET'],
		['name' => 'page#create_poll', 'url' => '/create', 'verb' => 'GET'],
		['name' => 'page#delete_poll', 'url' => '/delete', 'verb' => 'POST'],
		['name' => 'page#update_poll', 'url' => '/update', 'verb' => 'POST'],
		['name' => 'page#insert_poll', 'url' => '/insert', 'verb' => 'POST'],
		['name' => 'page#insert_vote', 'url' => '/insert/vote', 'verb' => 'POST'],
		['name' => 'page#insert_comment', 'url' => '/insert/comment', 'verb' => 'POST'],
		['name' => 'page#search', 'url' => '/search', 'verb' => 'POST'],
		['name' => 'page#get_display_name', 'url' => '/get/displayname', 'verb' => 'POST'],
		['name' => 'api#write_poll', 'url' => '/write', 'verb' => 'POST'],
		['name' => 'api#get_poll', 'url' => '/get/poll/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_options', 'url' => '/get/options/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_votes', 'url' => '/get/votes/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_comments', 'url' => '/get/comments/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_shares', 'url' => '/get/shares/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_event', 'url' => '/get/event/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_fullPoll', 'url' => '/get/fullpoll/{pollId}', 'verb' => 'GET'],
		['name' => 'api#get_polls', 'url' => '/get/polls/', 'verb' => 'GET'],
		['name' => 'api#get_site_users_and_groups', 'url' => '/get/siteusers', 'verb' => 'POST'],
		['name' => 'api#get_system', 'url' => '/get/system', 'verb' => 'GET']
	]
];
