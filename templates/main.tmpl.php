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

    use OCP\User;

    \OCP\Util::addStyle('polls', 'main');
    \OCP\Util::addStyle('polls', 'list');
    \OCP\Util::addScript('polls', 'start');

    $userId = $_['userId'];
    $userMgr = $_['userMgr'];
    $urlGenerator = $_['urlGenerator'];
?>
    <div id="app-content">
        <div id="app-content-wrapper">
                <div id="controls">
                    <div id="breadcrump">
                        <div class	="crumb svg last" data-dir="/">
                            <a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>">
                                <img class="svg" src="<?php print_unescaped(OCP\image_path("core", "places/home.svg")); ?>" alt="Home">
                            </a>
                        </div>
                    </div>
                    <div class="actions creatable" style="">
                        <a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>" class="button new">
                            <span class="icon icon-add"></span><span class="hidden-visually">Neu</span>
                        </a>
                        <input class="stop icon-close" style="display:none" value="" type="button">
                    </div>
                </div>
    <?php if (count($_['polls']) === 0) : ?>
        <div id="emptycontent" class="">
            <div class="icon-polls"></div>
            <h2><?php p($l->t('No existing polls.')); ?></h2>
        </div>
    <?php else : ?>
            <table class="polltable has-controls">
                <thead>
                    <tr>
                        <th id="headerName" class="columnheader name">
                            <div id="headerName-container">
                                <a class="name sort columntitle" data-sort="name"><span><?php p($l->t('Title')); ?></span><span class="sort-indicator"></span></a>
                            </div>
                        </th>
                        <th id="headerCreated" class="columnheader created">
                            <a class="name sort columntitle" data-sort="created"><span><?php p($l->t('Created')); ?></span><span class="sort-indicator"></span></a>
                        </th>
                        <th id="headerPrincipal" class="columnheader principal">
                            <a class="name sort columntitle" data-sort="principal"><span><?php p($l->t('By')); ?></span><span class="sort-indicator"></span></a>
                        </th>
                        <th id="headerExpiry" class="columnheader expiry">
                            <a class="name sort columntitle" data-sort="expiry"><span><?php p($l->t('Expires')); ?></span><span class="sort-indicator"></span></a>
                        </th>
                        <th id="headerParticipations" class="columnheader participations">
                            <a class="name sort columntitle" data-sort="voted"><span><?php p($l->t('participated')); ?></span><span class="sort-indicator"></span></a>
                        </th>
                        <th id="headerAccess" class="columnheader access">
                            <a class="name sort columntitle" data-sort="access"><span><?php p($l->t('Access')); ?></span><span class="sort-indicator"></span></a>
                        </th>
                        <th id="headerOptions" class="columnheader options">
                            <a class="name columntitle" <span><?php p($l->t('Options')); ?></span><span class="sort-indicator"></span></a>
                        </th>
                    </tr>
                </thead>
                <tbody id="polllist">
                <?php foreach ($_['polls'] as $poll) : ?>
                    <?php
                        if (!userHasAccess($poll, $userId)) continue;
                        // direct url to poll
                        $pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', array('hash' => $poll->getHash()));
                            $desc_str = $poll->getDescription();
                            if (strlen($desc_str) > 100) {
                                $desc_str = substr($desc_str, 0, 80) . '...';
                            }
                        ?>
                    <tr>
                        <td class="pollitem name">
                            <div class="thumbnail progress"></div>  <!-- Image to display status or type of poll */ -->
                            <a class="name" href="<?php p($pollUrl); ?>">
                                <div class="nametext">
                                    <div class="innernametext"><?php p($poll->getTitle()); ?></div>
                                    <div class="description"><?php p($desc_str); ?></div>
                                </div>
                            </a>
                        </td>
                        <td class="pollitem created"><?php p(date('d.m.Y H:i', strtotime($poll->getCreated()))); ?></td>
                        <td class="pollitem principal">
                            <?php
                                if ($poll->getOwner() === $userId) {
                                    p($l->t('Yourself'));
                                } else {
                                    p($userMgr->get($poll->getOwner()));
                                }
                            ?>
                        </td>
                            <?php
                                if ($poll->getExpire() !== null) {
                                    $style = '';
                                    if (date('U') > strtotime($poll->getExpire())) {
                                        $style = 'expired';
                                    }
                                    print_unescaped('<td class="pollitem expiry ' . $style . '">' . date('d.m.Y', strtotime($poll->getExpire())) . '</td>');
                                } else {
                                    print_unescaped('<td class="pollitem expiry">' . $l->t('Never') . '</td>');
                                }
                            ?>
                        <td class="pollitem participations">
                            <?php
                                $partic_class = 'partic_no';
                                $partic_polls = $_['participations'];
                                for ($i = 0; $i < count($partic_polls); $i++) {
                                    if ($poll->getId() == intval($partic_polls[$i]->getPollId())) {
                                        $partic_class = 'partic_yes';
                                        array_splice($partic_polls, $i, 1);
                                        break;
                                    }
                                }
                            ?>
                            <div class="partic_all <?php p($partic_class); ?>">
                            </div>
                            |
                            <?php
                                $partic_class = 'partic_no';
                                $partic_comm = $_['comments'];
                                for ($i = 0; $i < count($partic_comm); $i++) {
                                    if ($poll->getId() === intval($partic_comm[$i]->getPollId())) {
                                        $partic_class = 'partic_yes';
                                        array_splice($partic_comm, $i, 1);
                                        break;
                                    }
                                }
                            ?>
                            <div class="partic_all <?php p($partic_class); ?>">
                            </div>
                        </td>
                        <td class="pollitem access">
                            <?php p($l->t($poll->getAccess())); ?>
                        </td>
                        <td class="pollitem options">
                            <?php if ($poll->getOwner() === $userId) : ?>
                            <input type="button" id="id_del_<?php p($poll->getId()); ?>" class="table_button cl_delete icon-delete action permanent"></input>
                            <a href="<?php p($urlGenerator->linkToRoute('polls.page.edit_poll', ['hash' => $poll->getHash()])); ?>"><input type="button" id="id_edit_<?php p($poll->getId()); ?>" class="table_button icon-rename action permanent"></input></a>
                            <?php endif; ?>
                            <input type="button" class="table_button cl_link icon-public action permanent" data-url="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>"></input>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form id="form_delete_poll" name="form_delete_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.delete_poll')); ?>" method="POST">
            </form>
    <?php endif; ?>
            
        </div>
    </div>


