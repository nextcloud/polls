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
use OCA\Polls\Controller\ApiController;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\NotificationMapper;
use OCA\Polls\Db\VoteMapper;
use OCP\AppFramework\App;
use OCP\IContainer;

class Application extends App {

	/**
	 * Application constructor.
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('polls', $urlParams);
	}

	/**
	 * Register navigation entry for main navigation.
	 */
	public function registerNavigationEntry() {
		$container = $this->getContainer();
		$container->query('OCP\INavigationManager')->add(function() use ($container) {
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
