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
	$isAnonymous = $poll->getIsAnonymous() && $userId !== $poll->getOwner();
	$hideNames = $poll->getIsAnonymous() && $poll->getFullAnonymous();
	$notification = $_['notification'];

	if ($poll->getExpire() === null) {
		$expired = false;
	} else {
		$expired = time() > strtotime($poll->getExpire());
	}
?>

<?php if($poll->getType() === '0') : ?>
	<?php foreach($dates as $d) : ?>
		<input class="hidden-dates" type="hidden" value="<?php print_unescaped($d->getDt()); ?>" />
	<?php endforeach ?>
<?php endif ?>

<?php
if (   $poll->getDescription() !== null 
	&& $poll->getDescription() !== ''
) {
	$description = nl2br($poll->getDescription());
} else {
	$description = $l->t('No description provided.');
}

// init array for counting 'yes'-votes for each date
$total_y = array();
$total_n = array();
for ($i = 0 ; $i < count($dates) ; $i++) {
	$total_y[$i] = 0;
	$total_n[$i] = 0;
}
$user_voted = array();

$pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', ['hash' => $poll->getHash()]);
?>

<div id="app">
	<div id="app-content">
		<div id="app-content-wrapper">
			<div id="controls">
				<div id="breadcrump">
					<?php if (User::isLoggedIn()) : ?>
					<div class="crumb svg" data-dir="/">
						<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>">
							<img class="svg" src="<?php print_unescaped(OCP\image_path("core", "places/home.svg")); ?>"" alt="Home">
						</a>
					</div>
					<div class="crumb svg last">
						<span><?php p($poll->getTitle()); ?></span>
					</div>
					<?php endif; ?>

					<?php if (!User::isLoggedIn()) : ?>
					<div class="col-100">
						<h2><?php p($poll->getTitle()); ?></h2>
					</div>
					<?php endif; ?>
			</div>
			</div>
			<header class="row">
			</header>
			<div class="row">
				<div class="col-70">
					<div class="wordwrap desc"><?php p($description); ?></div>
					<div class="scroll_div">
						<table class="vote_table">
							<thead>
									<?php
									if ($poll->getType() === '0') {
										print_unescaped('<tr id="time-slots-header"><th class="first_header_cell" colspan="3"></th>');
									} else {
										print_unescaped('<tr id="vote-options-header"><th class="first_header_cell" colspan="3"></th>');
										foreach ($dates as $el) {
											print_unescaped('<th title="' . preg_replace('/_\d+$/', '', $el->getText()) . '" class="vote-option">' . preg_replace('/_\d+$/', '', $el->getText()) . '</th>');
										}
									}
									print_unescaped('</tr>');
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
									$userCnt = 0;
									foreach (array_keys($others) as $usr) {
										$userCnt++;
										if ($usr === $userId) {
											// if poll expired, just put current user among the others;
											// otherwise skip here to add current user as last row (to vote)
											if (!$expired) {
												$user_voted = $others[$usr];
												continue;
											}
										}
										print_unescaped('<tr>');
										print_unescaped('<td class="avatar-cell">');
										if (	$userMgr->get($usr) !== null 
											&& !$isAnonymous && !$hideNames
										) {
											print_unescaped('<div class="poll avatardiv" title="'.($usr).'"></div>');
											print_unescaped('</td>');
											print_unescaped('<td colspan="2" class="name">');
											p($userMgr->get($usr)->getDisplayName());
										} else {
											if ($isAnonymous || $hideNames) {
											print_unescaped('<div class="poll avatardiv" title="'.($userCnt).'"></div>');
											print_unescaped('</td>');
											print_unescaped('<td colspan="2" class="name">');
											} else {
												print_unescaped('<div class="poll avatardiv" title="'.($usr).'"></div>');
												print_unescaped('</td>');
												print_unescaped('<td colspan="2" class="name">');
												p($usr);
											}
										}
										print_unescaped('</td>');

										// loop over dts
										$i_tot = 0;
										foreach ($dates as $dt) {
											if ($poll->getType() === '0') {
												$date_id = strtotime($dt->getDt());
												$poll_id = "pollid_" . $dt->getId();
											} else {
												$date_id = $dt->getText();
												$poll_id = "pollid_" . $dt->getId();
											}
											// look what user voted for this dts
											$found = false;
											foreach ($others[$usr] as $vote) {
												$voteVal = null;
												if ($poll->getType() === '0') {
													$voteVal = strtotime($vote->getDt());
												} else {
													$voteVal = $vote->getText();
												}
												if ($date_id === $voteVal) {
													if ($vote->getType() === '1') {
														$cl = 'poll-cell yes';
														$total_y[$i_tot]++;
													} else if ($vote->getType() === '0') {
														$cl = 'poll-cell no';
														$total_n[$i_tot]++;
													} else if ($vote->getType() === '2') {
														$cl = 'poll-cell maybe';
													} else {
														$cl = 'poll-cell unvoted';
													}
													$found = true;
													break;
												}
											}
											if (!$found) {
												$cl = 'poll-cell unvoted';
											}
											// Make the td clickable
											print_unescaped('<td class="' . $cl . '"><div></div></td>');
											// Make the div clickable
											// print_unescaped('<td><div class="' . $cl . '"></div></td>');
											$i_tot++;
										}
										print_unescaped('</tr>');
									}
								}
								$total_y_others = array_merge(array(), $total_y);
								$total_n_others = array_merge(array(), $total_n);
								if (!$expired) {
									print_unescaped('<tr class="current-user">');
									print_unescaped('<td class="avatar-cell">');
									if (User::isLoggedIn()) {
										print_unescaped('<div class="poll avatardiv" title="'.($userId).'"></div>');
										print_unescaped('</td>');
										print_unescaped('<td class="name">');
										p($userMgr->get($userId)->getDisplayName());
									} else {
										print_unescaped('<div class="poll avatardiv" title="?"></div>');
										print_unescaped('</td>');
										print_unescaped('<td id="id_ac_detected" class="external current-user"><input type="text" name="user_name" id="user_name" placeholder="' . $l->t('Your name here') . '" />');
									}
									print_unescaped('</td><td class="toggle-all toggle maybe"><div id="toggle" class=""></div><img class="svg" src="../../../../core/img/actions/play-next.svg" "="" alt=""></td>');
									// print_unescaped('</td><td class="toggle-cell"><div id="toggle" class="toggle-all toggle maybe"></div><img class="svg" src="../../../../core/img/actions/play-next.svg" "="" alt=""></td>');
									$i_tot = 0;
									foreach ($dates as $dt) {
										if ($poll->getType() === '0') {
											$date_id = strtotime($dt->getDt());
											$poll_id = "pollid_" . $dt->getId();
										} else {
											$date_id = $dt->getText();
											$poll_id = "pollid_" . $dt->getId();
										}
										// see if user already has data for this event
										$cl = 'poll-cell active unvoted ';
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
														$cl = 'poll-cell active yes';
														$total_y[$i_tot]++;
													} else if ($obj->getType() === '0') {
														$cl = 'poll-cell active no';
														$total_n[$i_tot]++;
													} else if($obj->getType() === '2') {
														$cl = 'poll-cell active maybe';
													}
													break;
												}
											}
										}
										// Make the td clickable
										print_unescaped('<td id="' . $poll_id . '" class="cl_click ' . $cl . '" data-value="' . $date_id . '"><div></div></td>');
										// Make the div clickable
										// print_unescaped('<td><div id="' . $date_id . '" class="cl_click ' . $cl . '"></div></td>');

										$i_tot++;
									}
								}
								?>
							</tbody>
							<tbody class="summary">
								<?php
									$diff_array = $total_y;
									for($i = 0 ; $i < count($diff_array) ; $i++) {
										$diff_array[$i] = ($total_y[$i] - $total_n[$i]);
									}
									$max_votes = max($diff_array);
								?>
								<tr class="total">
									<th colspan="3"><?php p($l->t('Total')); ?></th>
									<?php for ($i = 0 ; $i < count($dates) ; $i++) : ?>
										<td class="total">
											<?php
											$classSuffix = "pollid_" . $dates[$i]->getId();
											if (isset($total_y[$i])) {
												$val = $total_y[$i];
											} else {
												$val = 0;
											}
											?>
											<div id="id_y_<?php p($classSuffix); ?>" class="result-cell yes" data-value=<?php p(isset($total_y_others[$i]) ? $total_y_others[$i] : '0'); ?>>
												<?php p($val); ?>
											</div>
											<div id="id_n_<?php p($classSuffix); ?>" class="result-cell no" data-value=<?php p(isset($total_n_others[$i]) ? $total_n_others[$i] : '0'); ?>>
												<?php p(isset($total_n[$i]) ? $total_n[$i] : '0'); ?>
											</div>
										</td>
									<?php endfor; ?>
								</tr>
								<tr class="best">
									<th colspan="3"><?php p($l->t('Best option')); ?></th>
									<?php
									for ($i = 0; $i < count($dates); $i++) {
										$check = '';
										if ($total_y[$i] - $total_n[$i] === $max_votes) {
											$check = 'icon-checkmark';
										}
										print_unescaped('<td class="win_row ' . $check . '" id="id_total_' . $i . '"></td>');
									}
									?>
								</tr>
							</tbody>
						</table>
						<form class="finish_vote" name="finish_vote" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_vote')); ?>" method="POST">
							<input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
							<input type="hidden" name="userId" value="<?php p($userId); ?>" />
							<input type="hidden" name="dates" value="<?php p($poll->getId()); ?>" />
							<input type="hidden" name="types" value="<?php p($poll->getId()); ?>" />
							<input type="hidden" name="receiveNotifications" />
							<input type="hidden" name="changed" />
							<input type="button" id="submit_finish_vote" class="button btn" value="<?php p($l->t('Vote!')); ?>" />
						</form>
						<?php if (User::isLoggedIn()) : ?>
						<span class="notification">
							<input type="checkbox" id="check_notif" class="checkbox" <?php if ($notification !== null) print_unescaped(' checked'); ?> />
							<label for="check_notif"><?php p($l->t('Receive notification email on activity')); ?></label>
						</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="col-30">
					<div class="input-group share">
						<div class="input-group-addon">
							<span class="icon-share"></span><?php p($l->t('Link')); ?>
						</div>
						<input type="text" value="<?php p($pollUrl);?>" readonly="readonly">
					</div>
					<?php if ($expired) : ?>
						<div id="expired_info">
							<h2><?php p($l->t('Poll expired')); ?></h2>
							<p>
								<?php p($l->t('The poll expired on %s. Voting is disabled, but you can still comment.', array(date('d.m.Y H:i', strtotime($poll->getExpire()))))); ?>
							</p>
						</div>
					<?php endif; ?>
					<h2><?php p($l->t('Comments')); ?></h2>
					<div class="comments">
						<div class="comment new-comment">
							<form name="send_comment" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_comment')); ?>" method="POST">
								<input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
								<input type="hidden" name="userId" value="<?php p($userId); ?>" />
								<div class="comment-content">
								<?php if (!User::isLoggedIn()) : ?>
									<a href="<?php p($urlGenerator->linkToRouteAbsolute('core.login.showLoginForm')); ?>"><?php p($l->t('Login')); ?></a>
									<?php p($l->t('or')); ?>
									<?php print_unescaped('<th id="id_ac_detected" class="external current-user"><input type="text" name="user_name_comm" id="user_name_comm" placeholder="' . $l->t('Your name here') . '" /></th>'); ?>
								<?php else: ?>
									<?php p($l->t('Logged in as') . ' ' . $userId); ?>
								<?php endif; ?>
									<textarea id="commentBox" name="commentBox"></textarea>
									<p>
										<input type="button" id="submit_send_comment" class="button btn" value="<?php p($l->t('Send!')); ?>" />
										<span class="icon-loading-small" style="float:right;"></span>
									</p>
								</div>
							</form>
						</div>
						<?php if ($comments !== null) : ?>
							<?php foreach ($comments as $comment) : ?>
								<div class="comment">
									<div class="comment-header">
										<?php
										print_unescaped('<span class="comment-date">' . date('d.m.Y H:i:s', strtotime($comment->getDt())) . '</span>');
										if ($isAnonymous || $hideNames) {
											p('Anonymous');
										} else {
											if ($userMgr->get($comment->getUserId()) !== null) {
												p($userMgr->get($comment->getUserId())->getDisplayName());
											} else {
												print_unescaped('<i>');
												p($comment->getUserId());
												print_unescaped('</i>');
											}
										}
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
