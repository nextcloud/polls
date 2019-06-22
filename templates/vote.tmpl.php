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

	use OCP\User; //To do: replace according to API
	use OCP\Util;
	use OCP\Template;

	Util::addStyle('polls', 'main');
	Util::addStyle('polls', 'flex');
	Util::addStyle('polls', 'vote');
	Util::addStyle('polls', 'sidebar');
	if (!User::isLoggedIn()) {
		Util::addStyle('polls', 'public');
	}

	Util::addScript('polls', 'app');
	Util::addScript('polls', 'vote');

	$userId = $_['userId'];
	/** @var \OCP\IUserManager $userMgr */
	$userMgr = $_['userMgr'];
	/** @var \OCP\IURLGenerator $urlGenerator */
	$urlGenerator = $_['urlGenerator'];
	/** @var \OCP\IAvatarManager $avaMgr */
	$avaMgr = $_['avatarManager'];
	/** @var \OCA\Polls\Db\Event $poll */
	$poll = $_['poll'];
	/** @var OCA\Polls\Db\Option[] $options */
	$options = $_['options'];
	/** @var OCA\Polls\Db\Vote[] $votes */
	$votes = $_['votes'];
	/** @var \OCA\Polls\Db\Comment[] $comments */
	$comments = $_['comments'];
	/** @var \OCA\Polls\Db\Notification $notification */
	$notification = $_['notification'];

	$isAnonymous = $poll->getIsAnonymous() && $userId !== $poll->getOwner();
	$hideNames = $poll->getIsAnonymous() && $poll->getFullAnonymous();
	if ($poll->getAllowMaybe()) {
		$maybe = 'maybeallowed';
	} else {
		$maybe = 'maybedisallowed';
	}
	$access = $poll->getAccess();
	$updatedPoll = false;
	$dataUnvoted = '';

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
		$description = str_replace(array('\r\n', '\r', '\n'), '<br/>', htmlspecialchars($poll->getDescription()));
	} else {
		$description = $l->t('No description provided.');
	}

	// init array for counting 'yes'-votes for each date
	$total = array();
	for ($i = 0; $i < count($votes); $i++) {
		$total['yes'][$i] = 0;
		$total['no'][$i] = 0;
		$total['maybe'][$i] = 0;
	}
	$userVoted = array();
	$pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', ['hash' => $poll->getHash()]);
