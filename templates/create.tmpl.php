<?php
    \OCP\Util::addStyle('polls', 'main');
    \OCP\Util::addStyle('polls', 'create');
    \OCP\Util::addStyle('polls', 'jquery.datetimepicker');
    \OCP\Util::addScript('polls', 'create_edit');
    \OCP\Util::addScript('polls', 'jquery.datetimepicker.full.min');
    $userId = $_['userId'];
    $userMgr = $_['userMgr'];
    $urlGenerator = $_['urlGenerator'];
    $isUpdate = isset($_['poll']) && $_['poll'] !== null;
    if($isUpdate) {
        $poll = $_['poll'];
        $dates = $_['dates'];
        $chosen = '[';
        foreach($dates as $d) {
            if($poll->getType() === '0') $chosen .= strtotime($d->getDt());
            else $chosen .= '"' . $d->getText() . '"';
            $chosen .= ',';
        }
        $chosen = trim($chosen, ',');
        $chosen .= ']';
        $title = $poll->getTitle();
        $desc = $poll->getDescription();
        if($poll->getExpire() !== null) {
            $expireTs = strtotime($poll->getExpire()) - 60*60*24; //remove one day, which has been added to expire at the end of a day
            $expireStr = date('d.m.Y', $expireTs);
        }
        $access = $poll->getAccess();
        $accessTypes = $access;
        if($access !== 'registered' && $access !== 'hidden' && $access !== 'public') $access = 'select';
    }
?>

<div id="app">
    <div id="app-content">
        <div id="app-content-wrapper">
<?php if($isUpdate): ?>
<form name="finish_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.update_poll')); ?>" method="POST">
    <input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
<?php else: ?>
<form name="finish_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_poll')); ?>" method="POST">
<?php endif; ?>
    <input type="hidden" name="chosenDates" id="chosenDates" value="<?php if(isset($chosen)) p($chosen); ?>" />
    <input type="hidden" name="expireTs" id="expireTs" value="<?php if(isset($expireTs)) p($expireTs); ?>" />
    <input type="hidden" name="userId" id="userId" value="<?php p($userId); ?>" />

    <header class="row">
        <div class="col-100">
            <?php if($isUpdate): ?>
                <h1><?php p($l->t('Edit poll') . ' ' . $poll->getTitle()); ?></h1>
            <?php else: ?>
                <h1><?php p($l->t('Create new poll')); ?></h1>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="new_poll row">
        <div class="col-50">
            <h2><?php p($l->t('Basic information')); ?></h2>
            <label for="pollTitle" class="input_title"><?php p($l->t('Title')); ?></label>
            <input type="text" class="input_field" id="pollTitle" name="pollTitle" value="<?php if(isset($title)) p($title); ?>" />
            <label for="pollDesc" class="input_title"><?php p($l->t('Description')); ?></label>
            <textarea class="input_field" id="pollDesc" name="pollDesc"><?php if(isset($desc)) p($desc); ?></textarea>

            <label class="input_title"><?php p($l->t('Access')); ?></label>

            <input type="radio" name="accessType" id="private" value="registered" <?php if(!$isUpdate || $access === 'registered') print_unescaped('checked'); ?> />
            <label for="private"><?php p($l->t('Registered users only')); ?></label>

            <input type="radio" name="accessType" id="hidden" value="hidden" <?php if($isUpdate && $access === 'hidden') print_unescaped('checked'); ?> />
            <label for="hidden"><?php p($l->t('hidden')); ?></label>

            <input type="radio" name="accessType" id="public" value="public" <?php if($isUpdate && $access === 'public') print_unescaped('checked'); ?> />
            <label for="public"><?php p($l->t('Public access')); ?></label>

            <input type="radio" name="accessType" id="select" value="select" <?php if($isUpdate && $access === 'select') print_unescaped('checked'); ?>>
            <label for="select"><?php p($l->t('Select')); ?></label>
            <span id="id_label_select">...</span>
            
            <div id="access_rights" class="row">
                <div class="col-50">
                    <h3><?php p($l->t('Groups')); ?></h3>
                    <ul>
                        <?php $groups = OC_Group::getUserGroups($userId); ?>
                        <?php foreach($groups as $gid) : ?>
                            <li class="cl_group_item cl_access_item" id="group_<?php p($gid); ?>">
                                <?php p($gid); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-50">
                    <h3><?php p($l->t('Users')); ?></h3>
                    <ul>
                        <?php $users = $userMgr->search(''); ?>
                        <?php foreach ($users as $user) : ?>
                            <li class="cl_user_item cl_access_item" id="user_<?php p($user->getUID()); ?>" >
                                <?php p($user->getDisplayName()); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <input type="hidden" name="accessValues" id="accessValues" value="<?php if($isUpdate && $access === 'select') p($accessTypes) ?>" />

            <label class="input_title"><?php p($l->t('Type')); ?></label>

            <input type="radio" name="pollType" id="event" value="event" <?php if(!$isUpdate || $poll->getType() === '0') print_unescaped('checked'); ?> />
            <label for="event"><?php p($l->t('Event schedule')); ?></label>

            <!-- TODO texts to db -->
            <input type="radio" name="pollType" id="text" value="text" <?php if($isUpdate && $poll->getType() === '1') print_unescaped('checked'); ?>>
            <label for="text"><?php p($l->t('Text based')); ?></label>

            <label for="id_expire_set" class="input_title"><?php p($l->t('Expires')); ?></label>
            <div class="input-group" id="expiration">
                <div class="input-group-addon">
                    <input id="id_expire_set" name="check_expire" type="checkbox" <?php ($isUpdate && $poll->getExpire() !== null) ? print_unescaped('value="true" checked') : print_unescaped('value="false"'); ?> />
                </div>
                <input id="id_expire_date" type="text" required="" <?php (!$isUpdate || $poll->getExpire() === null) ? print_unescaped('disabled="true"') : print_unescaped('value="' . $expireStr . '"'); ?> name="expire_date_input" />
            </div>
        </div>
        <div class="col-50">
            <h2><?php p($l->t('Choices')); ?></h2>
            <div id="date-select-container" <?php if($isUpdate && $poll->getType() === '1') print_unescaped('style="display:none;"'); ?> >
                <label for="datetimepicker" class="input_title"><?php p($l->t('Dates')); ?></label>
                <input id="datetimepicker" type="text" />
                <table id="selected-dates-table" class="choices">
                </table>
            </div>
            <div id="text-select-container" <?php if(!$isUpdate || $poll->getType() === '0') print_unescaped('style="display:none;"'); ?> >
                <label for="text-title" class="input_title"><?php p($l->t('Text item')); ?></label>
                <div class="input-group">
                    <input type="text" id="text-title" placeholder="<?php print_unescaped('Insert text...'); ?>" />
                    <div class="input-group-btn">
                        <input type="button" id="text-submit" value="<?php p($l->t('Add')); ?>" class="btn"/>
                    </div>
                </div>
                <table id="selected-texts-table" class="choices">
                </table>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <?php if($isUpdate): ?>
            <input type="submit" id="submit_finish_poll" value="<?php p($l->t('Update poll')); ?>" />
        <?php else: ?>
            <input type="submit" id="submit_finish_poll" value="<?php p($l->t('Create poll')); ?>" />
        <?php endif; ?>
        <a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>" id="submit_cancel_poll" class="button"><?php p($l->t('Cancel')); ?></a>
    </div>
</form>
</div>
</div>
</div>
