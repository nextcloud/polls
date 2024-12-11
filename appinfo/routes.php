<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		['name' => 'public#get_acl', 'url' => '/s/{token}/acl', 'verb' => 'GET'],
		['name' => 'public#vote_page', 'url' => '/s/{token}', 'verb' => 'GET'],
		['name' => 'public#get_share', 'url' => '/s/{token}/share', 'verb' => 'GET'],
		['name' => 'public#get_poll', 'url' => '/s/{token}/poll', 'verb' => 'GET'],
		['name' => 'public#get_comments', 'url' => '/s/{token}/comments', 'verb' => 'GET'],
		['name' => 'public#get_options', 'url' => '/s/{token}/options', 'verb' => 'GET'],
		['name' => 'public#get_votes', 'url' => '/s/{token}/votes', 'verb' => 'GET'],
		['name' => 'public#get_subscription', 'url' => '/s/{token}/subscription', 'verb' => 'GET'],
		['name' => 'public#set_email_address', 'url' => '/s/{token}/email/{emailAddress}', 'verb' => 'PUT'],
		['name' => 'public#set_display_name', 'url' => '/s/{token}/name/{displayName}', 'verb' => 'PUT'],
		['name' => 'public#delete_email_address', 'url' => '/s/{token}/email', 'verb' => 'DELETE'],

		['name' => 'public#set_vote', 'url' => '/s/{token}/vote', 'verb' => 'PUT'],
		['name' => 'public#add_option', 'url' => '/s/{token}/option', 'verb' => 'POST'],
		['name' => 'public#delete_option', 'url' => '/s/{token}/option/{optionId}', 'verb' => 'DELETE'],
		['name' => 'public#restore_option', 'url' => '/s/{token}/option/{optionId}/restore', 'verb' => 'PUT'],

		['name' => 'public#add_comment', 'url' => '/s/{token}/comment', 'verb' => 'POST'],
		['name' => 'public#restore_comment', 'url' => '/s/{token}/comment/{commentId}/restore', 'verb' => 'PUT', 'postfix' => 'auth'],
		['name' => 'public#delete_comment', 'url' => '/s/{token}/comment/{commentId}', 'verb' => 'DELETE', 'postfix' => 'public'],
		['name' => 'public#subscribe', 'url' => '/s/{token}/subscribe', 'verb' => 'PUT'],
		['name' => 'public#unsubscribe', 'url' => '/s/{token}/unsubscribe', 'verb' => 'PUT'],
		['name' => 'public#register', 'url' => '/s/{token}/register', 'verb' => 'POST'],
		['name' => 'public#resend_invitation', 'url' => '/s/{token}/resend', 'verb' => 'POST'],
		['name' => 'public#validate_public_display_name', 'url' => '/check/username', 'verb' => 'POST'],
		['name' => 'public#validate_email_address', 'url' => '/check/emailaddress/{emailAddress}', 'verb' => 'GET'],
		['name' => 'public#delete_user', 'url' => '/s/{token}/user', 'verb' => 'DELETE'],
		['name' => 'public#delete_orphaned_votes', 'url' => '/s/{token}/votes/orphaned', 'verb' => 'DELETE'],
		['name' => 'public#watch_poll', 'url' => '/s/{token}/watch', 'verb' => 'GET'],

		['name' => 'admin#index', 'url' => '/administration', 'verb' => 'GET'],

		['name' => 'admin#list', 'url' => '/administration/polls', 'verb' => 'GET'],
		['name' => 'admin#takeover', 'url' => '/administration/poll/{pollId}/takeover', 'verb' => 'PUT'],
		['name' => 'admin#run_auto_reminder_job', 'url' => 'administration/autoreminder/run', 'verb' => 'GET'],
		['name' => 'admin#run_janitor_job', 'url' => 'administration/janitor/run', 'verb' => 'GET'],
		['name' => 'admin#run_notification_job', 'url' => 'administration/notification/run', 'verb' => 'GET'],

		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/not-found', 'verb' => 'GET', 'postfix' => 'notfound'],
		['name' => 'page#index', 'url' => '/list/{category}', 'verb' => 'GET', 'postfix' => 'list', 'defaults' => ['category' => 'relevant']],
		['name' => 'page#index', 'url' => '/combo', 'verb' => 'GET', 'postfix' => 'combo'],
		['name' => 'page#vote', 'url' => '/vote/{id}', 'verb' => 'GET'],

		['name' => 'poll#list', 'url' => '/polls', 'verb' => 'GET'],
		['name' => 'poll#get', 'url' => '/poll/{pollId}/poll', 'verb' => 'GET'],
		['name' => 'poll#add', 'url' => '/poll/add', 'verb' => 'POST'],
		['name' => 'poll#update', 'url' => '/poll/{pollId}', 'verb' => 'PUT'],
		['name' => 'poll#delete', 'url' => '/poll/{pollId}', 'verb' => 'DELETE'],
		
		['name' => 'poll#close', 'url' => '/poll/{pollId}/close', 'verb' => 'PUT'],
		['name' => 'poll#reopen', 'url' => '/poll/{pollId}/reopen', 'verb' => 'PUT'],
		['name' => 'poll#toggleArchive', 'url' => '/poll/{pollId}/toggleArchive', 'verb' => 'PUT'],
		['name' => 'poll#clone', 'url' => '/poll/{pollId}/clone', 'verb' => 'POST'],
		['name' => 'poll#getParticipantsEmailAddresses', 'url' => '/poll/{pollId}/addresses', 'verb' => 'GET'],
		['name' => 'poll#sendConfirmation', 'url' => '/poll/{pollId}/confirmation', 'verb' => 'POST'],
		['name' => 'poll#transfer_polls', 'url' => '/polls/transfer/{sourceUser}/{destinationUser}', 'verb' => 'PUT'],

		['name' => 'option#list', 'url' => '/poll/{pollId}/options', 'verb' => 'GET'],
		['name' => 'option#add', 'url' => '/option', 'verb' => 'POST'],
		['name' => 'option#addBulk', 'url' => '/option/bulk', 'verb' => 'POST'],
		['name' => 'option#update', 'url' => '/option/{optionId}', 'verb' => 'PUT'],
		['name' => 'option#delete', 'url' => '/option/{optionId}', 'verb' => 'DELETE'],
		['name' => 'option#restore', 'url' => '/option/{optionId}/restore', 'verb' => 'PUT'],

		['name' => 'option#shift', 'url' => '/poll/{pollId}/shift', 'verb' => 'POST'],
		['name' => 'option#reorder', 'url' => '/poll/{pollId}/options/reorder', 'verb' => 'POST'],
		['name' => 'option#confirm', 'url' => '/option/{optionId}/confirm', 'verb' => 'PUT'],
		['name' => 'option#sequence', 'url' => '/option/{optionId}/sequence', 'verb' => 'POST'],
		['name' => 'option#findCalendarEvents', 'url' => '/option/{optionId}/events', 'verb' => 'GET'],

		['name' => 'vote#list', 'url' => '/poll/{pollId}/votes', 'verb' => 'GET'],
		['name' => 'vote#set', 'url' => '/vote', 'verb' => 'PUT'],
		['name' => 'vote#delete', 'url' => '/poll/{pollId}/user/{userId}', 'verb' => 'DELETE', 'postfix' => 'other'],
		['name' => 'vote#delete', 'url' => '/poll/{pollId}/user', 'verb' => 'DELETE', 'postfix' => 'self'],
		['name' => 'vote#delete_orphaned', 'url' => '/poll/{pollId}/votes/orphaned', 'verb' => 'DELETE'],

		['name' => 'share#list', 'url' => '/poll/{pollId}/shares', 'verb' => 'GET'],
		['name' => 'share#add', 'url' => '/poll/{pollId}/share', 'verb' => 'POST'],
		['name' => 'share#send_all_invitations', 'url' => '/poll/{pollId}/inviteAll', 'verb' => 'PUT'],
		['name' => 'share#delete', 'url' => '/share/{token}', 'verb' => 'DELETE'],
		['name' => 'share#restore', 'url' => '/share/{token}/restore', 'verb' => 'PUT'],
		['name' => 'share#personal', 'url' => '/share/personal', 'verb' => 'POST'],
		['name' => 'share#sendInvitation', 'url' => '/share/{token}/invite', 'verb' => 'POST'],
		['name' => 'share#resolveGroup', 'url' => '/share/{token}/resolve', 'verb' => 'GET'],
		['name' => 'share#user_to_admin', 'url' => '/share/{token}/admin', 'verb' => 'PUT'],
		['name' => 'share#admin_to_user', 'url' => '/share/{token}/user', 'verb' => 'PUT'],
		['name' => 'share#set_label', 'url' => '/share/{token}/setlabel', 'verb' => 'PUT'],
		['name' => 'share#set_public_poll_email', 'url' => '/share/{token}/publicpollemail/{value}', 'verb' => 'PUT'],
		['name' => 'share#lock', 'url' => '/share/{token}/lock', 'verb' => 'PUT'],
		['name' => 'share#unlock', 'url' => '/share/{token}/unlock', 'verb' => 'PUT'],

		['name' => 'settings#getAppSettings', 'url' => '/settings/app', 'verb' => 'GET'],
		['name' => 'settings#writeAppSettings', 'url' => '/settings/app', 'verb' => 'POST'],

		['name' => 'subscription#get', 'url' => '/poll/{pollId}/subscription', 'verb' => 'GET'],
		['name' => 'subscription#set', 'url' => '/poll/{pollId}/subscription', 'verb' => 'PUT'],
		['name' => 'subscription#subscribe', 'url' => '/poll/{pollId}/subscribe', 'verb' => 'PUT'],
		['name' => 'subscription#unsubscribe', 'url' => '/poll/{pollId}/unsubscribe', 'verb' => 'PUT'],

		['name' => 'comment#list', 'url' => '/poll/{pollId}/comments', 'verb' => 'GET'],
		['name' => 'comment#add', 'url' => '/poll/{pollId}/comment', 'verb' => 'POST'],
		['name' => 'comment#restore', 'url' => '/comment/{commentId}/restore', 'verb' => 'PUT', 'postfix' => 'auth'],
		['name' => 'comment#delete', 'url' => '/comment/{commentId}', 'verb' => 'DELETE', 'postfix' => 'auth'],

		['name' => 'system#user_search', 'url' => '/search/users/{query}', 'verb' => 'GET'],
		['name' => 'system#group_search', 'url' => '/groups/{query}', 'verb' => 'GET', 'postfix' => 'query'],
		['name' => 'system#group_search', 'url' => '/groups', 'verb' => 'GET', 'postfix' => 'all'],

		['name' => 'watch#watch_poll', 'url' => '/poll/{pollId}/watch', 'verb' => 'GET'],

		['name' => 'user#get_acl', 'url' => '/acl', 'verb' => 'GET'],
		['name' => 'user#write_preferences', 'url' => '/preferences', 'verb' => 'POST'],
		['name' => 'user#get_preferences', 'url' => '/preferences', 'verb' => 'GET'],
		['name' => 'user#get_calendars', 'url' => '/calendars', 'verb' => 'GET'],
		

		// REST-API calls
		['name' => 'user_api#get_acl', 'url' => '/api/v1.0/acl', 'verb' => 'GET'],
		['name' => 'base_api#preflighted_cors', 'url' => '/api/v1.0/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
		['name' => 'poll_api#list', 'url' => '/api/v1.0/polls', 'verb' => 'GET'],
		['name' => 'poll_api#transfer_polls', 'url' => '/api/v1.0/polls/transfer/{sourceUser}/{destinationUser}', 'verb' => 'PUT'],
		['name' => 'poll_api#transfer_poll', 'url' => '/api/v1.0/poll/{pollId}/transfer/{destinationUser}', 'verb' => 'PUT'],
		['name' => 'poll_api#add', 'url' => '/api/v1.0/poll', 'verb' => 'POST'],
		['name' => 'poll_api#get', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'GET'],
		['name' => 'poll_api#update', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'PUT'],
		['name' => 'poll_api#delete', 'url' => '/api/v1.0/poll/{pollId}', 'verb' => 'DELETE'],
		['name' => 'poll_api#toggleArchive', 'url' => '/api/v1.0/poll/{pollId}/toggleArchive', 'verb' => 'PUT'],
		['name' => 'poll_api#clone', 'url' => '/api/v1.0/poll/{pollId}/clone', 'verb' => 'POST'],
		['name' => 'poll_api#get_participants_email_addresses', 'url' => '/api/v1.0/poll/{pollId}/addresses', 'verb' => 'GET'],
		['name' => 'poll_api#enum', 'url' => '/api/v1.0/enum/poll', 'verb' => 'GET'],
		['name' => 'poll_api#close', 'url' => '/api/v1.0/poll/{pollId}/close', 'verb' => 'PUT'],
		['name' => 'poll_api#reopen', 'url' => '/api/v1.0/poll/{pollId}/reopen', 'verb' => 'PUT'],

		['name' => 'option_api#list', 'url' => '/api/v1.0/poll/{pollId}/options', 'verb' => 'GET'],
		['name' => 'option_api#add', 'url' => '/api/v1.0/poll/{pollId}/option', 'verb' => 'POST'],
		['name' => 'option_api#update', 'url' => '/api/v1.0/option/{optionId}', 'verb' => 'PUT'],
		['name' => 'option_api#delete', 'url' => '/api/v1.0/option/{optionId}', 'verb' => 'DELETE'],
		['name' => 'option_api#restore', 'url' => '/api/v1.0/option/{optionId}/restore', 'verb' => 'PUT'],
		['name' => 'option_api#setOrder', 'url' => '/api/v1.0/option/{optionId}/setorder/{order}', 'verb' => 'PUT'],
		['name' => 'option_api#confirm', 'url' => '/api/v1.0/option/{optionId}/confirm', 'verb' => 'PUT'],

		['name' => 'vote_api#list', 'url' => '/api/v1.0/poll/{pollId}/votes', 'verb' => 'GET'],
		['name' => 'vote_api#set', 'url' => '/api/v1.0/option/{optionId}/vote/{answer}', 'verb' => 'PUT'],
		['name' => 'vote_api#delete', 'url' => '/api/v1.0/poll/{pollId}/user/{userId}', 'verb' => 'DELETE', 'postfix' => 'other'],
		['name' => 'vote_api#delete', 'url' => '/api/v1.0/poll/{pollId}/user', 'verb' => 'DELETE', 'postfix' => 'self'],
		['name' => 'vote_api#delete_orphaned', 'url' => '/api/v1.0/poll/{pollId}/votes/orphaned', 'verb' => 'DELETE'],

		['name' => 'share_api#list', 'url' => '/api/v1.0/poll/{pollId}/shares', 'verb' => 'GET'],
		['name' => 'share_api#get', 'url' => '/api/v1.0/share/{token}', 'verb' => 'GET'],
		['name' => 'share_api#add', 'url' => '/api/v1.0/poll/{pollId}/share/{type}', 'verb' => 'POST'],
		['name' => 'share_api#delete', 'url' => '/api/v1.0/share/{token}', 'verb' => 'DELETE'],
		['name' => 'share_api#restore', 'url' => '/api/v1.0/share/{token}/restore', 'verb' => 'PUT'],
		['name' => 'share_api#sendInvitation', 'url' => '/api/v1.0/share/send/{token}', 'verb' => 'PUT'],
		['name' => 'share_api#lock', 'url' => '/api/v1.0/share/lock/{token}', 'verb' => 'PUT'],
		['name' => 'share_api#unlock', 'url' => '/api/v1.0/share/unlock/{token}', 'verb' => 'PUT'],

		['name' => 'subscription_api#get', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'GET'],
		['name' => 'subscription_api#subscribe', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'PUT'],
		['name' => 'subscription_api#unsubscribe', 'url' => '/api/v1.0/poll/{pollId}/subscription', 'verb' => 'DELETE'],

		['name' => 'comment_api#list', 'url' => '/api/v1.0/poll/{pollId}/comments', 'verb' => 'GET'],
		['name' => 'comment_api#add', 'url' => '/api/v1.0/poll/{pollId}/comment', 'verb' => 'POST'],
		['name' => 'comment_api#delete', 'url' => '/api/v1.0/comment/{commentId}', 'verb' => 'DELETE'],
		['name' => 'comment_api#restore', 'url' => '/api/v1.0/comment/{commentId}/restore', 'verb' => 'PUT'],

	]
];
