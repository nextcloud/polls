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

use OC\AppFramework\Utility\SimpleContainer;
use \OCP\AppFramework\App;
use \OCA\Polls\Db\CommentMapper;
use \OCA\Polls\Db\DateMapper;
use \OCA\Polls\Db\EventMapper;
use \OCA\Polls\Db\NotificationMapper;
use \OCA\Polls\Db\ParticipationMapper;
use \OCA\Polls\Db\ParticipationTextMapper;
use \OCA\Polls\Db\TextMapper;
use \OCA\Polls\Controller\PageController;

class Application extends App
{

    /**
     * Application constructor.
     * @param array $urlParams
     */
    public function __construct(array $urlParams = array())
    {
        parent::__construct('polls', $urlParams);

        $container = $this->getContainer();
        $server = $container->getServer();

        /**
         * Controllers
         */
        $container->registerService('PageController', function ($c) use ($server) {
            /** @var SimpleContainer $c */
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
                $c->query('DateMapper'),
                $c->query('EventMapper'),
                $c->query('NotificationMapper'),
                $c->query('ParticipationMapper'),
                $c->query('ParticipationTextMapper'),
                $c->query('TextMapper')
            );
        });

        $container->registerService('UserManager', function ($c) {
            /** @var SimpleContainer $c */
            return $c->query('ServerContainer')->getUserManager();
        });

        $container->registerService('GroupManager', function ($c) {
            /** @var SimpleContainer $c */
            return $c->query('ServerContainer')->getGroupManager();
        });

        $container->registerService('AvatarManager', function ($c) {
            /** @var SimpleContainer $c */
            return $c->query('ServerContainer')->getAvatarManager();
        });

        $container->registerService('Logger', function ($c) {
            /** @var SimpleContainer $c */
            return $c->query('ServerContainer')->getLogger();
        });

        $container->registerService('L10N', function ($c) {
            return $c->query('ServerContainer')->getL10N($c->query('AppName'));
        });

        $container->registerService('CommentMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new CommentMapper(
                $server->getDatabaseConnection()
            );
        });

        $container->registerService('DateMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new DateMapper(
                $server->getDatabaseConnection()
            );
        });

        $container->registerService('EventMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new EventMapper(
                $server->getDatabaseConnection()
            );
        });

        $container->registerService('NotificationMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new NotificationMapper(
                $server->getDatabaseConnection()
            );
        });

        $container->registerService('ParticipationMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new ParticipationMapper(
                $server->getDatabaseConnection()
            );
        });

        $container->registerService('ParticipationTextMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new ParticipationTextMapper(
                $server->getDatabaseConnection()
            );
        });

        $container->registerService('TextMapper', function ($c) use ($server) {
            /** @var SimpleContainer $c */
            return new TextMapper(
                $server->getDatabaseConnection()
            );
        });
    }

    /**
     * Register navigation entry for main navigation.
     */
    public function registerNavigationEntry()
    {
        $container = $this->getContainer();
        $container->query('OCP\INavigationManager')->add(function () use ($container) {
            $urlGenerator = $container->query('OCP\IURLGenerator');
            $l10n = $container->query('OCP\IL10N');
            return [
                'id' => 'polls',
                'order' => 77,
                'href' => $urlGenerator->linkToRoute('polls.page.index'),
                'icon' => $urlGenerator->imagePath('polls', 'app-logo-polls.svg'),
                'name' => $l10n->t('Polls')
            ];
        });
    }
}
