<?php

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
    <?php if(count($_['polls']) === 0) : ?>
        <div id="emptycontent" class="">
            <div class="icon-polls"></div>
            <h2><?php p($l->t('No existing polls.')); ?></h2>
        </div>
    <?php else : ?>
            <div class="table has-controls">
                <div class ="row table-header">
                    <div class="wrapper wrapper-1">
                        <div class="wrapper wrapper-1-1">
                            <div class="column name">           <?php p($l->t('Title')); ?></div>
                            <div class="column description">    <?php p($l->t('Description')); ?></div>
                        </div>
                        <div class="wrapper wrapper-1-2">
                            <div class="column principal">      <?php p($l->t('By')); ?>          </div>
                            <div class="column access">         <?php p($l->t('Access')); ?>      </div>
                        </div>
                    </div>
                    <div class="wrapper wrapper-2">
                        <div class="column created">        <?php p($l->t('Created')); ?>     </div>
                        <div class="column expiry">         <?php p($l->t('Expires')); ?>     </div>
                        <div class="column participants"> <?php p($l->t('participated')); ?></div>
                    </div>
                    <div class="wrapper wrapper-3">
                        <div class="column options">        <?php p($l->t('Options')); ?>     </div>
                    </div>
                </div>
                
                <?php foreach ($_['polls'] as $poll) : ?>
                    <?php
                        if (!userHasAccess($poll, $userId)) continue;
                        // direct url to poll
                        $pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', array('hash' => $poll->getHash()));
                        if($poll->getOwner() === $userId) {
                            $principal = $l->t('Yourself');
                        }   else {
                            $principal = $userMgr->get($poll->getOwner());
                        }

                        // 
                        if ($poll->getExpire() !== null) {
                            $expiry_style = '';
                            $expiry_date = date('d.m.Y', strtotime($poll->getExpire()));
                            if (date('U') > strtotime($poll->getExpire())) {
                                $expiry_style = 'expired';
                            }
                        } else {
                            $expiry_style = '';
                            $expiry_date = $l->t('Never');
                        }
                    ?>

                    <div class="row table-body">
                        <div class="wrapper wrapper-1">
                            <div class="wrapper wrapper-1-1">
                                <div class="column name">        <a href="<?php p($pollUrl); ?>"> <?php p($poll->getTitle()); ?></a>                            </div>
                                <div class="column description">                                  <?php p($poll->getDescription()); ?>                          </div>
                            </div>
                            <div class="wrapper wrapper-1-2">
                                <div class="column principal">                                    <?php p($principal); ?>                                       </div>
                                <div class="column access">                                       <?php p($l->t($poll->getAccess())); ?>                        </div>
                            </div>
                        </div>
                        <div class="wrapper wrapper-2">
                            <div class="column created">                                          <?php p(date('d.m.Y H:i', strtotime($poll->getCreated()))); ?></div>
                            <div class="column expiry <?php p($expiry_style); ?>">                <?php p($expiry_date); ?>                                     </div>
                            <div class="column participants">
                                    <?php
                                        $partic_class = 'partic_no';
                                        $partic_polls = $_['participations'];
                                        for($i = 0; $i < count($partic_polls); $i++){
                                            if($poll->getId() == intval($partic_polls[$i]->getPollId())){
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
                                        for($i = 0; $i < count($partic_comm); $i++){
                                            if($poll->getId() === intval($partic_comm[$i]->getPollId())){
                                                $partic_class = 'partic_yes';
                                                array_splice($partic_comm, $i, 1);
                                                break;
                                            }
                                        }
                                    ?>
                                    <div class="partic_all <?php p($partic_class); ?>">
                                    </div>
                            </div>

                        </div>
                        <div class="wrapper wrapper-3">
                            <div class="column options">
                                <?php if ($poll->getOwner() === $userId) : ?>
                                <input type="button" id="id_del_<?php p($poll->getId()); ?>" class="table_button cl_delete icon-delete action permanent"></input>
                                <a href="<?php p($urlGenerator->linkToRoute('polls.page.edit_poll', ['hash' => $poll->getHash()])); ?>"><input type="button" id="id_edit_<?php p($poll->getId()); ?>" class="table_button icon-rename action permanent"></input></a>
                                <?php endif; ?>
                                <input type="button" class="table_button cl_link icon-public action permanent" data-url="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>"></input>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
    if($poll === null) return false;
    $access = $poll->getAccess();
    $owner = $poll->getOwner();
    if (!User::isLoggedIn()) return false;
    if ($access === 'public') return true;
    if ($access === 'hidden') return true;
    if ($access === 'registered') return true;
    if ($owner === $userId) return true;
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
