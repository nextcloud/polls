<?php
/**
 * ownCloud - polls
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @copyright Vinzenz Rosenkranz 2016
 */

namespace OCA\Polls\AppInfo;

$l = \OC::$server->getL10N('polls');

\OC::$server->getNavigationManager()->add(array(
    // the string under which your app will be referenced in owncloud
    'id' => 'polls',

    // sorting weight for the navigation. The higher the number, the higher
    // will it be listed in the navigation
    'order' => 77,

    // the route that will be shown on startup
    'href' => \OC::$server->getURLGenerator()->linkToRoute('polls.page.index'),

    // the icon that will be shown in the navigation
    // this file needs to exist in img/
    'icon' => \OC::$server->getURLGenerator()->imagePath('polls', 'app-logo-polls.svg'),

    // the title of your application. This will be used in the
    // navigation or on the settings page of your app
    'name' => $l->t('Polls')
));
