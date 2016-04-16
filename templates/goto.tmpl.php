<?php

use \OCP\User;

\OCP\Util::addStyle('polls', 'main');
\OCP\Util::addStyle('polls', 'vote');
\OCP\Util::addScript('polls', 'vote');

$userId = $_['userId'];
$userMgr = $_['userMgr'];
$urlGenerator = $_['urlGenerator'];
$avaMgr = $_['avatarManager'];

$poll = $_['poll'];
$dates = $_['dates'];
$votes = $_['votes'];
$comments = $_['comments'];
$notification = $_['notification'];

if ($poll->getExpire() === null) {
    $expired = false;
} else {
    $expired = time() > strtotime($poll->getExpire());
}

if ($poll->getType() === '0') {
    // count how many times in each date
    $arr_dates = null;  // will be like: [21.02] => 3
    $arr_years = null;  // [1992] => 6
    foreach($dates as $d) {
        $day_month = date('d.m', strtotime($d->getDt()));
        $year = date('Y', strtotime($d->getDt()));

        if (isset($arr_dates[$day_month])) {
            $arr_dates[$day_month] += 1;
        } else {
            $arr_dates[$day_month] = 1;
        }

        if (isset($arr_years[$year])) {
            $arr_years[$year] += 1;
        } else {
            $arr_years[$year] = 1;
        }
    }

    $for_string_dates = '';
    foreach (array_keys($arr_dates) as $dt) {
        $for_string_dates .= '<th colspan="' . $arr_dates[$dt] . '" class="bordered">' . $dt . '</th>';
    }

    $for_string_years = '';
    foreach (array_keys($arr_years) as $year) {
        $for_string_years .= '<th colspan="' . $arr_years[$year] . '" class="bordered">' . $year . '</th>';
    }
}
if ($poll->getDescription() !== null && $poll->getDescription() !== '') {
    $line = str_replace("\n", '<br/>', $poll->getDescription());
} else {
    $line = $l->t('No description provided.');
}

// init array for counting 'yes'-votes for each date
$total_y = array();
$total_n = array();
for ($i = 0 ; $i < count($dates) ; $i++) {
    $total_y[$i] = 0;
    $total_n[$i] = 0;
}
$user_voted = array();

$pollUrl = OCP\Util::linkToAbsolute('', $urlGenerator->linkToRoute('polls.page.goto_poll', ['hash' => $poll->getHash()]));
?>

