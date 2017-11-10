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
	if (!User::isLoggedIn()) {
		\OCP\Util::addStyle('polls', 'public');
	}

	\OCP\Util::addStyle('polls', 'app-navigation-simulation');
	\OCP\Util::addScript('polls', 'app');
	\OCP\Util::addScript('polls', 'vote');

	$userId = $_['userId'];
	/** @var \OCP\IUserManager $userMgr */
	$userMgr = $_['userMgr'];
	/** @var \OCP\IURLGenerator $urlGenerator */
	$urlGenerator = $_['urlGenerator'];
	/** @var \OCP\IAvatarManager $avaMgr */
	$avaMgr = $_['avatarManager'];
	/** @var \OCA\Polls\Db\Event $poll */
	$poll = $_['poll'];
	/** @var OCA\Polls\Db\Date[]|OCA\Polls\Db\Text[] $dates */
	$dates = $_['dates'];
	/** @var OCA\Polls\Db\Participation[]|OCA\Polls\Db\ParticipationText[] $votes */
	$votes = $_['votes'];
	/** @var \OCA\Polls\Db\Comment[] $comments */
	$comments = $_['comments'];
	$isAnonymous = $poll->getIsAnonymous() && $userId !== $poll->getOwner();
	$hideNames = $poll->getIsAnonymous() && $poll->getFullAnonymous();
	/** @var \OCA\Polls\Db\Notification $notification */
	$notification = $_['notification'];

	if ($poll->getExpire() === null) {
		$expired = false;
	} else {
		$expired = time() > strtotime($poll->getExpire());
	}

	if ($expired) {
		$statusClass = 'expired-vote';
	} else {
		$statusClass = 'open-vote';
		if (time() < strtotime($poll->getExpire())) {
			$statusClass .= ' endless';
		}
	}

 	if ($poll->getType() === 0) {
		$pollType = 'date-poll';
		$pollTypeClass = 'date-poll';
	} else if ($poll->getType() === 1) {
		$pollType = 'option-poll';
		$pollTypeClass = 'option-poll';
	}


	if (
		$poll->getDescription() !== null &&
		$poll->getDescription() !== ''
	) {
		$description = nl2br($poll->getDescription());
	} else {
		$description = $l->t('No description provided.');
	}

	// init array for counting 'yes'-votes for each date
	$total = array();
	for ($i = 0; $i < count($dates); $i++) {
		$total['yes'][$i] = 0;
		$total['no'][$i] = 0;
	}
	$userVoted = array();
	$pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', ['hash' => $poll->getHash()]);
?>

