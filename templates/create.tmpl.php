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
	use OCP\Util;
	use OCP\Template;

	Util::addStyle('polls', 'createpoll');
	Util::addStyle('polls', 'vendor/jquery.datetimepicker.min');
	Util::addScript('polls', 'create_edit');
	Util::addScript('polls', 'vendor/jquery.datetimepicker.full.min');

	$userId = $_['userId'];
	/** @var \OCP\IUserManager $userMgr */
	$userMgr = $_['userMgr'];
	/** @var \OCP\IURLGenerator $urlGenerator */
	$urlGenerator = $_['urlGenerator'];
	$isUpdate = isset($_['poll']) && $_['poll'] !== null;
	$isAnonymous = false;
	$hideNames = false;

	if ($isUpdate) {
		/** @var OCA\Polls\Db\Event $poll */
		$poll = $_['poll'];
		$isAnonymous = $poll->getIsAnonymous();
		$hideNames = $isAnonymous && $poll->getFullAnonymous();
		/** @var OCA\Polls\Db\Date[]|OCA\Polls\Db\Text[] $dates */
		$dates = $_['dates'];
		$chosen = '[';
		foreach ($dates as $d) {
			if ($poll->getType() === 0) {
				$chosen .= strtotime($d->getDt());
			} else {
				$chosen .= '"' . $d->getText() . '"';
			}
			$chosen .= ',';
		}
		$chosen = trim($chosen, ',');
		$chosen .= ']';
		$title = $poll->getTitle();
		$desc = $poll->getDescription();
		if ($poll->getExpire() !== null) {
			$expireTs = strtotime($poll->getExpire());
			$expireStr = date('d.m.Y', $expireTs);
		}
		$access = $poll->getAccess();
		$accessTypes = $access;
		if (
			$access !== 'registered'
			&& $access !== 'hidden' && $access !== 'public'
		) {
			$access = 'select';
		}
	}
