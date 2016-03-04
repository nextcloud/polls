<?php
\OCP\Util::addStyle('polls', 'main');
\OCP\Util::addScript('polls', 'vote');

$userId = $_['userId'];
$userMgr = $_['userMgr'];
$urlGenerator = $_['urlGenerator'];
$avaMgr = $_['avatarManager'];
use \OCP\User;

$poll = $_['poll'];
$dates = $_['dates'];
$votes = $_['votes'];
$comments = $_['comments'];
$notification = $_['notification'];
$poll_type = $poll->getType();
if ($poll->getExpire() === null) $expired = false;
else {
    $expired = time() > strtotime($poll->getExpire());
}

if ($poll->getType() === '0') {
    // count how many times in each date
    $arr_dates = null;  // will be like: [21.02] => 3
    $arr_years = null;  // [1992] => 6
    foreach($dates as $d) {
        $date = date('d.m.Y', strtotime($d->getDt()));
        $arr = explode('.', $date);
        $day_month = $arr[0] . '.' . $arr[1] . '.'; // 21.02
        $year = $arr[2];                      // 1992

        if (isset($arr_dates[$day_month])) {
            $arr_dates[$day_month] += 1;
        } else {
            $arr_dates[$day_month] = 1;
        }

        // -----
        if (isset($arr_years[$year])) {
            $arr_years[$year] += 1;
        } else {
            $arr_years[$year] = 1;
        }

    }

    $for_string_dates = '';
    foreach (array_keys($arr_dates) as $dt) {                           // date (13.09)
        $for_string_dates .= '<th colspan="' . $arr_dates[$dt] . '">' . $dt . '</th>';
    }

    $for_string_years = '';
    foreach (array_keys($arr_years) as $year) {                         // year (1992)
        $for_string_years .= '<th colspan="' . $arr_years[$year] . '">' . $year . '</th>';
    }

}
if($poll->getDescription() !== null && $poll->getDescription() !== '') $line = str_replace("\n", '<br/>', $poll->getDescription());
else $line = $l->t('No description provided.');

// ----------- title / descr --------
?>
<!-- TODO reimplement?
<?php if(!User::isLoggedIn()) : ?>
    <p>
        <header>
                <div id="header">
                <a href="<?php print_unescaped(link_to('', 'index.php')); ?>"
                                title="" id="owncloud">
                    <div class="logo-wide svg"></div>
                </a>
                        <div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
                        <div class="header-right">
                    <?php p($l->t('Already have an account?')); ?>
                    <?php $url = OCP\Util::linkToAbsolute( '', 'index.php' ).'?redirect_url='.$urlGenerator->linkToRoute('polls_goto', array('poll_id' => $poll_id)); ?>
                    <a href="<?php p($url); ?>"><?php p($l->t('Login')); ?></a>
                </div>
            </div>
        </header>
    </p>
    <p>&nbsp;</p><p>&nbsp;</p> <?php // for some reason the header covers the title otherwise ?>
<?php endif; ?>
-->


<h1><?php p($poll->getTitle()); ?></h1>
<div class="wordwrap desc"><?php p($line); ?></div>

<?
// -------------- url ---------------

?>
<h2><?php p($l->t('Poll URL')); ?></h2>
<p class="url">
    <?php
        $url = $urlGenerator->linkToRoute('polls.page.goto_poll', ['hash' => $poll->getHash()]);
    ?>
    <a href="<?php p($url);?>"><?php p($url); ?></a>
</p>


