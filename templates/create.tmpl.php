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

	\OCP\Util::addStyle('polls', 'main');
	\OCP\Util::addStyle('polls', 'createpoll');
	\OCP\Util::addStyle('polls', 'createpoll-newui');
	\OCP\Util::addStyle('polls', 'vendor/jquery.ui.timepicker');

	\OCP\Util::addscript('polls', 'vendor/lodash.core.min');
	\OCP\Util::addscript('polls', 'vendor/vue'); //developing
	// \OCP\Util::addscript('polls', 'vendor/vue.min'); // production
	\OCP\Util::addscript('polls', 'vendor/jquery.ui.timepicker');
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
	$lang = \OC::$server->getL10NFactory()->findLanguage();

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
		
			<div id="workbench" class="main-container flex-row">
				<div class="flex-column">
					<div class="flex-column poll_description">
						<label for="pollTitle" ><?php p($l->t('Title')); ?></label>
						<input type="text" id="pollTitle" name="pollTitle" v-model="polls_event.title">
						<label for="pollDesc"><?php p($l->t('Description')); ?></label>
						<textarea id="pollDesc" name="pollDesc" v-model="polls_event.description"></textarea>
					</div>
					
					<div class="flex-column">
						<div id="pollContent" class="flex-column poll_table">
							<div id="date-poll-list" v-show="polls_event.pollType === 'datePoll'">
								<transition-group name="list" tag="ul" class="flex-row">
									<li
										is="date-poll-item"
										v-for="(pollDate, index) in votes.pollDates"
										v-bind:option="pollDate"
										v-bind:key="pollDate.id"
										v-on:remove="votes.pollDates.splice(index, 1)">
									</li>
								</transition-group>
							</div>
							<div id="text-poll-list" v-show="polls_event.pollType === 'textPoll'">
								<transition-group name="list" tag="ul" class="flex-column">
									<li
										is="text-poll-item"
										v-for="(pollText, index) in votes.pollTexts"
										v-bind:option="pollText"
										v-bind:key="pollText.id"
										v-on:remove="votes.pollTexts.splice(index, 1)">
									</li>
								</transition-group>
							</div>
						</div>
					</div>
				</div>

				<div class="flex-column">
					<div id="polls_event.pollType">
						<input id="datePoll" v-model="polls_event.pollType" value="datePoll" type="radio" class="radio"/>
						<label for="datePoll"><?php p($l->t('Event schedule')); ?></label>
						<input id="textPoll" v-model="polls_event.pollType" value="textPoll" type="radio" class="radio"/>
						<label for="textPoll"><?php p($l->t('Text based')); ?></label>
					</div>
					<div id="date-select-container" v-show="polls_event.pollType === 'datePoll'">
						<span><?php p($l->t('Select the time for the poll option to add:')); ?></span>
						<time-picker placeholder="<?php p($l->t('Add time')); ?>" v-model="newPollTime"></time-picker>
						<date-picker-inline v-model="newPollDate" date-format="yy-mm-dd" v-show="polls_event.pollType === 'datePoll'"></date-picker-inline>
					</div>
					<div id="text-select-container" v-show="polls_event.pollType === 'textPoll'">
						<input v-model="newPollText" @keyup.enter="addNewPollText" placeholder="<?php p($l->t('Add option')); ?>">
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
	
	<div id="app-sidebar" class="detailsView scroll-container">
		<side-bar-close></side-bar-close>
		<div class="header flex-row">
			<div class="pollInformation flex-column">
				<autor-div class="authorRow user-cell flex-row"></autor-div>
			</div>
		</div>

		<ul class="tabHeaders">
			<li class="tabHeader selected" data-tabid="optionsTabView" data-tabindex="0">
				<a href="#"><?php p($l->t('Poll options')); ?></a>
			</li>
		</ul>		
		<div class="tabsContainer">
			<div id="optionsTabView" class="tab optionsTabView">
				<div class="flex-row">
					<div class="flex-column">
						<label><?php p($l->t('Access')); ?></label>
						<div>
							<input type="radio" v-model="polls_event.accessType" value="registered" id="private" class="radio"/>
							<label for="private"><?php p($l->t('Registered users only')); ?></label>
						</div>
						<div>
							<input type="radio" v-model="polls_event.accessType" value="hidden" id="hidden" class="radio"/>
							<label for="hidden"><?php p($l->t('hidden')); ?></label>
						</div>
						<div>
							<input type="radio" v-model="polls_event.accessType" value="public" id="public" class="radio"/>
							<label for="public"><?php p($l->t('Public access')); ?></label>
						</div>
						<div>
							<input type="radio" v-model="polls_event.accessType" value="select" id="select" class="radio"/>
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
						<label><?php p($l->t('Poll options')); ?></label>
						<div>
							<input id="maybeOptionAllowed" v-model="polls_event.maybeOptionAllowed"type="checkbox" class="checkbox" />
							<label for="maybeOptionAllowed">{{label.maybeOptionAllowed}}</label>
						</div>
						
						<div>
							<input id="anonymous" v-model="polls_event.is_anonymous"type="checkbox" class="checkbox" />
							<label for="anonymous">{{label.is_anonymous}}</label>

							<input id="trueAnonymous" v-model="polls_event.full_anonymous" v-show="polls_event.is_anonymous" type="checkbox" class="checkbox"/>
							<label for="trueAnonymous" v-show="polls_event.is_anonymous">{{label.full_anonymous}} </label>
						</div>

						<div class="expirationView subView">
							<input id="expiration" v-model="polls_event.expiration" type="checkbox" class="checkbox" />
							<label for="expiration">{{label.expirationDate}}</label>
							  <date-picker placeholder="<?php p($l->t('Expiration date')); ?>" v-model="polls_event.expirationDate" date-format="yy-mm-dd" v-show="polls_event.expiration"></date-picker>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