?>

	<div id="app-content" class="<?php p($statusClass . ' ' . $pollTypeClass . ' ' . $maybe); ?>">
		<div id="controls" class="controls">

			<div class="breadcrumb">

	<?php if (User::isLoggedIn()) : ?>
				<div class="crumb svg crumbhome">
					<a class="icon-home" href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>"> Home </a>
				</div>
	<?php endif; ?>

				<div class="crumb svg last">
					<span><?php p($poll->getTitle()); ?></span>
				</div>

			</div>


			<a id="switchDetails" class="button has-tooltip-bottom details" title="Details" href="#">
				<span class="symbol icon-settings"></span>
				<?php if (count($comments)) : ?>
					<div id="comment-counter" class="badge icon-comment-yes"><?php p(count($comments)) ?></div>
				<?php else: ?>
					<div id="comment-counter" class="badge no-comments icon-comment-no"><?php p(count($comments)) ?></div>
				<?php endif; ?>
			</a>
		</div>

		<div id="votings" class="main-container">
			<div class="wordwrap description">
				<span>
					<?php print_unescaped($description); ?>
				</span>
					<?php if ($expired) { print_unescaped('<span class="' . $statusClass . '">' . $l->t('The poll expired on %s. Voting is disabled, but you can still comment.', array(date('d.m.Y H:i', strtotime($poll->getExpire())))) . '</span>'); }?>
			</div>

			<div class="table">
				<ul class="flex-row header" >
					<?php
					foreach ($options as $optionElement) {
						if ($poll->getType() === 0) {
							$timestamp = strtotime($optionElement->getPollOptionText());
							print_unescaped('<li id="slot_' . $optionElement->getId() . '" title="' . $optionElement->getPollOptionText() . ' ' . date_default_timezone_get() . '" class="flex-column vote time has-tooltip" data-timestamp="' . $timestamp . '"data-value-utc="' . $optionElement->getPollOptionText() . '">');
							print_unescaped('	<div class="date-box flex-column">');
							print_unescaped('		<div class="month">' . $l->t(date('M', $timestamp)) . '</div>');
							print_unescaped('		<div class="day">' . date('j', $timestamp) . '</div>');
							print_unescaped('		<div class="dayow">' . $l->t(date('D', $timestamp)) . '</div>');
							print_unescaped('		<div class="time">' . date('G:i', $timestamp) . ' UTC</div>');
							print_unescaped('	</div>');
						} else {
							print_unescaped('<li id="slot_' . $optionElement->getId() . '" title="' . $optionElement->getPollOptionText() . '" class="flex-column vote option">');
							print_unescaped('	<div class="date-box flex-column">' . $optionElement->getPollOptionText() . '</div>');
						}
						print_unescaped('<div class="counter flex-row">');
						print_unescaped('	<div class="yes flex-row">');
						print_unescaped('		<div class="icon-yes"></div>');
						print_unescaped('		<div id="counter_yes_voteid_' . $optionElement->getId() . '" class ="result-cell yes" data-voteId="' . $optionElement->getId() . '">0</div>');
						print_unescaped('	</div>');
						print_unescaped('	<div class="no flex-row">');
						print_unescaped('		<div class="icon-no"></div>');
						print_unescaped('		<div id="counter_no_voteid_' . $optionElement->getId() . '" class ="result-cell no" data-voteId="' . $optionElement->getId() . '">0</div>');
						print_unescaped('	</div>');
						print_unescaped('</div>');
					}
					?>
					</li>
				</ul>

				<ul class="flex-column table-body">
					<?php

					if ($votes !== null) {
						//group by user
						$others = array();
						$displayName = '';
						$avatarName = '';
						foreach ($votes as $vote) {
							if (!isset($others[$vote->getUserId()])) {
								$others[$vote->getUserId()] = array();
							}
							$others[$vote->getUserId()][] = $vote;
						}
						$userCnt = 0;
						foreach (array_keys($others) as $usr) {
							$userCnt++;
							if ($usr === $userId) {
								// if poll expired, just put current user among the others;
								// otherwise skip here to add current user as last flex-row (to vote)
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
								$displayName = \OC_User::getDisplayName($usr);
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
							<li class="flex-row user">
								<div class="first">
									<div class="user-cell flex-row">
										<div class="avatar has-tooltip" title="<?php p($avatarName)?>"></div>
										<div class="name"><?php p($displayName) ?></div>
										</div>
									</div>
									<ul class="flex-row">
							<?php
							// loop over dts
							$i_tot = 0;

							foreach ($options as $optionElement) {
								// look what user voted for this dts
								foreach ($others[$usr] as $vote) {
									if ($optionElement->getPollOptionText() === $vote->getVoteOptionText()) {
										$class = $vote->getVoteAnswer() . ' icon-'.$vote->getVoteAnswer();
										break;
									}
									$class = 'no icon-no';
								}
								print_unescaped('<li id="voteid_' . $optionElement->getId() . '" class="flex-column poll-cell ' . $class . '"></li>');
								$i_tot++;
							}

							print_unescaped('</ul>');
							print_unescaped('</li>');
						}
					}

					$toggleTooltip = $l->t('Switch all options at once');
					if (!$expired) {
						print_unescaped('<li class="flex-row user current-user">');
						print_unescaped('	<div class="flex-row first">');
						print_unescaped('		<div class="user-cell flex-row">');
						if (User::isLoggedIn()) {
							print_unescaped('		<div class="avatar has-tooltip" title="' . ($userId) . '"></div>');
							print_unescaped('		<div class="name">');
							p(\OC_User::getDisplayName($userId));
						} else {
							print_unescaped('		<div class="avatar has-tooltip" title="?"></div>');
							print_unescaped('		<div id="id_ac_detected" class="name external current-user"><input type="text" name="user_name" id="user_name" placeholder="' . $l->t('Your name here') . '" />');
						}
						print_unescaped('		</div>');
						print_unescaped('	</div>');
						?>

						<div class="actions">
							<div class="icon-more popupmenu" value="1" id="expand_1"></div>
							<div class="popovermenu bubble menu hidden" id="expanddiv_1">
								<ul>
									<li>
										<a id="toggle-yes" class="toggle toggle-yes menuitem alt-tooltip copy-link action permanent" href="#">
											<span class="icon-yes"></span>
											<span><?php p($l->t('Say yes to all')); ?></span>
										</a>
									</li>
									<li>
										<a id="toggle-no" class="toggle toggle-no menuitem alt-tooltip copy-link action permanent" href="#">
											<span class="icon-no"></span>
											<span><?php p($l->t('Reset all (say no)')); ?></span>
										</a>
									</li>
									<?php if ($maybe === 'maybeallowed') :?>
									<li>
										<a id="toggle-maybe" class="toggle toggle-maybe menuitem alt-tooltip copy-link action permanent" href="#">
											<span class="icon-maybe"></span>
											<span><?php p($l->t('Say maybe to all')); ?></span>
										</a>
									</li>
									<?php endif; ?>
								</ul>
							</div>
						</div>

						<?php
							if ($maybe === 'maybeallowed') {
								print_unescaped('	<div id="toggle-cell" class="toggle-cell has-tooltip maybe" title="' . $toggleTooltip . '">');
							} else {
								print_unescaped('	<div id="toggle-cell" class="toggle-cell has-tooltip yes" title="' . $toggleTooltip . '">');
							}
							print_unescaped('		<div class="toggle"></div>');
							print_unescaped('	</div>');
							print_unescaped('</div>');
							print_unescaped('<div class="flex-row">');

							$i_tot = 0;
							foreach ($options as $optionElement) {
								// see if user already has data for this event
								$class = 'no icon-no';
								$dataUnvoted = '';
								if (isset($userVoted)) {
									foreach ($userVoted as $vote) {
										if ($optionElement->getPollOptionText() === $vote->getVoteOptionText()) {
											$class = $vote->getVoteAnswer() . ' icon-' . $vote->getVoteAnswer();
											break;
										} else {
											$class = 'unvoted';
										}
									}
								}

								if ($class === 'unvoted') {
									$dataUnvoted = $l->t('New');
									$updatedPoll = true;
								}

								print_unescaped('<div class="poll-cell">');
	 							print_unescaped('    <div id="voteid_' . $optionElement->getId() . '" class="flex-column poll-cell active  ' . $class . '" data-value="' . $optionElement->getPollOptionText() . '" data-unvoted="' . $dataUnvoted . '"></div>');
	 							print_unescaped('</div>');
								$i_tot++;
							}
							print_unescaped('</div>');
							print_unescaped('</li>');
						}
					?>
				</ul>
			</div>

	<?php if ($updatedPoll) : ?>
			<div class="updated-poll alert">
				<p> <?php p($l->t('This poll was updated since your last visit. Please check your votes.')); ?></p>
			</div>
	<?php endif; ?>

			<div class="submitPoll flex-row">
				<div>
					<form class="finish_vote" name="finish_vote" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_vote')); ?>" method="POST">
						<input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="userId" value="<?php p($userId); ?>" />
						<input type="hidden" name="options" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="answers" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="receiveNotifications" />
						<input type="hidden" name="changed" />
						<input type="button" id="submit_finish_vote" class="button btn primary" value="<?php p($l->t('Vote!')); ?>" />
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

	</div>

	<div id="app-sidebar" class="detailsView scroll-container disappear">
		<div class="close flex-row">
			<a id="closeDetails" class="close icon-close has-tooltip-bottom" title="<?php p($l->t('Close details')); ?>" href="#" alt="<?php $l->t('Close'); ?>"></a>
		</div>

		<div class="header flex-row">
			<div class="pollInformation flex-column">
				<div class="authorRow user-cell flex-row">
					<div class="description leftLabel"><?php p($l->t('Owner')); ?></div>
					<div class="avatar has-tooltip-bottom" title="<?php p($poll->getOwner())?>"></div>
					<div class="author"><?php p(\OC_User::getDisplayName($poll->getOwner())); ?></div>
				</div>

				<div class="cloud">
					<?php
					if ($expired) {
						print_unescaped('<span class="expired">' . $l->t('Expired') . '</span>');
					} else {
						if ($poll->getExpire() !== null) {
							print_unescaped('<span class="open">' . $l->t('Expires on %s', array(date('d.m.Y', strtotime($poll->getExpire())))) . '</span>');
						} else {
							print_unescaped('<span class="open">' . $l->t('Expires never') . '</span>');
						}
					}

					if ($access === 'public' || $access === 'hidden' || $access === 'registered') {
						print_unescaped('<span class="information">' . $access . '</span>');
					} else {
						print_unescaped('<span class="information">' . $l->t('Invitation access') . '</span>');
					}
					if ($isAnonymous) {
						print_unescaped('<span class="information">' . $l->t('Anonymous poll') . '</span>');
						if ($hideNames) {
							print_unescaped('<span class="information">' . $l->t('Usernames hidden to Owner') . '</span>');
						} else {
							print_unescaped('<span class="information">' . $l->t('Usernames visible to Owner') . '</span>');
						}
					}
					?>
				</div>

			</div>
			<div class="pollActions flex-column">
				<ul class="with-icons">
					<li>
						<a id="id_copy_<?php p($poll->getId()); ?>" class="icon-clippy has-tooltip-bottom svg copy-link" data-clipboard-text="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>" href="#">
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

	<?php if ($expired) : ?>
		<div id="expired_info">
			<h2><?php p($l->t('Poll expired')); ?></h2>
			<p>
				<?php p($l->t('The poll expired on %s. Voting is disabled, but you can still comment.', array(date('d.m.Y H:i', strtotime($poll->getExpire()))))); ?>
			</p>
		</div>
	<?php endif; ?>

		<ul class="tabHeaders">
			<li class="tabHeader selected" data-tabid="commentsTabView" data-tabindex="0">
				<a href="#"><?php p($l->t('Comments')); ?></a>
			</li>
		</ul>

		<div class="tabsContainer">
			<div id="commentsTabView" class="tab commentsTabView">
				<div class="newCommentRow comment new-comment">

	<?php if (User::isLoggedIn()) : ?>
					<div class="authorRow user-cell flex-row">
						<div class="avatar has-tooltip" title="<?php p($userId)?>"></div>
						<div class="author"><?php p(\OC_User::getDisplayName($userId)) ?></div>
					</div>
	<?php else: ?>
					<a href="<?php p($urlGenerator->linkToRouteAbsolute('core.login.showLoginForm')); ?>"><?php p($l->t('Login or ...')); ?></a>
					<div class="authorRow user-cell flex-row">
						<div class="avatar has-tooltip" title="?"></div>
						<div id="id_ac_detected" class="author  flex-column external">
							<input type="text" name="user_name_comm" id="user_name_comm" placeholder="<?php p($l->t('Your name here')); ?>" />
						</div>
					</div>
	<?php endif; ?>

					<form class="newCommentForm flex-row" name="send_comment" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_comment')); ?>" method="POST">
						<input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
						<input type="hidden" name="userId" value="<?php p($userId); ?>" />
						<div id="commentBox" name="commentBox" class="message" data-placeholder="<?php p($l->t('New comment …'))?>" contenteditable="true"></div>
						<input id="submit_send_comment" class="submitComment icon-confirm" value="" type="submit">
						<span class="icon-loading-small" style="float:right;"></span>
					</form>
				</div>

				<ul class="comments flex-column">

				<?php if ($comments == null) : ?>
					<li id="no-comments" class="emptycontent">
				<?php else : ?>
					<li id="no-comments" class="emptycontent hidden">
				<?php endif; ?>

						<div class="icon-comment"></div>
						<p><?php p($l->t('No comments yet. Be the first.')); ?></p>
					</li>

				<?php foreach ($comments as $comment) : ?>

					<?php
						if ($comment->getUserId() === $userId) {
							// Comment is from current user
							// -> display user
							$avatarName = $userId;
							$displayName = \OC_User::getDisplayName($userId);

						} else if (!$isAnonymous && !$hideNames) {
							// comment is from another user,
							// poll is not anoymous (for current user)
							// users are not hidden
							// -> display user
							$avatarName = $comment->getUserId();
							$displayName = \OC_User::getDisplayName($comment->getUserId());
						} else {
							// in all other cases
							// -> make user anonymous
							// poll is anonymous and current user is not owner
							// or names are hidden
							$displayName = 'Anonymous';
							$avatarName = $displayName;
						}
					?>

					<li id="comment_<?php p($comment->getId()); ?>" class="comment flex-column">
						<div class="authorRow user-cell flex-row">
							<div class="avatar has-tooltip" title="<?php p($avatarName)?>"></div>
							<div class="author"><?php p($displayName) ?></div>
							<div class="date has-tooltip live-relative-timestamp datespan" data-timestamp="<?php p(strtotime($comment->getDt()) * 1000); ?>" title="<?php p($comment->getDt()) ?>"><?php p(Template::relative_modified_date(strtotime($comment->getDt()))) ?></div>
						</div>
						<div class="message wordwrap comment-content"><?php p($comment->getComment()); ?></div>
					</li>
				<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>

	<form id="form_delete_poll" name="form_delete_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.delete_poll')); ?>" method="POST"></form>