<div class="scroll_div">
    <table class="vote_table" id="id_table_1"> <?php //from above title ?>
        <tr>
            <th></th>
            <?php
            if ($poll_type === '0') {
                print_unescaped($for_string_years);
            }
            else {
                foreach ($dates as $el) {
                    print_unescaped('<th title="' . $el->getText(). '">' . $el->getText() . '</th>');
                }
            }
            ?>
        </tr>
        <?php
        if ($poll_type === '0'){
            print_unescaped('<tr><th></th>' .  $for_string_dates . '</tr>');

            print_unescaped('<tr><th></th>');
            for ($i = 0; $i < count($dates); $i++) {
                $ch_obj = date('H:i', strtotime($dates[$i]->getDt()));
                print_unescaped('<th>' . $ch_obj . '</th>');
            }
            print_unescaped('</tr>');
        }

        // init array for counting 'yes'-votes for each dt
        $total_y = array();
        $total_n = array();
        for ($i = 0; $i < count($dates); $i++){
            $total_y[$i] = 0;
            $total_n[$i] = 0;
        }
        $user_voted = array();
        // -------------- other users ---------------
        // loop over users
        ?>
        <?php
        if ($votes !== null) {
            //group by user
            $others = array();
            foreach ($votes as $vote) {
                if (!isset($others[$vote->getUserId()])) {
                    $others[$vote->getUserId()] = array();
                }
                array_push($others[$vote->getUserId()], $vote);
            }
            foreach (array_keys($others) as $usr) {
                if ($usr === $userId) {
                    // if poll expired, just put current user among the others;
                    // otherwise skip here to add current user as last row (to vote)
                    if (!$expired) {
                        $user_voted = $others[$usr];
                        continue;
                    }
                }
                print_unescaped('<tr>');
                print_unescaped('<th>');
                if($userMgr->get($usr) != null) {
                    $avatar = $avaMgr->getAvatar($usr)->get(32);
                    if($avatar !== false) {
                        $avatarImg = '<img class="userNameImg" src="data:' . $avatar->mimeType() . ';base64,' . $avatar . '" />';
                    } else {
                        $avatarImg = '<div class="userNameImg noAvatar" style="background-color:' . getHsl($usr) . ';">' . strtoupper($usr[0]) . '</div>';
                    }
                    print_unescaped($avatarImg);
                    p($userMgr->get($usr)->getDisplayName());
                }
                else p($usr);
                print_unescaped('</th>');
                $i_tot = -1;

                // loop over dts
                foreach($dates as $dt) {
                    $i_tot++;

                    $date_id = '';
                    if ($poll_type === '0') {
                        $date_id = strtotime($dt->getDt());
                    }
                    else {
                        $date_id = $dt->getText();
                    }
                    // look what user voted for this dts
                    $found = false;
                    foreach ($others[$usr] as $vote) {
                        if ($date_id === strtotime($vote->getDt())) {
                            if ($vote->getType() === '1') {
                                $cl = 'poll-cell-is';
                                $total_y[$i_tot]++;
                            }
                            else if ($vote->getType() === '0') {
                                $cl = 'poll-cell-not';
                                $total_n[$i_tot]++;
                            } else {
                                $cl = 'poll-cell-maybe';
                            }
                            $found = true;
                            break;
                        }
                    }
                    if(!$found) {
                        $cl = 'poll-cell-un';
                    }
                    print_unescaped('<td class="' . $cl . '"></td>');
                }
                print_unescaped('</tr>');
            }
        }
        // -------------- current user --------------
        ?>
        <tr>
            <?php
            if (!$expired) {
                if (User::isLoggedIn()) {
                    print_unescaped('<th>');
                    $avatar = $avaMgr->getAvatar($userId)->get(32);
                    if($avatar !== false) {
                        $avatarImg = '<img class="userNameImg" src="data:' . $avatar->mimeType() . ';base64,' . $avatar . '" />';
                    } else {
                        $avatarImg = '<div class="userNameImg noAvatar" style="background-color:' . getHsl($userId) . ';">' . strtoupper($userId[0]) . '</div>';
                    }
                    print_unescaped($avatarImg);
                    p($userMgr->get($userId)->getDisplayName());
                    print_unescaped('</th>');
                } else {
                    print_unescaped('<th id="id_ac_detected" ><input type="text" name="user_name" id="user_name" /></th>');
                }
                $i_tot = -1;
                $date_id = '';
                foreach ($dates as $dt) {
                    $i_tot++;
                    if ($poll_type === '0') {
                        $date_id = strtotime($dt->getDt());
                    } else {
                        $date_id = $dt->getText();
                    }
                    // see if user already has data for this event
                    $cl = 'poll-cell-active-un';
                    if (isset($user_voted)) {
                        foreach ($user_voted as $obj) {
                            $voteVal = null;
                            if($poll_type === '0') $voteVal = strtotime($obj->getDt());
                            else $voteVal = $obj->getText();
                            if ($voteVal === $date_id) {
                                if ($obj->getType() === '1') {
                                    $cl = 'poll-cell-active-is';
                                    $total_y[$i_tot]++;
                                } else if ($obj->getType() === '0') {
                                    $cl = 'poll-cell-active-not';
                                    $total_n[$i_tot]++;
                                } else if($obj->getType() === '2'){
                                    //$total_m[$i_tot]++;
                                    $cl = 'poll-cell-active-maybe';
                                }
                                break;
                            }
                        }
                    }
                    print_unescaped('<td class="cl_click ' . $cl . '" id="' . $date_id . '"></td>');
                }
            }
            ?>
        </tr>
        <?php // --------------- total -------------------- ?>
        <?php
            $diff_array = $total_y;
            for($i = 0; $i < count($diff_array); $i++){
                $diff_array[$i] = ($total_y[$i] - $total_n[$i]);
            }
            $max_votes = max($diff_array);
        ?>
        <tr>
            <th><?php p($l->t('Total')); ?>:</th>
            <?php for ($i = 0; $i < count($dates); $i++) : ?>
                <td>
                    <?php if($poll_type === '0'): ?>
                    <table id="id_tab_total">
                        <tr>
                            <td id="id_y_<?php p(strtotime($dates[$i]->getDt())); ?>"
                                <?php if(isset($total_y[$i])) : ?>
                                    <?php if( $total_y[$i] - $total_n[$i] === $max_votes) : ?>
                                        <?php
                                            $class = 'cl_total_y cl_win';
                                        ?>
                                    <?php else : ?>
                                        <?php $class='cl_total_y'; ?>
                                    <?php endif; ?>
                                    <?php $val = $total_y[$i]; ?>
                                <?php else : ?>
                                    <?php $val = 0; ?>
                                <?php endif; ?>
                                class="<?php p($class); ?>"><?php p($val); ?>
                            </td>
                        </tr>
                        <tr>
                            <td id="id_n_<?php p(strtotime($dates[$i]->getDt())); ?>" class="cl_total_n"><?php p(isset($total_n[$i]) ? $total_n[$i] : '0'); ?></td>
                        </tr>
                    </table>
                    <?php endif; ?>
                </td>
            <?php endfor; ?>
        </tr>

        <?php // ------------ winner ----------------------- ?>
        <tr>
            <th><?php p($l->t('Win:')); ?></th>
            <?php for ($i = 0; $i < count($dates); $i++) :

                $check = '';
                if ($total_y[$i] - $total_n[$i] === $max_votes){
                    $check = 'icon-checkmark';
                }

                print_unescaped('<td class="win_row ' . $check . '" id="id_total_' . $i . '"></td>');

            endfor;
            ?>
        </tr>
        <?php
        if ($poll_type === '0'){
            print_unescaped('<tr><td></td>');
            for ($i = 0; $i < count($dates); $i++) {
                $ch_obj = date('H:i', strtotime($dates[$i]->getDt()));
                print_unescaped('<th>' . $ch_obj . '</th>');
            }
            print_unescaped('</tr>');
            print_unescaped('<tr><td></td>' .  $for_string_dates . '</tr>');
        }
        ?>
        <tr>
            <?php
            if ($poll_type === '0') {
                print_unescaped('<th>' . $for_string_years . '</th>');
            }
            else {
                foreach ($dates as $el) {
                    print_unescaped('<th title="' . $el->getText() . '">' . $el->getText() . '</th>');
                }
            }
            ?>
        </tr>
    </table>
