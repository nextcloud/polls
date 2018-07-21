/**
 * @copyright 2018 René Gieling <github@dartcafe.de>
 *
 * @author 2018 René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
/*jshint esversion: 6 */
/* global OC */
import Vue from 'vue';
import axios from 'axios';
import moment from 'moment';
import lodash from 'lodash';

import UserDiv from './components/userDiv.vue';
import CloudDiv from './components/cloudDiv.vue';
import DatePickerInput from './components/datePickerInput.vue';
import TimePicker from './components/timePicker.vue';

import ShareDiv from './components/shareDiv.vue';
import Breadcrump from './components/breadcrump.vue';
import DatePickerInline from './components/datePickerInline.vue';
import DatePollItem from './components/datePollItem.vue';
import SideBarClose from './components/sideBarClose.vue';
import TextPollItem from './components/textPollItem.vue';

Vue.config.devtools;

Vue.component('user-div', UserDiv);
Vue.component('cloud-div', CloudDiv);
Vue.component('date-picker-input', DatePickerInput);
Vue.component('time-picker', TimePicker);

export class Create {
	start() {
		Vue.mixin({
			methods: {
				t: function(app, text, vars, count, options) {
					return OC.L10N.translate(app, text, vars, count, options);
				},
				n: function(app, textSingular, textPlural, count, vars, options) {
					return OC.L10N.translatePlural(app, textSingular, textPlural, count, vars, options);
				}
			}
		});

		let newPoll = new Vue({
			el: '#app',
			data: {
				poll: {
					mode: 'create',
					comments: [],
					votes: [],
					shares: [],
					event: {
						id: 0,
						hash: '',
						type: 'datePoll',
						title: '',
						description: '',
						owner:'',
						created: '',
						access: 'public',
						expiration: false,
						expire: false,
						expired: false,
						isAnonymous: false,
						fullAnonymous: false,
						disallowMaybe: false,
					},
					options: {
						pollDates: [],
						pollTexts: []
					}
				},
				lang: OC.getLocale(),
				localeData: moment.localeData(moment.locale(OC.getLocale())),
				placeholder: '',
				newPollDate: '',
				newPollTime: '',
				newPollText: '',
				nextPollDateId: 0,
				nextPollTextId: 0,
				protect: false,
				sidebar: false,
				titleEmpty: false,
				slug: '',
				indexPage: ''
			},

			components: {
				'share-div': ShareDiv,
				'breadcrump': Breadcrump,
				'date-picker-inline': DatePickerInline,
				'date-poll-item': DatePollItem,
				'side-bar-close': SideBarClose,
				'text-poll-item': TextPollItem,
			},
			
			created: function() {
				var urlArray = window.location.pathname.split( '/' );
				this.poll.event.hash = urlArray[urlArray.length - 1];
				this.indexPage = OC.generateUrl('apps/polls/');
				if (this.poll.event.hash !== '') {
					this.loadPoll(this.poll.event.hash);
					this.protect = true;
					this.poll.mode = 'edit';
				}
				if (window.innerWidth >1024) {
					this.sidebar = true;
				}
			},
			
			computed: {
				title: function() {
					if (this.poll.event.title === '') {
						return t('polls','Create new poll');
					} else {
						return this.poll.event.title;
						
					}
				}
			},
			
			watch: {
				title () {
					// only used when the title changes after page load
					document.title = t('polls','Polls') + ' - ' + this.title;
				}
			},
			
			methods: {
				switchSidebar: function() {
					this.sidebar = !this.sidebar;
				},
				addShare: function (item){
					this.poll.shares.push(item);
				},

				removeShare: function (item){
					this.poll.shares.splice(this.poll.shares.indexOf(item), 1);
				},

				addNewPollDate: function (newPollDate, newPollTime) {
					if (newPollTime !== undefined) {
						this.newPollDate = moment(newPollDate +' ' + newPollTime);
					} else {
						this.newPollDate = moment(newPollDate);
					}
					this.poll.options.pollDates.push({
						id: this.nextPollDateId++,
						timestamp: moment(newPollDate).unix(),
					});
					this.poll.options.pollDates = _.sortBy(this.poll.options.pollDates, 'timestamp');
				},
				
				addNewPollText: function () {
					if (this.newPollText !== null & this.newPollText !== '') {
						this.poll.options.pollTexts.push({
							id: this.nextPollTextId++,
							text: this.newPollText
						});
					}
					this.newPollText = '';
				},

				writePoll: function (mode) {
					if (mode !== '') {
						this.poll.mode = mode;
					}
					if (this.poll.event.title.length === 0) {
						this.titleEmpty = true;
					} else {
						this.titleEmpty = false;
						axios.post(OC.generateUrl('apps/polls/write'), this.poll)
							.then((response) => {
								this.poll.mode = 'edit';
								this.poll.event.hash = response.data.hash;
								this.poll.event.id = response.data.id;
								window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash);
							}, (error) => {
								console.log(error.response);
						});
					}
				},
				
				loadPoll: function (hash) {
					axios.get(OC.generateUrl('apps/polls/get/poll/' + hash))
					.then((response) => {
						this.poll = response.data.poll;
						if (this.poll.event.type === 'datePoll') {
							var i;
							for (i = 0; i < this.poll.options.pollTexts.length; i++) {
								this.addNewPollDate(new Date(moment.utc(this.poll.options.pollTexts[i].text)));
							}
							this.poll.options.pollTexts = [];
						}
					}, (error) => {
						console.log(error.response);
					});
				}
			},
		});
	}
}
