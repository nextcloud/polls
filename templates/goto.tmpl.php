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
	\OCP\Util::addStyle('polls', 'app-navigation-simulation');
	\OCP\Util::addScript('polls', 'app');
	\OCP\Util::addScript('polls', 'vote');
	
	$userId = $_['userId'];
	$userMgr = $_['userMgr'];
	$urlGenerator = $_['urlGenerator'];
	$avaMgr = $_['avatarManager'];

	$poll = $_['poll'];
	$dates = $_['dates'];
	$votes = $_['votes'];
	$comments = $_['comments'];
	$isAnonymous = $poll->getIsAnonymous() && $userId != $poll->getOwner();
	$hideNames = $poll->getIsAnonymous() && $poll->getFullAnonymous();
	$notification = $_['notification'];

	if ($poll->getExpire() === null) {
		$expired = false;
	} else {
		$expired = time() > strtotime($poll->getExpire());
	}
	
	if ($expired) {
		$statusClass = "expired-vote";
	} else {
		$statusClass = "open-vote";
		if (time() < strtotime($poll->getExpire())) {
			$statusClass = $statusClass . ' endless';
		}
	}

 	if ($poll->getType() == '0') {
		$pollType = 'date-poll';
		$pollTypeClass = 'date-poll';
	} else if ($poll->getType() == '1') {
		$pollType = 'option-poll';
		$pollTypeClass = 'option-poll';
	}

	if (   $poll->getDescription() != null 
		&& $poll->getDescription() != ''
	) {
		$description = nl2br($poll->getDescription());
	} else {
		$description = $l->t('No description provided.');
	}

	// init array for counting 'yes'-votes for each date
	$total = array();
	for ($i = 0 ; $i < count($dates) ; $i++) {
		$total['yes'][$i] = 0;
		$total['no'][$i] = 0;
	}

	$userVoted = array();
	$pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', ['hash' => $poll->getHash()]);
?>