?>

	<div id="app-content">

			<div id="controls">
				<div id="breadcrump">
					<div class="crumb svg" data-dir="/">
						<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>">
							<img class="svg" src="<?php print_unescaped(Template::image_path('core', 'places/home.svg')); ?>" alt="Home">
						</a>
					</div>
					<div class="crumb svg last">
						<span>
						<?php if ($isUpdate): ?>
							<?php p($l->t('Edit poll') . ' ' . $poll->getTitle()); ?>
						<?php else: ?>
						  <?php p($l->t('Create new poll')); ?>
						<?php endif; ?>
						</span>
					</div>
				</div>
			</div>

		<?php if ($isUpdate): ?>
			<form name="finish_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.update_poll')); ?>" method="POST">
				<input type="hidden" name="pollId" value="<?php p($poll->getId()); ?>" />
		<?php else: ?>
			<form name="finish_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.insert_poll')); ?>" method="POST">
		<?php endif; ?>
				<input type="hidden" name="chosenDates" id="chosenDates" value="<?php if (isset($chosen)) p($chosen); ?>" />
				<input type="hidden" name="expireTs" id="expireTs" value="<?php if (isset($expireTs)) p($expireTs); ?>" />
				<input type="hidden" name="userId" id="userId" value="<?php p($userId); ?>" />

				<header class="row">
				</header>

				<div class="new_poll row">
					<div class="col-50">
						<label for="pollTitle" class="input_title"><?php p($l->t('Title')); ?></label>
						<input type="text" class="input_field" id="pollTitle" name="pollTitle" value="<?php if (isset($title)) p($title); ?>" />
						<label for="pollDesc" class="input_title"><?php p($l->t('Description')); ?></label>
						<textarea class="input_field" id="pollDesc" name="pollDesc"><?php if (isset($desc)) p($desc); ?></textarea>

						<label class="input_title"><?php p($l->t('Access')); ?></label>

						<input type="radio" name="accessType" id="private" value="registered" class="radio" <?php if (!$isUpdate || $access === 'registered') print_unescaped('checked'); ?> />
						<label for="private"><?php p($l->t('Registered users only')); ?></label>

						<input type="radio" name="accessType" id="hidden" value="hidden" class="radio" <?php if ($isUpdate && $access === 'hidden') print_unescaped('checked'); ?> />
						<label for="hidden"><?php p($l->t('hidden')); ?></label>

						<input type="radio" name="accessType" id="public" value="public" class="radio" <?php if ($isUpdate && $access === 'public') print_unescaped('checked'); ?> />
						<label for="public"><?php p($l->t('Public access')); ?></label>

						<input type="radio" name="accessType" id="select" value="select" class="radio" <?php if ($isUpdate && $access === 'select') print_unescaped('checked'); ?>>
						<label for="select"><?php p($l->t('Select')); ?></label>
						<span id="id_label_select">...</span>

						<div id="selected_access" class="row user-group-list">
							<ul id="selected-search-list-id">
							</ul>
						</div>
						<div id="access_rights" class="row user-group-list">
							<div class="col-50">
								<input type="text" class="live-search-box" id="user-group-search-box" placeholder="<?php p($l->t('User/Group search')); ?>" />
								<ul class="live-search-list" id="live-search-list-id">
								</ul>
							</div>
						</div>

						<input type="hidden" name="accessValues" id="accessValues" value="<?php if ($isUpdate && $access === 'select') p($accessTypes) ?>" />

						<input id="isAnonymous" name="isAnonymous" type="checkbox" class="checkbox" <?php $isAnonymous ? print_unescaped('value="true" checked') : print_unescaped('value="false"'); ?> />
						<label for="isAnonymous" class="input_title"><?php p($l->t('Anonymous')) ?></label>

						<div id="anonOptions" style="display:none;">
							<input id="hideNames" name="hideNames" type="checkbox" class="checkbox" <?php $hideNames ? print_unescaped('value="true" checked') : print_unescaped('value="false"'); ?> />
							<label for="hideNames" class="input_title"><?php p($l->t('Hide user names for admin')) ?></label>
						</div>

						<input id="id_expire_set" name="check_expire" type="checkbox" class="checkbox" <?php ($isUpdate && $poll->getExpire() !== null) ? print_unescaped('value="true" checked') : print_unescaped('value="false"'); ?> />
						<label for="id_expire_set" class="input_title"><?php p($l->t('Expires')); ?></label>
						<div class="input-group" id="expiration">
							<input id="id_expire_date" type="text" required="" <?php (!$isUpdate || $poll->getExpire() === null) ? print_unescaped('disabled="true"') : print_unescaped('value="' . $expireStr . '"'); ?> name="expire_date_input" />
						</div>
					</div>
					<div class="col-50">

						<input type="radio" name="pollType" id="event" value="event" class="radio" <?php if (!$isUpdate || $poll->getType() === 0) print_unescaped('checked'); ?> />
						<label for="event"><?php p($l->t('Event schedule')); ?></label>

						<!-- TODO texts to db -->
						<input type="radio" name="pollType" id="text" value="text" class="radio" <?php if ($isUpdate && $poll->getType() === 1) print_unescaped('checked'); ?>>
						<label for="text"><?php p($l->t('Text based')); ?></label>

						<div id="date-select-container" <?php if ($isUpdate && $poll->getType() === 1) print_unescaped('style="display:none;"'); ?> >
							<label for="datetimepicker" class="input_title"><?php p($l->t('Dates')); ?></label>
							<input id="datetimepicker" type="text" />
							<table id="selected-dates-table" class="choices">
							</table>
						</div>
						<div id="text-select-container" <?php if (!$isUpdate || $poll->getType() === 0) print_unescaped('style="display:none;"'); ?> >
							<label for="text-title" class="input_title"><?php p($l->t('Text item')); ?></label>
							<div class="input-group">
								<input type="text" id="text-title" placeholder="<?php print_unescaped('Insert text...'); ?>" />
								<div class="input-group-btn">
									<input type="button" id="text-submit" class="button btn" value="<?php p($l->t('Add')); ?>" class="btn"/>
								</div>
							</div>
							<table id="selected-texts-table" class="choices">
							</table>
						</div>
					</div>
				</div>
				<div class="form-actions">
					<?php if ($isUpdate): ?>
						<input type="submit" id="submit_finish_poll" class="button btn primary" value="<?php p($l->t('Update poll')); ?>" />
					<?php else: ?>
						<input type="submit" id="submit_finish_poll" class="button btn primary" value="<?php p($l->t('Create poll')); ?>" />
					<?php endif; ?>
					<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>" id="submit_cancel_poll" class="button"><?php p($l->t('Cancel')); ?></a>
				</div>
			</form>
		
	</div>

