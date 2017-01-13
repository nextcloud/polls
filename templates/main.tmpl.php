<?php
    \OCP\Util::addStyle('polls', 'main');
    \OCP\Util::addStyle('polls', 'list');
    \OCP\Util::addStyle('polls', 'sidebar');
    \OCP\Util::addScript('polls', 'start');
    //\OCP\Util::addScript('polls', 'bootstrap.min');
    //\OCP\Util::addScript('polls', 'menu');
    use OCP\User;
    $userId = $_['userId'];
    $userMgr = $_['userMgr'];
    $urlGenerator = $_['urlGenerator'];

    //Modal: http://vodkabears.github.io/remodal/
?>
<div id="app">
  <?php print_unescaped($this->inc('navigation.tmpl')); ?>

  <div id="app-content">
    <div id="app-content-wrapper">
<div id="controls">
      <div class="breadcrumb">
        <div class="crumb svg last">
          <a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>"><img class="svg" src="/core/img/places/home.svg" alt="Home"></a>
        </div>

      </div>

      <div class="actions creatable">
        <a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>" class="button new" data-original-title="" title=""><span class="icon icon-add"></span></a>
      </div></div>
    <?php if(count($_['polls']) === 0) : ?>
    <?php p($l->t('No existing polls.')); ?>
    <?php else : ?>
      <div class="polltable">
    <table id="polltable" class="has-controls">
      <thead>
        <tr>
          <th>
            <a class="sort columntitle" data-sort="name"><span><?php p($l->t('Title')); ?></span><span class="sort-indicator icon-triangle-n"></span></a>
          </th>
          <th><?php p($l->t('Description')); ?></th>
          <th><?php p($l->t('Created')); ?></th>
          <th><?php p($l->t('By')); ?></th>
          <th><?php p($l->t('Expires')); ?></th>
          <th><?php p($l->t('participated')); ?></th>
            <th id="id_th_descr"><?php p($l->t('Access')); ?></th>
            <th><?php p($l->t('Options')); ?></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($_['polls'] as $poll) : ?>
            <?php

                if (!userHasAccess($poll, $userId)) continue;
                // direct url to poll
                $pollUrl = $urlGenerator->linkToRoute('polls.page.goto_poll', array('hash' => $poll->getHash()));
            ?>
            <tr>
                <td class="pollname">
                  <div class="thumbnail" style="background-image:url(/apps/polls/img/poll-icon.svg); background-size: 32px;"></div>
                    <a class="name" href="<?php p($pollUrl); ?>"><?php p($poll->getTitle()); ?></a>
                </td>
                <?php
                    $desc_str = $poll->getDescription();
                    if($desc_str === null) $desc_str = $l->t('No description provided.');
                    if (strlen($desc_str) > 100){
                        $desc_str = substr($desc_str, 0, 80) . '...';
                    }
                ?>
                <td ><?php p($desc_str); ?></td>
                <td><?php p(date('d.m.Y H:i', strtotime($poll->getCreated()))); ?></td>
                <td>
                    <?php
                        if($poll->getOwner() === $userId) p($l->t('Yourself'));
                        else p($userMgr->get($poll->getOwner()));
                    ?>
                </td>
                    <?php
                        if ($poll->getExpire() !== null) {
                            $style = '';
                            if (date('U') > strtotime($poll->getExpire())) {
                                $style = ' style="color: red"';
                            }
                            print_unescaped('<td' . $style . '>' . date('d.m.Y', strtotime($poll->getExpire())) . '</td>');
                        }
                        else {
                            print_unescaped('<td>' . $l->t('Never') . '</td>');
                        }
                    ?>
                    <td>
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
                                    </td>
                                    <td>
                                        <?php p($l->t($poll->getAccess())); ?>
                                    </td>
                                    <td>
                                        <?php if ($poll->getOwner() === $userId) : ?>
                                        <input type="button" id="id_del_<?php p($poll->getId()); ?>" class="table_button cl_delete icon-delete"></input>
                                        <a href="<?php p($urlGenerator->linkToRoute('polls.page.edit_poll', ['hash' => $poll->getHash()])); ?>"><input type="button" id="id_edit_<?php p($poll->getId()); ?>" class="table_button cl_edit icon-rename"></input></a>
                                        <?php endif; ?>
                                        <input type="button" class="table_button cl_link icon-public" data-url="<?php p(OCP\Util::linkToAbsolute('', $pollUrl)); ?>" title="<?php p($l->t('Click to get link')); ?>"></input>
                                    </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <form id="form_delete_poll" name="form_delete_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.delete_poll')); ?>" method="POST">
    </form>
    <?php endif; ?>
    <a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>"><input type="button" id="submit_new_poll" class="icon-add" /></a>
</div>
</div>
</div>
</div>
<?php
// ---- helper functions ----
    function userHasAccess($poll, $userId) {
        if($poll === null) return false;
        $access = $poll->getAccess();
        $owner = $poll->getOwner();
        if (!User::isLoggedIn()) return false;
        if ($access === 'public') return true;
        if ($access === 'hidden') return true;
        if ($access === 'registered') return true;
        if ($owner === $userId) return true;
        $user_groups = OC_Group::getUserGroups($userId);

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


function print_r2($val){
        echo '<pre>';
        print_r($val);
        echo  '</pre>';
}

?>
