<template>
	<div id="create-poll">

		<div class="controls">
			<breadcrump :index-page="indexPage" :intitle="title"></breadcrump>
			<button @click="writePoll(poll.mode)" class="button btn primary"><span>{{ saveButtonTitle }}</span></button>
			<a :href="indexPage" class="button">{{ t('polls', 'Cancel') }}</a>

			<button @click="switchSidebar" class="button">
				<span class="symbol icon-settings"></span>
			</button>
		</div>

		<div class="polls-content">
			<div class="workbench">
				<div>
					<h2>{{ t('polls', 'Poll description') }}</h2>
					<div>{{ t('polls', 'Title') }}</div>
					<input type="text" id="pollTitle" :class="{ error: titleEmpty }" v-model="poll.event.title">
					<div>{{ t('polls', 'Description') }}</div>
					<textarea id="pollDesc" v-model="poll.event.description"></textarea>
				</div>
				<div id="poll-options">
					<h2>{{ t('polls', 'Vote options') }}</h2>
					<div v-if="poll.mode == 'create'">
						<input id="datePoll" v-model="poll.event.type" value="datePoll" type="radio" class="radio" :disabled="protect"/>
						<label for="datePoll">{{ t('polls', 'Event schedule') }}</label>
						<input id="textPoll" v-model="poll.event.type" value="textPoll" type="radio" class="radio" :disabled="protect"/>
						<label for="textPoll">{{ t('polls', 'Text based') }}</label>
					</div>

					<div id="date-poll-container" v-show="poll.event.type === 'datePoll'">
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
					<div id="text-poll-container" v-show="poll.event.type === 'textPoll'">
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

			<div id="polls-sidebar" v-if="sidebar">
				<user-div description="Owner" :user-id="poll.event.owner"></user-div>
				<cloud-div v-bind:options="poll.event"></cloud-div>
				<div v-if="protect">{{ t('polls', 'Configuration is locked. Changing options may result in unwanted behaviour,but you can unlock it anyway.') }}</div>
				<button v-if="protect" @click="protect=false" > {{ t('polls', 'Unlock configuration ') }} </button>
				<tab-container v-model="activeTab"
					:tabs="tabData" >
					<component :is="activeTab.component" :protect="protect" :mode="poll.mode" v-model="poll">
					</component>
				</tab-container>

					<div id="configurationsTabView" class="tab configurationsTabView flex-row flex-wrap">


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
	import TabContainer from './components/_tab-Container.vue';
	import ConfigTab from './components/_tab-ConfigTab.vue';
	import UserProfiles from './components/_test-UserProfiles.vue';

	
	const tabData = [
		{
			displayTitle: t('polls', 'Configuration'),
			component: 'config-tab'
		},
		{
			displayTitle: t('polls', 'Share'),
			component: 'UserProfiles'
		},
 		{
			displayTitle: t('polls', 'Comments'),
			component: {
				name: 'SomeOtherComponent',
				template: '<div>					<p>Dynamic Componet!</p>				</div>'
			}
		}
	];

	export default {
		name: 'create-poll',

		components: {
			'share-div': ShareDiv,
			'breadcrump': Breadcrump,
			'date-picker-inline': DatePickerInline,
			'date-poll-item': DatePollItem,
			'side-bar-close': SideBarClose,
			'text-poll-item': TextPollItem,
			'tab-container': TabContainer,
			'config-tab': ConfigTab,
			'UserProfiles': UserProfiles,
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
				indexPage: '',
				longDateFormat: moment.localeData().longDateFormat('L'),
				activeTab: tabData[0],
				tabData: tabData
			}
		},

		created: function() {
			this.indexPage = OC.generateUrl('apps/polls/');
			this.$store.commit('increment')
			var urlArray = window.location.pathname.split( '/' );

			if (urlArray[urlArray.length - 1] === 'create') {
				this.poll.event.owner = OC.getCurrentUser().uid;
			} else {
				this.loadPoll(urlArray[urlArray.length - 1])
				this.protect = true;
				this.poll.mode = 'edit';
			};

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
			},
			saveButtonTitle: function() {
				if (this.poll.mode === 'edit') {
					return t('polls', 'Update poll')
				} else {
					return t('polls', 'Create new poll')
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
							this.poll.event.hash = '';
							console.log(error.response);
					});
				}
			},
			
			loadPoll: function (hash) {
				this.$store.dispatch('poll/getPollByHash', hash)
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
					this.poll.event.hash = '';
					console.log(error.response);
				});
			}
		}
	}
	
</script>

<style lang="scss">

#poll-options {
	> div {
		display: flex;
		flex-grow: 1;
		flex-wrap: wrap;
		> div {
			padding-right: 8px;
		}
	}
}

#text-poll-container > * {
	width: 100%;
}
</style>
