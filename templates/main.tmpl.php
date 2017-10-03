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
                            <span class="symbol icon-add"></span><span class="hidden-visually">Neu</span>
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
                
                    <div class="wrapper group-master">
                        <div class="wrapper group-1">
                            <div class="wrapper group-1-1">
                                <div class="column name">        <?php p($l->t('Title')); ?></div>
                                <div class="column description"> <?php p($l->t('Description')); ?></div>
                            </div>
                            <div class="wrapper group-1-2">
                                <div class="column principal">   <?php p($l->t('By')); ?></div>
                                <div class="column access">      <?php p($l->t('Access')); ?></div>
                            </div>
                        </div>
                        <div class="wrapper group-2">
                            <div class="column created">         <?php p($l->t('Created')); ?></div>
                            <div class="column expiry">          <?php p($l->t('Expires')); ?></div>
                            <div class="column participants">    <?php p($l->t('participated')); ?></div>
                        </div>
                     </div>
                    <div class="wrapper group-3">
                        <div class="column options">     <?php p($l->t('Options')); ?></div>
                    </div>
                </div>
                
                <?php foreach ($_['polls'] as $poll) : ?>
                    <?php
                        if (!userHasAccess($poll, $userId)) continue;
                        // direct url to poll
                        $pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', array('hash' => $poll->getHash()));
                        $principal = $poll->getOwner();
                        if($principal === $userId) {
                            $principal = $l->t('Yourself');
                        }

                        $expiry_style = '';
                        if ($poll->getExpire() !== null) {
                            // $expiry_date = date('d.m.Y', strtotime($poll->getExpire()));
                            $expiry_date = OCP\relative_modified_date(strtotime($poll->getExpire())); // does not work, because relative_modified_date seems not to recognise future time diffs
                            $expiry_style = ' progress';
                            if (date('U') > strtotime($poll->getExpire())) {
                                $expiry_date = OCP\relative_modified_date(strtotime($poll->getExpire()));
                                $expiry_style = ' expired';
                            }
                        } else {
                            $expiry_style = ' endless';
                            $expiry_date = $l->t('Never');
                        }
                    ?>

                    <div class="row table-body">
                        <div class="wrapper group-master">
                            <div class="wrapper group-1">
                                <div class="thumbnail <?php p($expiry_style); ?>"></div>  <!-- Image to display status or type of poll */ -->
                                <a href="<?php p($pollUrl); ?>" class="wrapper group-1-1">
                                    <div class="column name">                          <?php p($poll->getTitle()); ?></div>
                                    <div class="column description">                   <?php p($poll->getDescription()); ?></div>
                                </a> 
                                <div class="wrapper group-1-2">
                                    <div class="column principal">  
                                        <div class="avatardiv" title="<?php p($poll->getOwner()); ?>" style="height: 32px; width: 32px;"></div>
                                        <div class="name-cell"><?php p($principal); ?></div>
                                    </div>
                                    <div class="column access"><?php p($l->t($poll->getAccess())); ?></div>
                                </div>
                            </div>
                            <div class="wrapper group-2">
                                <div class="column created" data-timestamp="<?php p(strtotime($poll->getCreated())); ?>" data-value="<?php p($poll->getCreated()); ?>"><?php p(OCP\relative_modified_date(strtotime($poll->getCreated()))); ?></div>
                                <div class="column expiry<?php p($expiry_style); ?>" data-value="<?php p($poll->getExpire()); ?>"> <?php p($expiry_date); ?></div>
                                <div class="column participants">
                                        <?php
                                            $partic_class = 'partic_no';
                                            $partic_polls = $_['participations'];
                                            $partic_title = 'You did not vote';
                                            for($i = 0; $i < count($partic_polls); $i++){
                                                if($poll->getId() == intval($partic_polls[$i]->getPollId())){
                                                    $partic_class = 'partic_yes';
                                                    $partic_title = 'You voted';
                                                    array_splice($partic_polls, $i, 1);
                                                    break;
                                                }
                                            }
                                        ?>
                                        <div class="symbol partic_voted icon-<?php p($partic_class); ?>" title="<?php p($partic_title); ?>">
                                        </div>
                                        <?php
                                            $partic_class = 'partic_no';
                                            $partic_comment = $_['comments'];
                                            $partic_title = 'You did not comment';
                                            for($i = 0; $i < count($partic_comment); $i++){
                                                if($poll->getId() === intval($partic_comment[$i]->getPollId())){
                                                    $partic_class = 'partic_yes';
                                                    $partic_title = 'You commented';
                                                    array_splice($partic_comment, $i, 1);
                                                    break;
                                                }
                                            }
                                        ?>
                                        <div class="symbol partic_commented icon-<?php p($partic_class); ?>" title="<?php p($partic_title); ?>">
                                        </div>
                                </div>

                            </div>
                        </div>
                        <div class="wrapper group-3">
                            <div class="column options">
                                <div class="symbol cl_link icon-clippy action permanent" data-url="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>"></div>
                                <?php if ($poll->getOwner() === $userId) : ?>
                                    <div class="symbol cl_delete icon-delete action permanent" id="id_del_<?php p($poll->getId()); ?>"></div>
                                    <a href="<?php p($urlGenerator->linkToRoute('polls.page.edit_poll', ['hash' => $poll->getHash()])); ?> " class="symbol icon-rename action permanent" id="id_edit_<?php p($poll->getId()); ?>"></a>
                                <?php endif; ?>
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
