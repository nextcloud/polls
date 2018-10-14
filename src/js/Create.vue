<template>
	<div id="create-poll" class="flex-column">
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
					
					<label>{{ t('polls', 'Title') }}</label>
					<input type="text" id="pollTitle" :class="{ error: titleEmpty }" v-model="poll.event.title">
				
					<label>{{ t('polls', 'Description') }}</label>
					<textarea id="pollDesc" v-model="poll.event.description" style="resize: vertical; 	width: 100%;"></textarea>

				</div>
				
				<div>
					<h2>{{ t('polls', 'Vote options') }}</h2>
					
					<div v-if="poll.mode == 'create'">
						<input id="datePoll" v-model="poll.event.type" value="datePoll" type="radio" class="radio" :disabled="protect"/>
						<label for="datePoll">{{ t('polls', 'Event schedule') }}</label>
						<input id="textPoll" v-model="poll.event.type" value="textPoll" type="radio" class="radio" :disabled="protect"/>
						<label for="textPoll">{{ t('polls', 'Text based') }}</label>
					</div>

						
					<date-picker @change="addNewPollDate" 
						v-bind="optionDatePicker"
						v-model="newPollDate"
						style="width:100%" 
						v-show="poll.event.type === 'datePoll'"
						confirm />
						
					<transition-group 
						id="date-poll-list" 
						name="list" 
						tag="ul" 
						class="poll-table" 
						v-show="poll.event.type === 'datePoll'">
						<li
							is="date-poll-item"
							v-for="(pollDate, index) in poll.options.pollDates"
							:option="pollDate"
							:key="pollDate.id"
							@remove="poll.options.pollDates.splice(index, 1)">
						</li>
					</transition-group>

				
					<div id="poll-item-selector-text" v-show="poll.event.type === 'textPoll'" >
						<input v-model="newPollText" @keyup.enter="addNewPollText()" :placeholder=" t('polls', 'Add option') ">
					</div>
					
					<transition-group 
						id="text-poll-list" 
						name="list" 
						tag="ul" 
						class="poll-table" 
						v-show="poll.event.type === 'textPoll'">
						<li
							is="text-poll-item"
							v-for="(pollText, index) in poll.options.pollTexts"
							:option="pollText"
							:key="pollText.id"
							@remove="poll.options.pollTexts.splice(index, 1)">
						</li>
					</transition-group>


				</div>
			</div>

			<div id="polls-sidebar" v-if="sidebar" class="flex-column detailsView scroll-container">
				<div class="header">
					<div class="pollInformation flex-column">
						<user-div :description="t('polls', 'Owner')" :user-id="poll.event.owner"></user-div>
					</div>
				</div>

				<ul class="tabHeaders">
					<li class="tabHeader selected" data-tabid="configurationsTabView" data-tabindex="0">
						<a href="#">{{ t('polls', 'Configuration') }}</a>
					</li>
				</ul>

				<div>
					<div class="flex-wrap align-centered space-between" v-if="protect">
						<span>{{ t('polls', 'Configuration is locked. Changing options may result in unwanted behaviour, but you can unlock it anyway.') }}</span>
						<button @click="protect=false" > {{ t('polls', 'Unlock configuration ') }} </button>
					</div>
					<div id="configurationsTabView" class="tab configurationsTabView flex-wrap">

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
								<date-picker v-bind="expirationDatePicker"
									:disabled="protect" 
									v-model="poll.event.expirationDate" 
									v-show="poll.event.expiration" 
									style="width:170px" 
									:time-picker-options="{ start: '00:00', step: '00:05', end: '23:55' }" />
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
									:active-shares="poll.shares" 
									:placeholder="t('polls', 'Name of user or group')" 
									v-show="poll.event.access === 'select'"/>
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

	import DatePollItem from './components/datePollItem.vue';
	import TextPollItem from './components/textPollItem.vue';
	
	export default {
		name: 'create-poll',

		components: {
			'DatePollItem': DatePollItem,
			'TextPollItem': TextPollItem,
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
						expirationDate: '',
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
				system:[],
				lang: '',
				locale: '',
				placeholder: '',
				newPollDate: '',
				newPollTime: '',
				newPollText: '',
				nextPollDateId: 1,
				nextPollTextId: 1,
				protect: false,
				sidebar: false,
				titleEmpty: false,
				indexPage: '',
				longDateFormat: '',
				dateTimeFormat: '',
			}
		},

		created: function() {
			this.indexPage = OC.generateUrl('apps/polls/');
			this.getSystemValues();
			this.lang = OC.getLanguage();
			try {
				this.locale = OC.getLocale();
			} catch (e) {
				if (e instanceof TypeError) {
					this.locale = this.lang;
				} else {
					console.log(e)
				}
			}
			moment.locale(this.locale);
			this.longDateFormat = moment.localeData().longDateFormat('L');
			this.dateTimeFormat = moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT');
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
			langShort: function () {
				return this.lang.split("-")[0]
			},
			
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
			},
			localeData:  function () {
				return moment.localeData(moment.locale(this.locale))
			},

			expirationDatePicker:   function () {
				return {
					editable: true,
					minuteStep: 1,
					type: 'datetime',
					lang: this.lang.split("-")[0],
					format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
					placeholder: t('polls', 'Expiration date') 
				}
			},

			optionDatePicker: function () {
				return {
					editable: false,
					minuteStep: 1,
					type: 'datetime', 
					format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
					lang: this.lang.split("-")[0],
					placeholder: t('polls', 'Click to add a date'),
					timePickerOptions: { 
						start: '00:00', 
						step: '00:05', 
						end: '23:55' 
					}
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
		
			switchSidebar () {
				this.sidebar = !this.sidebar;
			},
			
			getSystemValues: function() {
				axios.get(OC.generateUrl('apps/polls/get/system'))
				.then((response) => {
					this.system = response.data.system;
				}, (error) => {
					this.poll.event.hash = '';
					console.log(error.response);
				});
			},
			
			addShare: function (item){
				this.poll.shares.push(item);
			},

			removeShare: function (item){
				this.poll.shares.splice(this.poll.shares.indexOf(item), 1);
			},

			addNewPollDate: function (newPollDate) {
				if (newPollDate != null) {
					this.newPollDate = moment(newPollDate);
					this.poll.options.pollDates.push({
						id: this.nextPollDateId++,
						timestamp: moment(newPollDate).unix(),
					});
					this.poll.options.pollDates = _.sortBy(this.poll.options.pollDates, 'timestamp');
				}
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
							// window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash);
						}, (error) => {
							this.poll.event.hash = '';
							console.log(this.poll.event.hash);
							console.log(error.response);
					});
				}
			},
			
			loadPoll: function (hash) {
				axios.get(OC.generateUrl('apps/polls/get/poll/' + hash))
				.then((response) => {
					this.poll = response.data.poll;
					if (this.poll.event.expirationDate !== null) {
						this.poll.event.expirationDate = new Date(moment.utc(this.poll.event.expirationDate))
					} else {
						this.poll.event.expirationDate = ''
					}

					if (this.poll.event.type === 'datePoll') {
						var i;
						for (i = 0; i < this.poll.options.pollTexts.length; i++) {
							this.addNewPollDate(new Date(moment.utc(this.poll.options.pollTexts[i].text)))
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
	#content {
		display: flex;
	}

	#create-poll {
		width: 100%;
		input.hasTimepicker {
			width: 75px;
		}
	}

	.controls {
		display: flex;
		/* flex-direction: row; */
		/* flex-grow: 0; */
		border-bottom: 1px solid var(--color-border);
		position: fixed;
		background: var(--color-main-background);
		width: 100%;
		height: 45px;
		z-index: 1001;
		.button, button {
			flex-shrink: 0;
			/* box-sizing: border-box; */
			/* display: inline-block; */
			height: 36px;
			padding: 7px 10px;
			border: 0;
			background: transparent;
			color: var(--color-text-lighter);
			&.primary {
				background: var(--color-primary);
				color: var(--color-primary-text);
			}
		}
		.breadcrump {
			/* flex-shrink: 1; */
			overflow: hidden;
			min-width: 35px;
			div.crumb:last-child {
					flex-shrink: 1;
					overflow: hidden;
				> span {
					flex-shrink: 1;
					text-overflow: ellipsis;
				}
			}
		}
	}

	.polls-content {
		display: flex;
		padding-top: 45px;
		flex-grow: 1;
		.workbench {
			display: flex;
			justify-content: center;
			flex-grow: 1;
			flex-wrap: wrap;
			overflow-x: hidden;
			
			
			> div {
				min-width: 245px;
				max-width: 540px;
				display: flex;
				flex-direction: column;
				flex-grow: 1;
				padding: 8px;
			}
		}
	}

	input, textarea {
		&.error {
			border: 2px solid var(--color-error);
			box-shadow: 1px 0 var(--border-radius) var(--color-box-shadow);
		}
	}

	/* Transitions for inserting and removing list items */
		.list-enter-active, .list-leave-active {
			transition: all 0.5s ease;
		}

		.list-enter, .list-leave-to {
			opacity: 0;
		}

		.list-move {
			transition: transform 0.5s;
		}
	/*  */


	#poll-item-selector-text {
		> input {
			width: 100%
		}
	}

	.poll-table {
		>li {
			display: flex;
			align-items: center;
			padding-left: 8px;
			padding-right: 8px;
			line-height: 2em;
			min-height: 4em;
			border-bottom: 1px solid var(--color-border);
			overflow: hidden;
			white-space: nowrap;
			
			&:hover, &:active {
				transition: var(--background-dark) 0.3s ease;
				background-color: var(--color-loading-light); //$hover-color;
			}
			
			> div {
				display: flex;
				flex-grow: 1;
				font-size: 1.2em;
				opacity: 0.7;
				white-space: normal;
				padding-right: 4px;
				&.avatar {
					flex-grow: 0;
				}
			}

			> div:nth-last-child(1) {
				justify-content: center;
				flex-grow: 0;
				flex-shrink: 0;
			}
		}
	}

	button {
		&.button-inline{
			border: 0;
			background-color: transparent;
		}
	}

	.autocomplete {
		position: relative;
	}
	.configurationsTabView {
		display: flex;
	}
	
	#share-list {
		.user-list {
			max-height: 180px;
			overflow: auto;
			border: 1px solid var(--color-border);	
			margin-top: -3px;
			border-top: none;
			position: absolute;
			background: var(--color-main-background);
			z-index: 1;
			width: 99%;
		}
	}
</style>
