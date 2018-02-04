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

namespace OCA\Polls\AppInfo;

use OCA\Polls\Controller\PageController;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\OptionsMapper;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\NotificationMapper;
use OCA\Polls\Db\VotesMapper;
use OCP\AppFramework\App;
use OCP\IContainer;

class Application extends App {

	/**
	 * Application constructor.
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = array()) {
		parent::__construct('polls', $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

		/**
		 * Controllers
		 */
		$container->registerService('PageController', function (IContainer $c) {
			return new PageController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserManager'),
				$c->query('GroupManager'),
				$c->query('AvatarManager'),
				$c->query('Logger'),
				$c->query('L10N'),
				$c->query('ServerContainer')->getURLGenerator(),
				$c->query('UserId'),
				$c->query('CommentMapper'),
				$c->query('OptionsMapper'),
				$c->query('EventMapper'),
				$c->query('NotificationMapper'),
				$c->query('VotesMapper')
			);
		});

		$container->registerService('UserManager', function (IContainer $c) {
			return $c->query('ServerContainer')->getUserManager();
		});

		$container->registerService('GroupManager', function (IContainer $c) {
			return $c->query('ServerContainer')->getGroupManager();
		});

		$container->registerService('AvatarManager', function (IContainer $c) {
			return $c->query('ServerContainer')->getAvatarManager();
		});

		$container->registerService('Logger', function (IContainer $c) {
			return $c->query('ServerContainer')->getLogger();
		});

		$container->registerService('L10N', function (IContainer $c) {
			return $c->query('ServerContainer')->getL10N($c->query('AppName'));
		});

		$container->registerService('CommentMapper', function (IContainer $c) use ($server) {
			return new CommentMapper(
				$server->getDatabaseConnection()
			);
		});

		$container->registerService('OptionsMapper', function (IContainer $c) use ($server) {
			return new OptionsMapper(
				$server->getDatabaseConnection()
			);
		});

		$container->registerService('EventMapper', function (IContainer $c) use ($server) {
			return new EventMapper(
				$server->getDatabaseConnection()
			);
		});

		$container->registerService('NotificationMapper', function (IContainer $c) use ($server) {
			return new NotificationMapper(
				$server->getDatabaseConnection()
			);
		});

		$container->registerService('VotesMapper', function (IContainer $c) use ($server) {
			return new VotesMapper(
				$server->getDatabaseConnection()
			);
		});

	}
	/**
	 * Register navigation entry for main navigation.
	 */
	public function registerNavigationEntry() {
		$container = $this->getContainer();
		$container->query('OCP\INavigationManager')->add(function () use ($container) {
			$urlGenerator = $container->query('OCP\IURLGenerator');
			$l10n = $container->query('OCP\IL10N');
			return [
				'id' => 'polls',
				'order' => 77,
				'href' => $urlGenerator->linkToRoute('polls.page.index'),
				'icon' => $urlGenerator->imagePath('polls', 'app.svg'),
				'name' => $l10n->t('Polls')
			];
		});
	}
}
