<?php
/**
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @copyright Vinzenz Rosenkranz 2016
 */

namespace OCA\Polls\AppInfo;

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
$application = new Application();

$application->registerRoutes($this, array('routes' => array(
	array('name' => 'page#index', 'url' => '/', 'verb' => 'GET'),
	array('name' => 'page#goto_poll', 'url' => '/poll/{hash}', 'verb' => 'GET'),
	array('name' => 'page#edit_poll', 'url' => '/edit/{hash}', 'verb' => 'GET'),
	array('name' => 'page#create_poll', 'url' => '/create', 'verb' => 'GET'),
	array('name' => 'page#delete_poll', 'url' => '/delete', 'verb' => 'POST'),
	array('name' => 'page#update_poll', 'url' => '/update', 'verb' => 'POST'),
	array('name' => 'page#insert_poll', 'url' => '/insert', 'verb' => 'POST'),
	array('name' => 'page#insert_vote', 'url' => '/insert/vote', 'verb' => 'POST'),
	array('name' => 'page#insert_comment', 'url' => '/insert/comment', 'verb' => 'POST'),
)));
