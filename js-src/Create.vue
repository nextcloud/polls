<template>
	<div id="create-poll" class="flex-column">

		<div class="controls">
			<breadcrump :index-page="indexPage" :intitle="title"></breadcrump>
			<button v-if="poll.mode === 'edit'" @click="writePoll(poll.mode)" class="button btn primary"><span>{{ t('polls', 'Update poll') }}</span></button>
			<button v-if="poll.mode === 'create'" @click="writePoll(poll.mode)" class="button btn primary"><span>{{ t('polls', 'Create new poll') }}</span></button>
			<a :href="indexPage" class="button">{{ t('polls', 'Cancel') }}</a>

			<button @click="switchSidebar" class="button">
				<span class="symbol icon-settings"></span>
			</button>
		</div>

		<div class="polls-content flex-row">
			<div class="workbench">
				<div class="flex-column">
					<h2>{{ t('polls', 'Poll description') }}</h2>
					<div class="flex-column">
						<label>{{ t('polls', 'Title') }}</label>
						<input type="text" id="pollTitle" :class="{ error: titleEmpty }" v-model="poll.event.title">
					</div>
					<div class="flex-column">
						<label>{{ t('polls', 'Description') }}</label>
						<textarea id="pollDesc" v-model="poll.event.description"></textarea>
					</div>
				</div>
				<div class="flex-column">
					<h2>{{ t('polls', 'Vote options') }}</h2>
					<div v-if="poll.mode == 'create'">
						<input id="datePoll" v-model="poll.event.type" value="datePoll" type="radio" class="radio" :disabled="protect"/>
						<label for="datePoll">{{ t('polls', 'Event schedule') }}</label>
						<input id="textPoll" v-model="poll.event.type" value="textPoll" type="radio" class="radio" :disabled="protect"/>
						<label for="textPoll">{{ t('polls', 'Text based') }}</label>
					</div>

					<div class="flex-row flex-wrap" v-show="poll.event.type === 'datePoll'">
						<div id="poll-item-selector-date">
							<div class="time-seletcion flex-row">
								<label for="poll-time-picker">{{ t('polls', 'Select time for the date:') }}</label>
								<time-picker id="poll-time-picker" :placeholder=" t('polls', 'Add time') " v-model="newPollTime" />
							</div>
							<date-picker-inline @selected="addNewPollDate" :locale-data="localeData" :time="newPollTime" v-show="poll.event.type === 'datePoll'" />
						</div>
						<transition-group id="date-poll-list" name="list" tag="ul" class="flex-column poll-table">
							<li
								is="date-poll-item"
								v-for="(pollDate, index) in poll.options.pollDates"
								:option="pollDate"
								:key="pollDate.id"
								@remove="poll.options.pollDates.splice(index, 1)">
							</li>
						</transition-group>
					</div>
					<div class="flex-column flex-wrap" v-show="poll.event.type === 'textPoll'">
						<transition-group id="text-poll-list" name="list" tag="ul" class="poll-table">
							<li
								is="text-poll-item"
								v-for="(pollText, index) in poll.options.pollTexts"
								:option="pollText"
								:key="pollText.id"
								@remove="poll.options.pollTexts.splice(index, 1)">
							</li>
						</transition-group>

						<div id="poll-item-selector-text" >
							<input v-model="newPollText" @keyup.enter="addNewPollText()" :placeholder=" t('polls', 'Add option') ">
						</div>
					</div>
				</div>
			</div>

			<div id="polls-sidebar" v-if="sidebar" class="flex-column detailsView scroll-container">
				<div class="header flex-row">
					<div class="pollInformation flex-column">
						<user-div description="Owner" :user-id="poll.event.owner"></user-div>
					</div>
				</div>

				<ul class="tabHeaders">
					<li class="tabHeader selected" data-tabid="configurationsTabView" data-tabindex="0">
						<a href="#">{{ t('polls', 'Configuration') }}</a>
					</li>
				</ul>

				<div class="tabsContainer">
					<div class="tab configurationsTabView flex-row flex-wrap align-centered space-between" @click="protect=false" v-if="protect">
						<span>{{ t('polls', 'Configuration is locked. Changing options may result in unwanted behaviour,but you can unlock it anyway.') }}</span>
						<button> {{ t('polls', 'Unlock configuration ') }} </button>
					</div>
					<div id="configurationsTabView" class="tab configurationsTabView flex-row flex-wrap">

						<div class="configBox flex-column" v-if="poll.mode =='edit'">
							<label class="title">{{ t('polls', 'Poll type') }}</label>
							<input id="datePoll" v-model="poll.event.type" value="datePoll" type="radio" class="radio" :disabled="protect"/>
							<label for="datePoll">{{ t('polls', 'Event schedule') }}</label>
							<input id="textPoll" v-model="poll.event.type" value="textPoll" type="radio" class="radio" :disabled="protect"/>
							<label for="textPoll">{{ t('polls', 'Text based') }}</label>
						</div>

						<div class="configBox flex-column">
							<label class="title">{{ t('polls', 'Poll configurations') }}</label>
								<input :disabled="protect" id="disallowMaybe" v-model="poll.event.disallowMaybe"type="checkbox" class="checkbox" />
								<label for="disallowMaybe">{{ t('polls', 'Disallow maybe vote') }}</label>

								<input :disabled="protect" id="anonymous" v-model="poll.event.isAnonymous"type="checkbox" class="checkbox" />
								<label for="anonymous">{{ t('polls', 'Anonymous poll') }}</label>

								<input :disabled="protect" id="trueAnonymous" v-model="poll.event.fullAnonymous" v-show="poll.event.isAnonymous" type="checkbox" class="checkbox"/>
								<label for="trueAnonymous" v-show="poll.event.isAnonymous">{{ t('polls', 'Hide user names for admin') }} </label>

								<input :disabled="protect" id="expiration" v-model="poll.event.expiration" type="checkbox" class="checkbox" />
								<label for="expiration">{{ t('polls', 'Expires') }}</label>
								<date-picker-input :disabled="protect" :placeholder="t('polls', 'Expiration date')" v-model="poll.event.expire" v-show="poll.event.expiration"></date-picker-input>
						</div>

						<div class="configBox flex-column">
							<label class="title">{{ t('polls', 'Access') }}</label>
							<input :disabled="protect" type="radio" v-model="poll.event.access" value="registered" id="private" class="radio"/>
							<label for="private">{{ t('polls', 'Registered users only') }}</label>
							<input :disabled="protect" type="radio" v-model="poll.event.access" value="hidden" id="hidden" class="radio"/>
							<label for="hidden">{{ t('polls', 'hidden') }}</label>
							<input :disabled="protect" type="radio" v-model="poll.event.access" value="public" id="public" class="radio"/>
							<label for="public">{{ t('polls', 'Public access') }}</label>
							<input :disabled="protect" type="radio" v-model="poll.event.access" value="select" id="select" class="radio"/>
							<label for="select">{{ t('polls', 'Only shared') }}</label>

						</div>
						<share-div id="share-list" class="configBox flex-column oneline" 
									:placeholder="t('polls', 'Name of user or group')" 
									:active-shares="poll.shares" 
									v-show="poll.event.access === 'select'"
									@add-share="addShare" 
									@remove-share="removeShare"/>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import axios from 'axios';
	import moment from 'moment';
	import lodash from 'lodash';

	import ShareDiv from './components/shareDiv.vue';
	import Breadcrump from './components/breadcrump.vue';
	import DatePickerInline from './components/datePickerInline.vue';
	import DatePollItem from './components/datePollItem.vue';
	import SideBarClose from './components/sideBarClose.vue';
	import TextPollItem from './components/textPollItem.vue';

	export default {
		name: 'create-poll',

		components: {
			'share-div': ShareDiv,
			'breadcrump': Breadcrump,
			'date-picker-inline': DatePickerInline,
			'date-poll-item': DatePollItem,
			'side-bar-close': SideBarClose,
			'text-poll-item': TextPollItem,
		},

		data: function () {
			return {
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
			}
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
		}
	}
	
</script>
