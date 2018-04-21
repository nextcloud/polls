<?php
	/**
	 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 *
	 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 * @author René Gieling <github@dartcafe.de>
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


	/* global OC, OCA, $, _, t, define, console */
	use OCP\User; //To do: replace according to API

	\OCP\Util::addStyle('polls', 'main');
	\OCP\Util::addStyle('polls', 'flex');
	\OCP\Util::addStyle('polls', 'createpoll');
	\OCP\Util::addStyle('polls', 'sidebar');
	\OCP\Util::addStyle('polls', 'createpoll-newui');
	\OCP\Util::addStyle('polls', 'vendor/jquery.ui.timepicker');

	\OCP\Util::addscript('polls', 'vendor/axios.min');
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

<div id="app" class="flex-row">
	<div id="polls-content">
			<div id="controls">
				<breadcrump :intitle="poll.event.title" />
			</div>
		
			<div class="flex-column workbench">
				<div id="poll-title">
					<label for="pollTitle">{{ t('polls', 'Title') }}</label>
					<input type="text" id="pollTitle" name="pollTitle" v-model="poll.event.title">
				</div>
				<div id="poll-description">
					<label for="pollDesc">{{ t('polls', 'Description') }}</label>
					<textarea id="pollDesc" name="pollDesc" v-model="poll.event.description"></textarea>
				</div>
				<div id="poll-type">
					<input id="datePoll" v-model="poll.event.type" value="datePoll" type="radio" class="radio"/>
					<label for="datePoll">{{ t('polls', 'Event schedule') }}</label>
					<input id="textPoll" v-model="poll.event.type" value="textPoll" type="radio" class="radio"/>
					<label for="textPoll">{{ t('polls', 'Text based') }}</label>
				</div>
				<div class="flex-row flex-wrap" v-show="poll.event.type === 'datePoll'">
					<div id="poll-item-selector-date">
						<div class="time-seletcion flex-row">
							<label for="poll-date-picker">{{ t('polls', 'Select time for the date:') }}</label>
							<time-picker id="poll-date-picker" :placeholder=" t('polls', 'Add time') " v-model="newPollTime" />
						</div>
						<date-picker-inline v-model="newPollDate" date-format="yy-mm-dd" v-show="poll.event.type === 'datePoll'" />
					</div>
					<transition-group id="date-poll-list" name="list" tag="ul" class="flex-column poll-table">
						<li
							is="date-poll-item"
							v-for="(pollDate, index) in poll.options.pollDates"
							v-bind:option="pollDate"
							v-bind:key="pollDate.id"
							v-on:remove="poll.options.pollDates.splice(index, 1)">
						</li>
					</transition-group>
				</div>
				<div class="flex-column flex-wrap" v-show="poll.event.type === 'textPoll'">
					<transition-group id="text-poll-list" name="list" tag="ul" class="poll-table">
						<li
							is="text-poll-item"
							v-for="(pollText, index) in poll.options.pollTexts"
							v-bind:option="pollText"
							v-bind:key="pollText.id"
							v-on:remove="poll.options.pollTexts.splice(index, 1)">
						</li>
					</transition-group>

					<div id="poll-item-selector-text" >
						<input v-model="newPollText" @keyup.enter="addNewPollText()" :placeholder=" t('polls', 'Add option') ">
					</div>
				</div>


				<div class="form-actions">
					<?php if ($isUpdate): ?>
						<input type="submit" id="submit_finish_poll" class="button btn primary" :value="t('polls', 'Update poll')" />
					<?php else: ?>
						<input type="submit" id="submit_finish_poll" class="button btn primary" :value="t('polls', 'Create poll')" />
					<?php endif; ?>
					<a href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>" id="submit_cancel_poll" class="button">{{ t('polls', 'Cancel') }}</a>
				</div>

			</div>
	</div>
	
	<div id="polls-sidebar" class="detailsView scroll-container">
		<side-bar-close></side-bar-close>
		<div class="header flex-row">
			<div class="pollInformation flex-column">
				<author-div class="authorRow user-cell flex-row" />
			</div>
		</div>

		<ul class="tabHeaders">
			<li class="tabHeader selected" data-tabid="configurationsTabView" data-tabindex="0">
				<a href="#">{{ t('polls', 'Poll configurations') }}</a>
			</li>
		</ul>		
		<div class="tabsContainer">
			<div id="configurationsTabView" class="tab configurationsTabView flex-row flex-wrap">
				<div id="poll-access" class="flex-column">
					<label>{{ t('polls', 'Access') }}</label>
					<div>
						<input type="radio" v-model="poll.event.access" value="registered" id="private" class="radio"/>
						<label for="private">{{ t('polls', 'Registered users only') }}</label>
					</div>
					<div>
						<input type="radio" v-model="poll.event.access" value="hidden" id="hidden" class="radio"/>
						<label for="hidden">{{ t('polls', 'hidden') }}</label>
					</div>
					<div>
						<input type="radio" v-model="poll.event.access" value="public" id="public" class="radio"/>
						<label for="public">{{ t('polls', 'Public access') }}</label>
					</div>
					<div>
						<input type="radio" v-model="poll.event.access" value="select" id="select" class="radio"/>
						<label for="select">{{ t('polls', 'Select') }}</label>
						<span id="id_label_select">...</span>

						<div id="selected_access" class="row user-group-list">
							<ul id="selected-search-list-id">
							</ul>
						</div>
						<div id="access_rights" class="row user-group-list">
							<div class="col-50">
								<input type="text" class="live-search-box" id="user-group-search-box" :placeholder="t('polls', 'User/Group search')" />
								<ul class="live-search-list" id="live-search-list-id">
								</ul>
							</div>
						</div>
					</div>
				</div>
				
				<div id="poll-configuration" class="flex-column">
					<label>{{ t('polls', 'Poll configurations') }}</label>
					<div>
						<input id="disallowMaybe" v-model="poll.event.disallowMaybe"type="checkbox" class="checkbox" />
						<label for="disallowMaybe">{{ t('polls', 'Disallow maybe vote') }}</label>
					</div>
					
					<div>
						<input id="anonymous" v-model="poll.event.is_anonymous"type="checkbox" class="checkbox" />
						<label for="anonymous">{{ t('polls', 'Anonymous poll') }}</label>

						<input id="trueAnonymous" v-model="poll.event.full_anonymous" v-show="poll.event.is_anonymous" type="checkbox" class="checkbox"/>
						<label for="trueAnonymous" v-show="poll.event.is_anonymous">{{ t('polls', 'Hide user names for admin') }} </label>
					</div>

					<div class="expirationView subView">
						<input id="expiration" v-model="poll.event.expiration" type="checkbox" class="checkbox" />
						<label for="expiration">{{ t('polls', 'Expires') }}</label>
						  <date-picker :placeholder="t('polls', 'Expiration date')" v-model="poll.event.expire" date-format="yy-mm-dd" v-show="poll.event.expiration"></date-picker>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