</div>

<?php if(User::isLoggedIn()) : ?>
    <input type="checkbox" id="check_notif" <?php if($notification !== null) print_unescaped(' checked'); ?> />
    <label for="check_notif"><?php p($l->t('Receive notification email on activity')); ?></label>
    <br/>
<?php endif; ?>

<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>" style="float:left;padding-right: 5px;"><input type="button" class="icon-home" /></a>
<form name="finish_vote" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_vote')); ?>" method="POST">
    <input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
    <input type="hidden" name="userId" value="<?php p($userId); ?>" />
    <input type="hidden" name="dates" value="<?php p($poll->getId()); ?>" />
    <input type="hidden" name="types" value="<?php p($poll->getId()); ?>" />
    <input type="hidden" name="notif" />
    <input type="hidden" name="changed" />
    <input type="button" id="submit_finish_vote" value="<?php p($l->t('Vote!')); ?>" />
</form>


<?php if($expired) : ?>
<div id="expired_info">
    <h2><?php p($l->t('Poll expired')); ?></h2>
    <p>
        <?php p($l->t('The poll expired on %s. Voting is disabled, but you can still comment.', array(date('d.m.Y H:i', strtotime($poll->getExpire()))))); ?>
    </p>
</div>
<?php endif; ?>

<?php // -------- comments ---------- ?>
<h2><?php p($l->t('Comments')); ?></h2>
<div class="cl_user_comments">
    <?php if($comments !== null) : ?>
    <?php foreach ($comments as $comment) : ?>
        <div class="user_comment">
            <?php
                p($userMgr->get($comment->getUserId())->getDisplayName());
                print_unescaped(' <i class="date">' . date('d.m.Y H:i', strtotime($comment->getDt())) . '</i>');
            ?>
            <div class="wordwrap user_comment_text">
                <?php p($comment->getComment()); ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php else : ?>
        <?php p($l->t('No comments yet. Be the first.')); ?>
    <?php endif; ?>
    <div class="cl_comment">
        <form name="send_comment" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_comment')); ?>" method="POST">
            <input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
            <input type="hidden" name="userId" value="<?php p($userId); ?>" />
            <?php // -------- leave comment ---------- ?>
            <h3><?php p($l->t('Write new Comment')); ?></h3>
            <textarea style="width: 300px;" cols="50" rows="3" id="commentBox" name="commentBox"></textarea>
            <br/>
            <input type="button" id="submit_send_comment" value="<?php p($l->t('Send!')); ?>" />
        </form>
    </div>
</div>

<?php
    //adapted from jsxc.chat
    function getHsl($str) {
        $hash = 0;
        for($i=0; $i<strlen($str); $i++) {
            $utf16_char = mb_convert_encoding($str[i], "utf-16", "utf-8");
            $char = hexdec(bin2hex($utf16_char));
            $hash = (($hash << 5) - $hash) + $char;
            $hash |= 0; // Convert to 32bit integer
        }
        $hue = abs($hash) % 360;
        $saturation = 90;
        $lightness = 65;
        return 'hsl(' . $hue . ', ' . $saturation . '%, ' . $lightness . '%)';
    }
?>
