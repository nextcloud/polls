<?php
    \OCP\Util::addStyle('polls', 'main');
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

<?php if($isUpdate): ?>
<form name="finish_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.update_poll')); ?>" method="POST">
    <input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
<?php else: ?>
<form name="finish_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_poll')); ?>" method="POST">
<?php endif; ?>
    <input type="hidden" name="chosenDates" id="chosenDates" value="<?php if(isset($chosen)) p($chosen); ?>" />
    <input type="hidden" name="expireTs" id="expireTs" value="<?php if(isset($expireTs)) p($expireTs); ?>" />
    <input type="hidden" name="userId" id="userId" value="<?php p($userId); ?>" />

    <div class="new_poll">
        <?php if($isUpdate): ?>
        <h1><?php p($l->t('Edit poll') . ' ' . $poll->getTitle()); ?></h1>
        <?php else: ?>
        <h1><?php p($l->t('Create new poll')); ?></h1>
        <?php endif; ?>
        <label for="text_title" class="label_h1 input_title"><?php p($l->t('Title')); ?></label>
        <input type="text" class="input_field" id="pollTitle" name="pollTitle" value="<?php if(isset($title)) p($title); ?>" />
        <label for="pollDesc" class="label_h1 input_title"><?php p($l->t('Description')); ?></label>
        <textarea cols="50" rows="5" style="width: auto;" class="input_field" id="pollDesc" name="pollDesc"><?php if(isset($desc)) p($desc); ?></textarea>

        <div class="input_title"><?php p($l->t('Access')); ?></div>

        <input type="radio" name="accessType" id="private" value="registered" <?php if(!$isUpdate || $access === 'registered') print_unescaped('checked'); ?> />
        <label for="private"><?php p($l->t('Registered users only')); ?></label>

        <input type="radio" name="accessType" id="hidden" value="hidden" <?php if($isUpdate && $access === 'hidden') print_unescaped('checked'); ?> />
        <label for="hidden"><?php p($l->t('hidden')); ?></label>

        <input type="radio" name="accessType" id="public" value="public" <?php if($isUpdate && $access === 'public') print_unescaped('checked'); ?> />
        <label for="public"><?php p($l->t('Public access')); ?></label>

        <input type="radio" name="accessType" id="select" value="select" <?php if($isUpdate && $access === 'select') print_unescaped('checked'); ?>>
        <label for="select"><?php p($l->t('Select')); ?></label>
        <span id="id_label_select">...</span>

        <input type="hidden" name="accessValues" id="accessValues" value="<?php if($isUpdate && $access === 'select') p($accessTypes) ?>" />

        <div class="input_title"><?php p($l->t('Type')); ?></div>

        <input type="radio" name="pollType" id="event" value="event" <?php if(!$isUpdate || $poll->getType() === '0') print_unescaped('checked'); ?> />
        <label for="event"><?php p($l->t('Event schedule')); ?></label>

        <!-- TODO texts to db -->
        <input type="radio" name="pollType" id="text" value="text" <?php if($isUpdate && $poll->getType() === '1') print_unescaped('checked'); ?>>
        <label for="text"><?php p($l->t('Text based')); ?></label>

        <br/>
        <input id="id_expire_set" name="check_expire" type="checkbox" <?php ($isUpdate && $poll->getExpire() !== null) ? print_unescaped('value="true" checked') : print_unescaped('value="false"'); ?> />
        <label for="id_expire_set"><?php p($l->t('Expires')); ?>:</label>
        <input id="id_expire_date" type="text" required="" <?php (!$isUpdate || $poll->getExpire() === null) ? print_unescaped('disabled="true"') : print_unescaped('value="' . $expireStr . '"'); ?> name="expire_date_input" />
        <br/>
        <div id="date-select-container" <?php if($isUpdate && $poll->getType() === '1') print_unescaped('style="display:none;"'); ?> >
            <label for="datetimepicker"><?php p($l->t('Dates')); ?>:</label>
            <br/>
            <input id="datetimepicker" type="text" />

            <table id="selected-dates-table">
            </table>
        </div>
        <div id="text-select-container" <?php if(!$isUpdate || $poll->getType() === '0') print_unescaped('style="display:none;"'); ?> >
            <label for="text-title"><?php p($l->t('Text item')); ?>:</label>
            <input type="text" id="text-title" placeholder="<?php print_unescaped('Insert text...'); ?>" />
            <br/>
            <input type="button" id="text-submit" value="<?php p($l->t('Add')); ?>"/>
            <table id="selected-texts-table">
            </table>
        </div>

        <br/>
        <a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>"><input type="button" id="submit_cancel_poll" value="<?php p($l->t('Cancel')); ?>" /></a>
        <input type="submit" id="submit_finish_poll" value="<?php p($l->t('Next')); ?>" />
    </div>
</form>

<div id="dialog-box">
    <div id="dialog-message"></div>
    <?php if (isset($url)) : ?>
        <input type="radio" name="radio_pub" id="private" value="registered"/>
        <label for="private"><?php p($l->t('Registered users only')); ?></label>
        <br/>
        <input type="radio" name="radio_pub" id="hidden" value="hidden" />
        <label for="hidden"><?php p($l->t('hidden')); ?></label>
        <br/>
        <input type="radio" name="radio_pub" id="public" value="public" />
        <label for="public"><?php p($l->t('Public access')); ?></label>
        <br/>
        <input type="radio" name="radio_pub" id="select" value="select" checked />
        <label for="select"><?php p($l->t('Select')); ?></label>
        <br/>
    <?php endif; ?>

    <table id="table_access">
        <tr>
            <td>
                <div class="scroll_div_dialog">
                    <table id="table_groups">
                            <tr>
                                <th><?php p($l->t('Groups')); ?></th>
                            </tr>
                        <?php $groups = OC_Group::getUserGroups($userId); ?>
                        <?php foreach($groups as $gid) : ?>
                            <tr>
                                <td class="cl_group_item" id="group_<?php p($gid); ?>"><?php p($gid); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </td>
            <td>
                <div class="scroll_div_dialog">
                    <table id="table_users">
                        <tr>
                            <th><?php p($l->t('Users')); ?></th>
                        </tr>
                        <?php $users = $userMgr->search(''); ?>
                        <?php foreach ($users as $user) : ?>
                            <tr>
                                <td class="cl_user_item" id="user_<?php p($user->getUID()); ?>" >
                                    <?php p($user->getDisplayName()); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    <input type="button" id="button_cancel_access" value="<?php p($l->t('Cancel')); ?>" />
    <input type="button" id="button_ok_access" value="<?php p($l->t('Ok')); ?>" />
</div>
