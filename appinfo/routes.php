<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
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

$app = new \OCA\Polls\AppInfo\Application();
$app->registerRoutes($this, array(
	'routes' => array(
		array('name' => 'page#index', 'url' => '/', 'verb' => 'GET'),
		array('name' => 'page#goto_poll', 'url' => '/poll/{hash}', 'verb' => 'GET'),
		array('name' => 'page#edit_poll', 'url' => '/edit/{hash}', 'verb' => 'GET'),
		array('name' => 'page#create_poll', 'url' => '/create', 'verb' => 'GET'),
		array('name' => 'page#delete_poll', 'url' => '/delete', 'verb' => 'POST'),
		array('name' => 'page#update_poll', 'url' => '/update', 'verb' => 'POST'),
		array('name' => 'page#insert_poll', 'url' => '/insert', 'verb' => 'POST'),
		array('name' => 'page#insert_vote', 'url' => '/insert/vote', 'verb' => 'POST'),
		array('name' => 'page#insert_comment', 'url' => '/insert/comment', 'verb' => 'POST'),
		array('name' => 'page#search', 'url' => '/search', 'verb' => 'POST'),
		array('name' => 'page#get_display_name', 'url' => '/get/displayname', 'verb' => 'POST'),
	)
));
