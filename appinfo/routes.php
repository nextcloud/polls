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
		['name' => 'public#vote_page', 'url' => '/s/{token}', 'verb' => 'GET'],
		['name' => 'public#get_share', 'url' => '/s/{token}/share', 'verb' => 'GET'],
		['name' => 'public#get_poll', 'url' => '/s/{token}/poll', 'verb' => 'GET'],
		['name' => 'public#get_comments', 'url' => '/s/{token}/comments', 'verb' => 'GET'],
		['name' => 'public#get_options', 'url' => '/s/{token}/options', 'verb' => 'GET'],
		['name' => 'public#get_votes', 'url' => '/s/{token}/votes', 'verb' => 'GET'],
		['name' => 'public#get_subscription', 'url' => '/s/{token}/subscription', 'verb' => 'GET'],
		['name' => 'public#set_email_address', 'url' => '/s/{token}/email', 'verb' => 'PUT'],
		['name' => 'public#delete_email_address', 'url' => '/s/{token}/email', 'verb' => 'DELETE'],

		['name' => 'public#set_vote', 'url' => '/s/{token}/vote', 'verb' => 'PUT'],
		['name' => 'public#add_option', 'url' => '/s/{token}/option', 'verb' => 'POST'],
		['name' => 'public#delete_option', 'url' => '/s/{token}/option/{optionId}', 'verb' => 'DELETE'],
		['name' => 'public#add_comment', 'url' => '/s/{token}/comment', 'verb' => 'POST'],
		['name' => 'public#delete_comment', 'url' => '/s/{token}/comment/{commentId}', 'verb' => 'DELETE', 'postfix' => 'public'],
		['name' => 'public#subscribe', 'url' => '/s/{token}/subscribe', 'verb' => 'PUT'],
		['name' => 'public#unsubscribe', 'url' => '/s/{token}/unsubscribe', 'verb' => 'PUT'],
		['name' => 'public#register', 'url' => '/s/{token}/register', 'verb' => 'POST'],
		['name' => 'public#resend_invitation', 'url' => '/s/{token}/resend', 'verb' => 'GET'],
		['name' => 'public#validate_public_username', 'url' => '/check/username', 'verb' => 'POST'],
		['name' => 'public#validate_email_address', 'url' => '/check/emailaddress/{emailAddress}', 'verb' => 'GET'],
		['name' => 'public#watch_poll', 'url' => '/s/{token}/watch', 'verb' => 'GET'],

		['name' => 'admin#index', 'url' => '/administration', 'verb' => 'GET'],
		['name' => 'admin#list', 'url' => '/administration/polls', 'verb' => 'GET'],
		['name' => 'admin#takeover', 'url' => '/administration/poll/{pollId}/takeover', 'verb' => 'GET'],

		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/not-found', 'verb' => 'GET', 'postfix' => 'notfound'],
		['name' => 'page#index', 'url' => '/list/{id}', 'verb' => 'GET', 'postfix' => 'list'],
		['name' => 'page#vote', 'url' => '/vote/{id}', 'verb' => 'GET'],

		['name' => 'poll#list', 'url' => '/polls', 'verb' => 'GET'],
		['name' => 'poll#get', 'url' => '/poll/{pollId}/poll', 'verb' => 'GET'],
		['name' => 'poll#add', 'url' => '/poll/add', 'verb' => 'POST'],
		['name' => 'poll#update', 'url' => '/poll/{pollId}', 'verb' => 'PUT'],
		['name' => 'poll#delete', 'url' => '/poll/{pollId}', 'verb' => 'DELETE'],

		['name' => 'poll#switchDeleted', 'url' => '/poll/{pollId}/switchDeleted', 'verb' => 'PUT'],
		['name' => 'poll#clone', 'url' => '/poll/{pollId}/clone', 'verb' => 'GET'],
		['name' => 'poll#getParticipantsEmailAddresses', 'url' => '/poll/{pollId}/addresses', 'verb' => 'GET'],

		['name' => 'option#list', 'url' => '/poll/{pollId}/options', 'verb' => 'GET'],
		['name' => 'option#add', 'url' => '/option', 'verb' => 'POST'],
		['name' => 'option#update', 'url' => '/option/{optionId}', 'verb' => 'PUT'],
		['name' => 'option#delete', 'url' => '/option/{optionId}', 'verb' => 'DELETE'],

		['name' => 'option#shift', 'url' => '/poll/{pollId}/shift', 'verb' => 'POST'],
		['name' => 'option#reorder', 'url' => '/poll/{pollId}/options/reorder', 'verb' => 'POST'],
		['name' => 'option#confirm', 'url' => '/option/{optionId}/confirm', 'verb' => 'PUT'],
		['name' => 'option#sequence', 'url' => '/option/{optionId}/sequence', 'verb' => 'POST'],
		['name' => 'option#findCalendarEvents', 'url' => '/option/{optionId}/events', 'verb' => 'GET'],

		['name' => 'vote#list', 'url' => '/poll/{pollId}/votes', 'verb' => 'GET'],
		['name' => 'vote#set', 'url' => '/vote', 'verb' => 'PUT'],
		['name' => 'vote#delete', 'url' => '/poll/{pollId}/user/{userId}', 'verb' => 'DELETE'],

		['name' => 'share#list', 'url' => '/poll/{pollId}/shares', 'verb' => 'GET'],
		['name' => 'share#add', 'url' => '/poll/{pollId}/share', 'verb' => 'POST'],
		['name' => 'share#delete', 'url' => '/share/{token}', 'verb' => 'DELETE'],
		['name' => 'share#personal', 'url' => '/share/personal', 'verb' => 'POST'],
		['name' => 'share#sendInvitation', 'url' => '/share/{token}/invite', 'verb' => 'POST'],
		['name' => 'share#resolveGroup', 'url' => '/share/{token}/resolve', 'verb' => 'GET'],

		['name' => 'subscription#get', 'url' => '/poll/{pollId}/subscription', 'verb' => 'GET'],
		['name' => 'subscription#set', 'url' => '/poll/{pollId}/subscription', 'verb' => 'PUT'],
		['name' => 'subscription#subscribe', 'url' => '/poll/{pollId}/subscribe', 'verb' => 'PUT'],
		['name' => 'subscription#unsubscribe', 'url' => '/poll/{pollId}/unsubscribe', 'verb' => 'PUT'],

		['name' => 'comment#list', 'url' => '/poll/{pollId}/comments', 'verb' => 'GET'],
		['name' => 'comment#add', 'url' => '/poll/{pollId}/comment', 'verb' => 'POST'],
		['name' => 'comment#delete', 'url' => '/comment/{commentId}', 'verb' => 'DELETE', 'postfix' => 'auth'],

		['name' => 'system#user_search', 'url' => '/search/users/{query}', 'verb' => 'GET'],
		['name' => 'watch#watch_poll', 'url' => '/poll/{pollId}/watch', 'verb' => 'GET'],

		['name' => 'preferences#write', 'url' => '/preferences/write', 'verb' => 'POST'],
		['name' => 'preferences#get', 'url' => '/preferences/get', 'verb' => 'GET'],

		['name' => 'preferences#get_calendars', 'url' => '/calendars', 'verb' => 'GET'],

		// REST-API calls
		['name' => 'poll_api#list', 'url' => '/api/v1.0/polls', 'verb' => 'GET'],
		['name' => 'poll_api#add', 'url' => '/api/v1.0/poll', 'verb' => 'POST'],
		['name' => 'poll_api#get', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'GET'],
		['name' => 'poll_api#update', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'PUT'],
		['name' => 'poll_api#delete', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'DELETE'],
		['name' => 'poll_api#switchDeleted', 'url' => '/api/v1.0/poll/{pollId}/switchdeleted', 'verb' => 'PUT'],
		['name' => 'poll_api#clone', 'url' => '/api/v1.0/poll/{pollId}/clone', 'verb' => 'POST'],
		['name' => 'poll_api#trash', 'url' => '/api/v1.0/poll/{pollId}/trash', 'verb' => 'POST'],
		['name' => 'poll_api#get_participants_email_addresses', 'url' => '/api/v1.0/poll/{pollId}/addresses', 'verb' => 'GET'],
		['name' => 'poll_api#enum', 'url' => '/api/v1.0/enum/poll', 'verb' => 'GET'],

		['name' => 'option_api#list', 'url' => '/api/v1.0/poll/{pollId}/options', 'verb' => 'GET'],
		['name' => 'option_api#add', 'url' => '/api/v1.0/poll/{pollId}/option', 'verb' => 'POST'],
		['name' => 'option_api#update', 'url' => '/api/v1.0/option/{optionId}', 'verb' => 'PUT'],
		['name' => 'option_api#delete', 'url' => '/api/v1.0/option/{optionId}', 'verb' => 'DELETE'],
		['name' => 'option_api#setOrder', 'url' => '/api/v1.0/option/{optionId}/setorder/{order}', 'verb' => 'PUT'],
		['name' => 'option_api#confirm', 'url' => '/api/v1.0/option/{optionId}/confirm', 'verb' => 'PUT'],

		['name' => 'vote_api#list', 'url' => '/api/v1.0/poll/{pollId}/votes', 'verb' => 'GET'],
		['name' => 'vote_api#set', 'url' => '/api/v1.0/vote', 'verb' => 'POST'],

		['name' => 'share_api#list', 'url' => '/api/v1.0/poll/{pollId}/shares', 'verb' => 'GET'],
		['name' => 'share_api#get', 'url' => '/api/v1.0/share/{token}', 'verb' => 'GET'],
		['name' => 'share_api#add', 'url' => '/api/v1.0/share', 'verb' => 'POST'],
		['name' => 'share_api#delete', 'url' => '/api/v1.0/share/{token}', 'verb' => 'DELETE'],
		['name' => 'share_api#sendInvitation', 'url' => '/api/v1.0/share/send/{token}', 'verb' => 'POST'],

		['name' => 'subscription_api#get', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'GET'],
		['name' => 'subscription_api#subscribe', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'PUT'],
		['name' => 'subscription_api#unsubscribe', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'DELETE'],

		['name' => 'comment_api#list', 'url' => '/api/v1.0/poll/{pollId}/comments', 'verb' => 'GET'],
		['name' => 'comment_api#add', 'url' => '/api/v1.0/comment', 'verb' => 'POST'],
		['name' => 'comment_api#delete', 'url' => '/api/v1.0/comment/{commentId}', 'verb' => 'DELETE'],

	]
];