<div id="app-disabled">
	<div id="app-content" class="column <?php p($statusClass . ' ' . $pollTypeClass); ?>">
		<div id="controls" class="controls row">
			<div id="breadcrump" class="breadcrump row">
				<?php if (User::isLoggedIn()) : ?>
				<div class="crumb svg" data-dir="/">
					<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>">
						<img class="svg" src="<?php print_unescaped(\OCP\Template::image_path('core', 'places/home.svg')); ?>" alt="Home">
					</a>
				</div>
				<?php endif; ?>
				<div class="crumb svg last">
					<span><?php p($poll->getTitle()); ?></span>
				</div>

			</div>


			<a id="switchDetails" class="button details" title="Details" href="#">
				<span class="symbol icon-settings"></span>
				<?php if (count($comments)) : ?>
					<div id="comment-counter" class="badge"><?php p(count($comments)) ?></div>
				<?php else: ?>
					<div id="comment-counter" class="badge no-comments"><?php p(count($comments)) ?></div>
				<?php endif; ?>
			</a>
		</div>

		<div id="votings" class="main-container">
			<div class="wordwrap description"><span><?php p($description); ?></span>
			<?php
				if ($expired) {
					print_unescaped('<span class="' . $statusClass . '">' . $l->t('The poll expired on %s. Voting is disabled, but you can still comment.', array(date('d.m.Y H:i', strtotime($poll->getExpire())))) . '</span>');
				}?>
			</div>
			<div class="table">
					<ul class="row header" >
						<?php
						foreach ($dates as $el) {
							if ($poll->getType() === 0) {
								$timestamp = strtotime($el->getDt());
								print_unescaped('<li id="slot_' . $el->getId() . '" title="' . $el->getDt() . ' ' . date_default_timezone_get() . '" class="column vote time" data-timestamp="' . $timestamp . '"data-value-utc="' . $el->getDt() . '">');

								print_unescaped('	<div class="date-box column">');
								print_unescaped('		<div class="month">' . $l->t(date('M', $timestamp))  . '</div>');
								print_unescaped('		<div class="day">'   .       date('j', $timestamp)   . '</div>');
								print_unescaped('		<div class="dayow">' . $l->t(date('D', $timestamp))  . '</div>');
								print_unescaped('		<div class="time">'  .       date('G:i', $timestamp) . ' UTC</div>');
								print_unescaped('	</div>');
							} else {
								print_unescaped('<li id="slot_' . $el->getId() . '" title="' . preg_replace('/_\d+$/', '', $el->getText()) . '" class="column vote option">');
								print_unescaped('	<div class="date-box column">' . preg_replace('/_\d+$/', '', $el->getText()).'</div>');
							}
							print_unescaped('<div class="counter row">');
							print_unescaped('	<div class="yes row">');
							print_unescaped('		<div class="svg"></div>');
							print_unescaped('		<div id="counter_yes_voteid_' . $el->getId() . '" class ="result-cell yes" data-voteId="' . $el->getId() . '">0</div>');
							print_unescaped('	</div>');
							print_unescaped('	<div class="no row">');
							print_unescaped('		<div class="svg"></div>');
							print_unescaped('		<div id="counter_no_voteid_' . $el->getId() . '" class ="result-cell no" data-voteId="' . $el->getId() . '">0</div>');
							print_unescaped('	</div>');
							print_unescaped('</div>');
						}
						?>
						</li>
					</ul>
				<ul class="column table-body">
					<?php
					if ($votes !== null) {
						//group by user
						$others = array();
						$displayName = '';
						$avatarName = '';
						$activeClass = '';
						foreach ($votes as $vote) {
							if (!isset($others[$vote->getUserId()])) {
								$others[$vote->getUserId()] = array();
							}
							$others[$vote->getUserId()][]= $vote;
						}
						$userCnt = 0;
						foreach (array_keys($others) as $usr) {
							$userCnt++;
							if ($usr === $userId) {
								// if poll expired, just put current user among the others;
								// otherwise skip here to add current user as last row (to vote)
								if (!$expired) {
									$userVoted = $others[$usr];
									continue;
								}
							}
							if (
								$userMgr->get($usr) !== null &&
								!$isAnonymous &&
								!$hideNames
							) {
								$displayName = $userMgr->get($usr)->getDisplayName();
								$avatarName = $usr;
							} else {
								if ($isAnonymous || $hideNames) {
									$displayName = 'Anonymous';
									$avatarName = $userCnt;
								} else {
									$displayName = $usr;
									$avatarName = $usr;
								}
							}
							?>
							<li class="row user">
								<div class="first">
									<div class="user-cell row">
										<div class="avatar-cell">
											<div class="poll avatardiv" title="<?php p($avatarName)?>"></div>
										</div>
										<div class="name"><?php p($displayName) ?></div>
										</div>
									</div>
									<ul class="row">
							<?php
							// loop over dts
							$i_tot = 0;

							foreach ($dates as $dt) {
								if ($poll->getType() === 0) {
									$dateId = strtotime($dt->getDt());
									$pollId = 'voteid_' . $dt->getId();
								} else {
									$dateId = $dt->getText();
									$pollId = 'voteid_' . $dt->getId();
								}
								// look what user voted for this dts
								$class = 'column poll-cell no';
								foreach ($others[$usr] as $vote) {
									$voteVal = null;
									if ($poll->getType() === 0) {
										$voteVal = strtotime($vote->getDt());
									} else {
										$voteVal = $vote->getText();
									}
									if ($dateId === $voteVal) {
										if ($vote->getType() === 1) {
											$class = 'column poll-cell yes';
											$total['yes'][$i_tot]++;
										} else if ($vote->getType() === 0) {
											$class = 'column poll-cell no';
											$total['no'][$i_tot]++;
										} else if ($vote->getType() === 2) {
											$class = 'column poll-cell maybe';
										}
										break;
									}
								}
								print_unescaped('<li id="'. $pollId . '" class="' . $class . '"></li>');
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
						print_unescaped('		<div class="user-cell row">');
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
						print_unescaped('	<div id="toggle-cell" class="toggle-cell maybe" title="'. $toggleTooltip .'">');
						print_unescaped('		<div class="toggle"></div>');
						print_unescaped('	</div>');
						print_unescaped('</div>');
						print_unescaped('<ul class="row">');

						$i_tot = 0;
						foreach ($dates as $dt) {
							if ($poll->getType() === 0) {
								$dateId = strtotime($dt->getDt());
								$pollId = 'voteid_' . $dt->getId();
							} else {
								$dateId = $dt->getText();
								$pollId = 'voteid_' . $dt->getId();
							}
							// see if user already has data for this event
							$class = 'no';
							$activeClass = 'poll-cell active cl_click';
							if (isset($userVoted)) {
								foreach ($userVoted as $obj) {
									$voteVal = null;
									if($poll->getType() === 0) {
										$voteVal = strtotime($obj->getDt());
									} else {
										$voteVal = $obj->getText();
									}
									if ($voteVal === $dateId) {
										if ($obj->getType() === 1) {
											$class = 'column poll-cell yes';
											$total['yes'][$i_tot]++;
										} else if ($obj->getType() === 0) {
											$class = 'column poll-cell no';
											$total['no'][$i_tot]++;
										} else if($obj->getType() === 2) {
											$class = 'column poll-cell maybe';
										}
										break;
									}
								}
							}
							print_unescaped('<li id="' . $pollId . '" class="' . $class . ' ' . $activeClass . '" data-value="' . $dateId . '"></li>');

							$i_tot++;
						}
						print_unescaped('</ul>');
						print_unescaped('</li>');
					}
					?>
				</ul>
			</div>
			<div class="submit row">
				<div>
					<form class="finish_vote" name="finish_vote" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_vote')); ?>" method="POST">
						<input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="userId" value="<?php p($userId); ?>" />
						<input type="hidden" name="dates" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="types" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="receiveNotifications" />
						<input type="hidden" name="changed" />
						<input type="button" id="submit_finish_vote" class="button btn" value="<?php p($l->t('Vote!')); ?>" />
					</form>
				</div>
			<?php if (User::isLoggedIn()) : ?>
				<div class="notification">
					<input type="checkbox" id="check_notif" class="checkbox" <?php if ($notification !== null) print_unescaped(' checked'); ?> />
					<label for="check_notif"><?php p($l->t('Receive notification email on activity')); ?></label>
				</div>
			<?php endif; ?>
			</div>
		</div>

		<div id="app-sidebar" class="detailsView scroll-container">
			<a id="closeDetails" class="close icon-close" href="#" alt="<?php $l->t('Close');?>"></a>
			<div class="table">
				<div class="row">
					<div id="app-navigation-simulation">
						<ul class="with-icons">
							<li>
								<a id="id_copy_<?php p($poll->getId()); ?>" class="icon-clippy svg copy-link" data-clipboard-text="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>" href="#">
									<?php p($l->t('Copy Link')); ?>
								</a>
							</li>

					<?php if ($poll->getOwner() === $userId) : ?>
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