<div id="app">
	<div id="app-content" class="<?php p($statusClass . ' ' . $pollTypeClass); ?>">
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
			
			
			<a id="switchDetails" class="button details" title="Details" href="#">
				<span class="symbol icon-settings"></span>
				<?php if (count($comments)) : ?>
					<div id="comment-counter" class="badge"><?php p(count($comments)) ?></div>
				<?php else: ?>
					<div class="badge no-comments"><?php p(count($comments)) ?></div>
				<?php endif; ?>
			</a>			
		</div>
		
		<div id="votings" class="main-container">
			<div class="wordwrap description"><?php p($description); ?></div>
			<div class="table">
				<div id="header-row" class="row header">
					<div class="column first"></div>
					<ul id="options" class="row" >
						<?php
						foreach ($dates as $el) {
							if ($poll->getType() == '0') {
								$datavalue = strtotime($el->getDt());
								print_unescaped('<li id="slot-' . $datavalue . '" title="' . $el->getDt() . ' ' . date_default_timezone_get() . '" class="column time-slot" data-value="' . $datavalue . '" value="' . $el->getDt() . '">');
								
								print_unescaped('<div class="month">' . $l->t(date('M', $datavalue))  . '</div>');
								print_unescaped('<div class="day">'   .       date('j', $datavalue)   . '</div>');
								print_unescaped('<div class="dayow">' . $l->t(date('D', $datavalue))  . '</div>');
								print_unescaped('<div class="time">'  .       date('G:i', $datavalue) . ' UTC</div>');
								print_unescaped('</li>');
							} else {
								print_unescaped('<li title="' . preg_replace('/_\d+$/', '', $el->getText()) . '" class="column vote-option">' . preg_replace('/_\d+$/', '', $el->getText()) . '</li>');
							}
						}
						?>
					</ul>
				</div>
				<ul class="column table-body">
					<?php
					if ($votes != null) {
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
							if ($usr == $userId) {
								// if poll expired, just put current user among the others;
								// otherwise skip here to add current user as last row (to vote)
								if (!$expired) {
									$userVoted = $others[$usr];
									continue;
								}
							}
							print_unescaped('<li class="row user">');
							print_unescaped('	<div class="first">');
							print_unescaped('		<div class="wrapper user-cell">');
							print_unescaped('		<div class="avatar-cell">');
							if (	$userMgr->get($usr) != null 
								&& !$isAnonymous && !$hideNames
							) {
								print_unescaped('		<div class="poll avatardiv" title="'.($usr).'"></div>');
								print_unescaped('	</div>');
								print_unescaped('	<div class="name">');
								p($userMgr->get($usr)->getDisplayName());
							} else {
								if ($isAnonymous || $hideNames) {
									print_unescaped('	<div class="poll avatardiv" title="'.($userCnt).'"></div>');
									print_unescaped('</div>');
									print_unescaped('<div class="name">');
									p('Anonymous');
								} else {
									print_unescaped('	<div class="poll avatardiv" title="'.($usr).'"></div>');
									print_unescaped('</div>');
									print_unescaped('<div class="name">');
									p($usr);
								}
							}
							print_unescaped('		 </div>');
							print_unescaped('</div>');
							print_unescaped('	</div>');

							// loop over dts
							$i_tot = 0;
							print_unescaped('<ul class="wrapper row">');
							
							foreach ($dates as $dt) {
								if ($poll->getType() == '0') {
									$dateId = strtotime($dt->getDt());
									$pollId = "pollid_" . $dt->getId();
								} else {
									$dateId = $dt->getText();
									$pollId = "pollid_" . $dt->getId();
								}
								// look what user voted for this dts
								$found = false;
								foreach ($others[$usr] as $vote) {
									$voteVal = null;
									if ($poll->getType() == '0') {
										$voteVal = strtotime($vote->getDt());
									} else {
										$voteVal = $vote->getText();
									}
									if ($dateId == $voteVal) {
										if ($vote->getType() == '1') {
											$cl = 'yes';
											$total['yes'][$i_tot]++;
										} else if ($vote->getType() == '0') {
											$cl = 'no';
											$total['no'][$i_tot]++;
										} else if ($vote->getType() == '2') {
											$cl = 'maybe';
										} else {
											$cl = 'unvoted';
										}
										$found = true;
										break;
									}
								}
								if (!$found) {
									$cl = 'poll-cell unvoted';
								}
								print_unescaped('<li class="column poll-cell ' . $cl . '"><div class="vote"></div></li>');
								$i_tot++;
							}
							
							print_unescaped('</ul>');
							print_unescaped('</li>');
						}
					}
					$totalYesOthers = array_merge(array(), $total['yes']);
					$totalNoOthers = array_merge(array(), $total['no']);
					$toggleTooltip = $l->t('Switch all options at once');
					if (!$expired) {
						print_unescaped('<li class="row user current-user">');
						print_unescaped('	<div class="row first">');
						print_unescaped('		<div class="wrapper user-cell">');
						print_unescaped('			<div class="avatar-cell">');
						if (User::isLoggedIn()) {
							print_unescaped('			<div class="poll avatardiv" title="'.($userId).'"></div>');
							print_unescaped('		</div>');
							print_unescaped('		<div class="name">');
							p($userMgr->get($userId)->getDisplayName());
						} else {
							print_unescaped('			<div class="poll avatardiv" title="?"></div>');
							print_unescaped('		</div>');
							print_unescaped('		<div id="id_ac_detected" class="external current-user"><input type="text" name="user_name" id="user_name" placeholder="' . $l->t('Your name here') . '" />');
						}
						print_unescaped('		</div>');
						print_unescaped('	</div>');
						print_unescaped('	<div id="toggle-all" class="toggle-all maybe" title="'. $toggleTooltip .'">');
						print_unescaped('		<div class="toggle"></div>');
						print_unescaped('	</div>');
						print_unescaped('</div>');
						print_unescaped('<ul class="wrapper row">');

						$i_tot = 0;
						foreach ($dates as $dt) {
							if ($poll->getType() == '0') {
								$dateId = strtotime($dt->getDt());
								$pollId = "pollid_" . $dt->getId();
							} else {
								$dateId = $dt->getText();
								$pollId = "pollid_" . $dt->getId();
							}
							// see if user already has data for this event
							$cl = 'unvoted';
							if (isset($userVoted)) {
								foreach ($userVoted as $obj) {
									$voteVal = null;
									if($poll->getType() == '0') {
										$voteVal = strtotime($obj->getDt());
									} else {
										$voteVal = $obj->getText();
									}
									if ($voteVal == $dateId) {
										if ($obj->getType() == '1') {
											$cl = 'yes';
											$total['yes'][$i_tot]++;
										} else if ($obj->getType() == '0') {
											$cl = 'no';
											$total['no'][$i_tot]++;
										} else if($obj->getType() == '2') {
											$cl = 'maybe';
										}
										break;
									}
								}
							}
							print_unescaped('<li id="' . $pollId . '" class="column poll-cell active cl_click ' . $cl . '" data-value="' . $dateId . '" title="' . $cl . '"><div class="vote"></div></li>');

							$i_tot++;
						}
						print_unescaped('</ul>');
						print_unescaped('</li>');
					}
					?>
				</ul>
					<?php
						$diffArray = $total['yes'];
						for($i = 0 ; $i < count($diffArray) ; $i++) {
							$diffArray[$i] = ($total['yes'][$i] - $total['no'][$i]);
						}
						$maxVotes = max($diffArray);
					?>
					<div class="row total">
						<div class="wrapper row first"><?php p($l->t('Total')); ?></div>
						<ul class="wrapper row">
					<?php for ($i = 0 ; $i < count($dates) ; $i++) : ?>
							<li class="column total">
								<?php
								$classSuffix = "pollid_" . $dates[$i]->getId();
								if (isset($total['yes'][$i])) {
									$val = $total['yes'][$i];
								} else {
									$val = 0;
								}
								?>
								<div id="id_y_<?php p($classSuffix); ?>" class="result-cell column yes" data-value=<?php p(isset($totalYesOthers[$i]) ? $totalYesOthers[$i] : '0'); ?>>
									<div>
										<?php p($val); ?>
									</div>
								</div>
								<div id="id_n_<?php p($classSuffix); ?>" class="result-cell column no" data-value=<?php p(isset($totalNoOthers[$i]) ? $totalNoOthers[$i] : '0'); ?>>
									<div>
										<?php p(isset($total['no'][$i]) ? $total['no'][$i] : '0'); ?>
									</div>
								</div>
							</li>
					<?php endfor; ?>
						</ul>
					</div>
					<div class="row best">
						<div class="wrapper row first"><?php p($l->t('Best option'));?> </div>
						<ul class="wrapper row">
						<?php
						for ($i = 0; $i < count($dates); $i++) {
							$check = '';
							$bestOptionText = '';
							if ($total['yes'][$i] - $total['no'][$i] == $maxVotes) {
								$check = 'icon-checkmark';
							}
							print_unescaped('<li class="column win_row ' . $check . '" id="id_total_' . $i . '"><span>');
							print_unescaped('</span></li>');
						}
						?>
						</ul>
					</div>
			</div>
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
				<input type="checkbox" id="check_notif" class="checkbox" <?php if ($notification != null) print_unescaped(' checked'); ?> />
				<label for="check_notif"><?php p($l->t('Receive notification email on activity')); ?></label>
			</span>
		<?php endif; ?>
		</div>
					
		<div id="app-sidebar" class="detailsView scroll-container">
			<a id="closeDetails" class="close icon-close" href="#" alt="<?php $l->t('Close');?>"></a>


			<div class="table ">
				<div class="row">
					<div id="app-navigation-simulation">
						<ul class="with-icons">
							<li>
								<a id="id_copy_<?php p($poll->getId()); ?>" class="icon-clippy svg copy-link" data-clipboard-text="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>" href="#">
									<?php p($l->t('Copy Link')); ?>
								</a>
							</li>
						
					<?php if ($poll->getOwner() == $userId) : ?>
							<li class="">
								<a id="id_del_<?php p($poll->getId()); ?>" class="icon-delete svg delete-poll"  data-value="<?php p($poll->getTitle()); ?>" href="#">
									<?php p($l->t('Delete poll')); ?>
								</a>
							</li>
							<li>
								<a id="id_edit_<?php p($poll->getId()); ?>" class="icon-rename svg" href="<?php p($urlGenerator->linkToRoute('polls.page.edit_poll', ['hash' => $poll->getHash()])); ?>">
									<?php p($l->t('Edit Poll')); ?>
								</a>
							</li>
					<?php endif; ?>
						</ul>
					</div>
				<div class="column">
					<div class="poll-info owner">
						<div class="wrapper user-cell">
							<div class="avatar-cell">
								<div class="poll avatardiv" title="<?php p($poll->getOwner());?>"></div>
							</div>
							<div class="name"><?php p($poll->getOwner());?></div>
						</div>
					</div>
					<div class="poll-info expiry">
						
						<?php 
							p($l->t('Poll expires: '));
							if ($poll->getExpire() != null) {
								p(OCP\relative_modified_date(strtotime($poll->getExpire())));
							} else {
								p($l->t('Never'));
							}

						?>
					</div>
				</div>
				</div>
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
							<?php print_unescaped('<div id="id_ac_detected" class="column external current-user"><input type="text" name="user_name_comm" id="user_name_comm" placeholder="' . $l->t('Your name here') . '" /></div>'); ?>
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
				<?php if ($comments != null) : ?>
					<?php foreach ($comments as $comment) : ?>
						<div class="comment">
							<div class="comment-header">
								<?php
								print_unescaped('<span class="comment-date">' . date('d.m.Y H:i:s', strtotime($comment->getDt())) . '</span>');
								if ($isAnonymous || $hideNames) {
									p('Anonymous');
								} else {
									if ($userMgr->get($comment->getUserId()) != null) {
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