<div id="app">
    <div id="app-content">
        <div id="app-content-wrapper">
            <?php if(!User::isLoggedIn()) : ?>
                <div class="row">
                    <div class="col-100">
                        <div class="alert-info">
                            <?php
                                p($l->t('Already have an account?'));
                                $loginUrl = OCP\Util::linkToAbsolute('', 'index.php' ) . '?redirect_url=' . $urlGenerator->linkToRoute('polls.page.goto_poll', ['hash' => $poll->getHash()]);
                            ?>
                            <a href="<?php p($loginUrl); ?>"><?php p($l->t('Login')); ?></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <header class="row">
                <div class="col-100">
                    <h1><?php p($poll->getTitle()); ?></h1>
                    <div class="wordwrap desc"><?php p($line); ?></div>
                </div>
            </header>
            <div class="row">
                <div class="col-70">
                    <h2><?php p($l->t('Poll')); ?></h2>
                    <div class="scroll_div">
                        <table class="vote_table" id="id_table_1">
                            <thead>
                                <tr>
                                    <?php
                                    if ($poll->getType() === '0') {
                                        print_unescaped('<th rowspan=3></th>');
                                        print_unescaped($for_string_years);
                                        print_unescaped('<th class="bordered" rowspan=3>' . $l->t('All') . '</th>');
                                    } else {
                                        print_unescaped('<th></th>');
                                        foreach ($dates as $el) {
                                            print_unescaped('<th title="' . $el->getText(). '" class="bordered">' . $el->getText() . '</th>');
                                        }
                                        print_unescaped('<th class="bordered">' . $l->t('All') . '</th>');
                                    }
                                    ?>
                                </tr>
                                <?php
                                if ($poll->getType() === '0'){
                                    print_unescaped('<tr>' .  $for_string_dates . '</tr><tr>');
                                    $prev = "";
                                    for ($i = 0; $i < count($dates); $i++) {
                                        $c = ($prev != date('Y-m-d', strtotime($dates[$i]->getDt())) ? ' bordered' : '');
                                        $prev = date('Y-m-d', strtotime($dates[$i]->getDt()));
                                        $ch_obj = date('H:i', strtotime($dates[$i]->getDt()));
                                        print_unescaped('<th class="time-slot-cell' . $c . '">' . $ch_obj . '</th>');
                                        
                                    }
                                    print_unescaped('</tr>');
                                }
                                ?>
                            </thead>
                            <tbody class="votes">
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
                                        if($userMgr->get($usr) != null) {
                                            print_unescaped('<th class="user-cell">');
                                            $avatar = $avaMgr->getAvatar($usr)->get(32);
                                            if($avatar !== false) {
                                                print_unescaped('<img class="userNameImg" src="data:' . $avatar->mimeType() . ';base64,' . $avatar . '" />');
                                            } else {
                                                print_unescaped('<div class="userNameImg noAvatar" style="background-color:' . getHsl($usr) . ';">' . strtoupper($usr[0]) . '</div>');
                                            }
                                            p($userMgr->get($usr)->getDisplayName());
                                        } else {
                                            print_unescaped('<th class="user-cell external"> '. $usr);
                                        }
                                        print_unescaped('</th>');

                                        // loop over dts
                                        $i_tot = 0;
                                        foreach($dates as $dt) {
                                            if ($poll->getType() === '0') {
                                                $date_id = strtotime($dt->getDt());
                                            } else {
                                                $date_id = $dt->getText();
                                            }
                                            // look what user voted for this dts
                                            $found = false;
                                            foreach ($others[$usr] as $vote) {
                                                if ($date_id === strtotime($vote->getDt())) {
                                                    if ($vote->getType() === '1') {
                                                        $cl = 'poll-cell-is';
                                                        $total_y[$i_tot]++;
                                                    } else if ($vote->getType() === '0') {
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
                                            $i_tot++;
                                        }
                                        print_unescaped('<td></td>');
                                        print_unescaped('</tr>');
                                    }
                                }
                                $total_y_others = array_merge(array(), $total_y);
                                $total_n_others = array_merge(array(), $total_n);
                                if (!$expired) {
                                    print_unescaped('<tr class="current-user">');
                                    if (User::isLoggedIn()) {
                                        print_unescaped('<th class="user-cell">');
                                        $avatar = $avaMgr->getAvatar($userId)->get(32);
                                        if($avatar !== false) {
                                            print_unescaped('<img class="userNameImg" src="data:' . $avatar->mimeType() . ';base64,' . $avatar . '" />');
                                        } else {
                                            print_unescaped('<div class="userNameImg noAvatar" style="background-color:' . getHsl($userId) . ';">' . strtoupper($userId[0]) . '</div>');
                                        }
                                        p($userMgr->get($userId)->getDisplayName());
                                        print_unescaped('</th>');
                                    } else {
                                        print_unescaped('<th id="id_ac_detected" class="external current-user"><input type="text" name="user_name" id="user_name" placeholder="' . $l->t('Your name here') . '" /></th>');
                                    }
                                    $i_tot = 0;
                                    foreach ($dates as $dt) {
                                        if ($poll->getType() === '0') {
                                            $date_id = strtotime($dt->getDt());
                                        } else {
                                            $date_id = $dt->getText();
                                        }
                                        // see if user already has data for this event
                                        $cl = 'poll-cell-active-un';
                                        if (isset($user_voted)) {
                                            foreach ($user_voted as $obj) {
                                                $voteVal = null;
                                                if($poll->getType() === '0') {
                                                    $voteVal = strtotime($obj->getDt());
                                                } else {
                                                    $voteVal = $obj->getText();
                                                }
                                                if ($voteVal === $date_id) {
                                                    if ($obj->getType() === '1') {
                                                        $cl = 'poll-cell-active-is';
                                                        $total_y[$i_tot]++;
                                                    } else if ($obj->getType() === '0') {
                                                        $cl = 'poll-cell-active-not';
                                                        $total_n[$i_tot]++;
                                                    } else if($obj->getType() === '2'){
                                                        $cl = 'poll-cell-active-maybe';
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                        print_unescaped('<td class="cl_click ' . $cl . '" id="' . $date_id . '"></td>');
                                        $i_tot++;
                                    }
                                    print_unescaped('<td class="toggle-all selected-maybe"></td></tr>');
                                }
                                ?>
                            </tbody>
                            <tbody class="total">
                                <?php
                                    $diff_array = $total_y;
                                    for($i = 0 ; $i < count($diff_array) ; $i++) {
                                        $diff_array[$i] = ($total_y[$i] - $total_n[$i]);
                                    }
                                    $max_votes = max($diff_array);
                                ?>
                                <tr>
                                    <th><?php p($l->t('Total')); ?></th>
                                    <?php for ($i = 0 ; $i < count($dates) ; $i++) : ?>
                                        <td class="total">
                                            <?php
                                            $classSuffix = $poll->getType() === '0' ? strtotime($dates[$i]->getDt()) : str_replace(' ', '_', $dates[$i]->getText());
                                            if (isset($total_y[$i])) {
                                                $val = $total_y[$i];
                                            } else {
                                                $val = 0;
                                            }
                                            ?>
                                            <div id="id_y_<?php p($classSuffix); ?>" class="color_yes" data-value=<?php p(isset($total_y_others[$i]) ? $total_y_others[$i] : '0'); ?>>
                                                <?php p($val); ?>
                                            </div>
                                            <div id="id_n_<?php p($classSuffix); ?>" class="color_no" data-value=<?php p(isset($total_n_others[$i]) ? $total_n_others[$i] : '0'); ?>>
                                                <?php p(isset($total_n[$i]) ? $total_n[$i] : '0'); ?>
                                            </div>
                                        </td>
                                    <?php endfor; ?>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th><?php p($l->t('Best option')); ?></th>
                                    <?php
                                    for ($i = 0; $i < count($dates); $i++) {
                                        $check = '';
                                        if ($total_y[$i] - $total_n[$i] === $max_votes){
                                            $check = 'icon-checkmark';
                                        }
                                        print_unescaped('<td class="win_row ' . $check . '" id="id_total_' . $i . '"></td>');
                                    }
                                    ?>
                                    <td class="bordered"></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <?php
                                if ($poll->getType() === '0') {
                                    print_unescaped('<tr><th rowspan=3></th>');
                                    $prev = "";
                                    for ($i = 0; $i < count($dates); $i++) {
                                        $c = ($prev != date('Y-m-d', strtotime($dates[$i]->getDt())) ? ' bordered' : '');
                                        $prev = date('Y-m-d', strtotime($dates[$i]->getDt()));
                                        $ch_obj = date('H:i', strtotime($dates[$i]->getDt()));
                                        print_unescaped('<th class="time-slot-cell' . $c . '">' . $ch_obj . '</th>');
                                    }
                                    print_unescaped('<th rowspan=3 class="bordered">' . $l->t('All') . '</th></tr>');
                                    print_unescaped('<tr>' .  $for_string_dates . '</tr>');
                                }
                                ?>
                                <tr>
                                    <?php
                                    if ($poll->getType() === '0') {
                                        print_unescaped($for_string_years);
                                    } else {
                                        print_unescaped('<th></th>');
                                        foreach ($dates as $el) {
                                            print_unescaped('<th title="' . $el->getText() . '" class="bordered">' . $el->getText() . '</th>');
                                        }
                                        print_unescaped('<th class="bordered"></th>');
                                    }
                                    ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="input-group share">
                        <div class="input-group-addon">
                            <span class="icon-share"></span> Link
                        </div>
                        <input type="text" value="<?php p($pollUrl);?>" readonly="readonly">
                    </div>
                    <?php if(User::isLoggedIn()) : ?>
                        <p>
                            <input type="checkbox" id="check_notif" <?php if($notification !== null) print_unescaped(' checked'); ?> />
                            <label for="check_notif"><?php p($l->t('Receive notification email on activity')); ?></label>
                        </p>
                    <?php endif; ?>

                    <form name="finish_vote" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_vote')); ?>" method="POST">
                        <input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
                        <input type="hidden" name="userId" value="<?php p($userId); ?>" />
                        <input type="hidden" name="dates" value="<?php p($poll->getId()); ?>" />
                        <input type="hidden" name="types" value="<?php p($poll->getId()); ?>" />
                        <input type="hidden" name="notif" />
                        <input type="hidden" name="changed" />
                        <input type="button" id="submit_finish_vote" value="<?php p($l->t('Vote!')); ?>" />
                        <?php if(User::isLoggedIn()) : ?>
                            <a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>" class="button home-link"><?php p($l->t('Polls summary')); ?></a>
                        <?php endif; ?>
                    </form>


                    <?php if($expired) : ?>
                        <div id="expired_info">
                            <h2><?php p($l->t('Poll expired')); ?></h2>
                            <p>
                                <?php p($l->t('The poll expired on %s. Voting is disabled, but you can still comment.', array(date('d.m.Y H:i', strtotime($poll->getExpire()))))); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-30">
                    <h2><?php p($l->t('Comments')); ?></h2>
                    <div class="comments">
                        <div class="comment new-comment">
                            <?php if(!User::isLoggedIn()) : ?>
                                <div class="comment-content">
                                    <?php
                                    p($l->t('You must be logged in to post a comment.'));
                                    ?>
                                    <a href="<?php p($loginUrl); ?>"><?php p($l->t('Login')); ?></a>
                                </div>
                            <?php else: ?>
                                <form name="send_comment" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_comment')); ?>" method="POST">
                                    <input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
                                    <input type="hidden" name="userId" value="<?php p($userId); ?>" />
                                    <div class="comment-content">
                                        <textarea id="commentBox" name="commentBox"></textarea>
                                        <p>
                                            <input type="button" id="submit_send_comment" value="<?php p($l->t('Send!')); ?>" />
                                            <span class="icon-loading-small" style="float:right;"></span>
                                        </p>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if($comments !== null) : ?>
                            <?php foreach ($comments as $comment) : ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <?php
                                        print_unescaped('<span class="comment-date">' . date('d.m.Y H:i:s', strtotime($comment->getDt())) . '</span>');
                                        p($userMgr->get($comment->getUserId())->getDisplayName());
                                        ?>
                                    </div>
                                    <div class="wordwrap comment-content">
                                        <?php p($comment->getComment()); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php p($l->t('No comments yet. Be the first.')); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
//adapted from jsxc.chat
function getHsl($str) {
    $hash = 0;
    for($i=0; $i<strlen($str); $i++) {
        $utf16_char = mb_convert_encoding($str[$i], "utf-16", "utf-8");
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