<?php
// ---- helper functions ----
// from spreed.me
function getGroups($userId) {
    // $this->requireLogin();
    if (class_exists('\OC_Group', true)) {
        // Nextcloud <= 11, ownCloud
        return \OC_Group::getUserGroups($userId);
    }
    // Nextcloud >= 12
    $groups = \OC::$server->getGroupManager()->getUserGroups(\OC::$server->getUserSession()->getUser());
    return array_map(function ($group) {
        return $group->getGID();
    }, $groups);
}

function userHasAccess($poll, $userId) {
    if($poll === null) {
        return false;
    }
    $access = $poll->getAccess();
    $owner = $poll->getOwner();
    if (!User::isLoggedIn()) {
        return false;
    }
    if ($access === 'public') {
        return true;
    }
    if ($access === 'hidden') {
        return true;
    }
    if ($access === 'registered') {
        return true;
    }
    if ($owner === $userId) {
        return true;
    }
    $user_groups = getGroups($userId);

    $arr = explode(';', $access);

    foreach ($arr as $item) {
        if (strpos($item, 'group_') === 0) {
            $grp = substr($item, 6);
            foreach ($user_groups as $user_group) {
                if ($user_group === $grp) return true;
            }
        }
        else if (strpos($item, 'user_') === 0) {
            $usr = substr($item, 5);
            if ($usr === $userId) return true;
        }
    }
    return false;
}
?>
