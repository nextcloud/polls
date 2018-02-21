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



	\OCP\Util::addStyle('polls', 'main');
	\OCP\Util::addStyle('polls', 'createpoll');
	\OCP\Util::addStyle('polls', 'createpoll-newui');

	\OCP\Util::addscript('polls', 'vendor/vue');
	\OCP\Util::addScript('polls', 'app');
	// \OCP\Util::addScript('polls', 'create_edit');

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
		/** @var OCA\Polls\Db\options $options */
		$options = $_['options'];
		$chosen = '[';
		foreach ($options as $optionElement) {
			if ($poll->getType() === 0) {
				$chosen .= strtotime($optionElement->getPollOptionText());
			} else {
				$chosen .= '"' . $optionElement->getPollOptionText() . '"';
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

<div id="app">
	<div id="app-content">
		<div id="app-content-wrapper">
			<div id="controls">
				<div id="breadcrump">
					<div class="crumb svg" data-dir="/">
						<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>">
							<img class="svg" src="<?php print_unescaped(\OCP\Template::image_path('core', 'places/home.svg')); ?>" alt="Home">
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
		
			<div id="workbench" class="main-container">
				<div class="flex-row first">
		
					<div class="flex-column poll_description">
						<label for="pollTitle" ><?php p($l->t('Title')); ?></label>
						<input type="text" id="pollTitle" name="pollTitle" v-model="title">
						<label for="pollDesc"><?php p($l->t('Description')); ?></label>
						<textarea id="pollDesc" name="pollDesc" v-model="description"></textarea>
					</div>
					
					<div class="flex-column">
						<label><?php p($l->t('Access')); ?></label>
						<div>
							<input type="radio" v-model="accessType" value="registered" id="private" class="radio"/>
							<label for="private"><?php p($l->t('Registered users only')); ?></label>
						</div>
						<div>
							<input type="radio" v-model="accessType" value="hidden" id="hidden" class="radio"/>
							<label for="hidden"><?php p($l->t('hidden')); ?></label>
						</div>
						<div>
							<input type="radio" v-model="accessType" value="public" id="public" class="radio"/>
							<label for="public"><?php p($l->t('Public access')); ?></label>
						</div>
						<div>
							<input type="radio" v-model="accessType" value="select" id="select" class="radio"/>
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
						</div>
					</div>

					<div class="flex-column">
						<input id="anonymous" v-model="anonymousType"type="checkbox" class="checkbox" />
						<label for="anonymous">{{anonymousLabel}}</label>

						<div v-show="anonymousType">
							<input id="trueAnonymous" v-model="trueAnonymousType" type="checkbox" class="checkbox"/>
							<label for="trueAnonymous">{{trueAnonymousLabel}}</label>
						</div>

						<div class="expirationView subView">
							<input id="expiration" v-model="expiration" type="checkbox" class="checkbox" />
							<label for="expiration">{{expirationDateLabel}}</label>
							  <date-picker v-model="expirationDate" date-format="yy-mm-dd" v-show="expiration"></date-picker>
							<pre>{{ $data }}</pre>
						</div>
						
					</div>
				</div>
				<div class="flex-column">
					<div id="pollType">
						<input id="datePoll" v-model="pollType" value="datePoll" type="radio" class="radio"/>
						<label for="datePoll"><?php p($l->t('Event schedule')); ?></label>
						<input id="textPoll" v-model="pollType" value="textPoll" type="radio" class="radio"/>
						<label for="textPoll"><?php p($l->t('Text based')); ?></label>
					</div>
					<div id="pollContent" class="flex-column poll_table">
						<div id="date-select-container" v-show="pollType === 'datePoll'">
							<div id="addOptionButton">
								<button class="events--button button btn primary" type="button"><?php p($l->t('Add date option')); ?></button>
							</div>
							<date-poll-table></date-poll-table>
						</div>
						<div id="text-select-container" v-show="pollType === 'textPoll'">
							<div id="addOptionButton">
								<button class="events--button button btn primary" type="button"><?php p($l->t('Add text option')); ?></button>
							</div>
							<text-poll-table></text-poll-table>
						</div>
						<div class="form-actions">
							<?php if ($isUpdate): ?>
								<input type="submit" id="submit_finish_poll" class="button btn primary" value="<?php p($l->t('Update poll')); ?>" />
							<?php else: ?>
								<input type="submit" id="submit_finish_poll" class="button btn primary" value="<?php p($l->t('Create poll')); ?>" />
							<?php endif; ?>
							<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>" id="submit_cancel_poll" class="button"><?php p($l->t('Cancel')); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
