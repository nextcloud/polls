<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div id="create-poll">
		<Controls :index-page="indexPage" :intitle="title">
			<button :disabled="writingPoll" class="button btn primary" @click="writePoll(poll.mode)">
				<span>{{ saveButtonTitle }}</span>
				<span v-if="writingPoll" class="icon-loading-small" />
			</button>
			<button class="button" @click="switchSidebar">
				<span class="symbol icon-settings" />
			</button>
		</Controls>

		<div class="workbench">
			<div>
				<h2>{{ t('polls', 'Poll description') }}</h2>

				<label>{{ t('polls', 'Title') }}</label>
				<input id="pollTitle" v-model="poll.event.title" :class="{ error: titleEmpty }"
					type="text"
				>

				<label>{{ t('polls', 'Description') }}</label>
				<textarea id="pollDesc" v-model="poll.event.description" style="resize: vertical; 	width: 100%;" />
			</div>

			<div>
				<h2>{{ t('polls', 'Vote options') }}</h2>

				<div v-if="poll.mode == 'create'">
					<input id="datePoll" v-model="poll.event.type" :disabled="protect"
						value="datePoll" type="radio" class="radio"
					>
					<label for="datePoll">
						{{ t('polls', 'Event schedule') }}
					</label>
					<input id="textPoll" v-model="poll.event.type" :disabled="protect"
						value="textPoll" type="radio" class="radio"
					>
					<label for="textPoll">
						{{ t('polls', 'Text based') }}
					</label>
				</div>

				<DatePicker v-show="poll.event.type === 'datePoll'"
					v-model="newPollDate"
					v-bind="optionDatePicker"
					style="width:100%"
					confirm
					@change="addNewPollDate"
				/>

				<TransitionGroup
					v-show="poll.event.type === 'datePoll'"
					id="date-poll-list"
					name="list"
					tag="ul"
					class="poll-table"
				>
					<li
						is="date-poll-item"
						v-for="(pollDate, index) in poll.options.pollDates"
						:key="pollDate.id"
						:option="pollDate"
						@remove="poll.options.pollDates.splice(index, 1)"
					/>
				</TransitionGroup>

				<div v-show="poll.event.type === 'textPoll'" id="poll-item-selector-text">
					<input v-model="newPollText" :placeholder=" t('polls', 'Add option') " @keyup.enter="addNewPollText()">
				</div>

				<TransitionGroup
					v-show="poll.event.type === 'textPoll'"
					id="text-poll-list"
					name="list"
					tag="ul"
					class="poll-table"
				>
					<li
						is="text-poll-item"
						v-for="(pollText, index) in poll.options.pollTexts"
						:key="pollText.id"
						:option="pollText"
						@remove="poll.options.pollTexts.splice(index, 1)"
					/>
				</TransitionGroup>
			</div>
		</div>

		<SideBar v-if="sidebar">
			<div v-if="adminMode" class="warning">
				{{ t('polls', 'You are editing in admin mode') }}
			</div>
			<UserDiv :user-id="poll.event.owner" :description="t('polls', 'Owner')" />

			<ul class="tabHeaders">
				<li class="tabHeader selected" data-tabid="configurationsTabView" data-tabindex="0">
					<a href="#">
						{{ t('polls', 'Configuration') }}
					</a>
				</li>
			</ul>

			<div v-if="protect">
				<span>{{ t('polls', 'Configuration is locked. Changing options may result in unwanted behaviour, but you can unlock it anyway.') }}</span>
				<button @click="protect=false">
					{{ t('polls', 'Unlock configuration ') }}
				</button>
			</div>
			<div id="configurationsTabView" class="tab">
				<div v-if="poll.mode =='edit'" class="configBox">
					<label class="title icon-checkmark">
						{{ t('polls', 'Poll type') }}
					</label>
					<input id="datePoll" v-model="poll.event.type" :disabled="protect"
						value="datePoll" type="radio" class="radio"
					>
					<label for="datePoll">
						{{ t('polls', 'Event schedule') }}
					</label>
					<input id="textPoll" v-model="poll.event.type" :disabled="protect"
						value="textPoll" type="radio" class="radio"
					>
					<label for="textPoll">
						{{ t('polls', 'Text based') }}
					</label>
				</div>

				<div class="configBox ">
					<label class="title icon-settings">
						{{ t('polls', 'Poll configurations') }}
					</label>

					<input id="allowMaybe" v-model="poll.event.allowMaybe" :disabled="protect"
						type="checkbox" class="checkbox"
					>
					<label for="allowMaybe">
						{{ t('polls', 'Allow "maybe" vote') }}
					</label>

					<input id="anonymous" v-model="poll.event.isAnonymous" :disabled="protect"
						type="checkbox" class="checkbox"
					>
					<label for="anonymous">
						{{ t('polls', 'Anonymous poll') }}
					</label>

					<input v-show="poll.event.isAnonymous" id="trueAnonymous" v-model="poll.event.fullAnonymous"
						:disabled="protect" type="checkbox" class="checkbox"
					>
					<label v-show="poll.event.isAnonymous" for="trueAnonymous">
						{{ t('polls', 'Hide user names for admin') }}
					</label>

					<input id="expiration" v-model="poll.event.expiration" :disabled="protect"
						type="checkbox" class="checkbox"
					>
					<label for="expiration">
						{{ t('polls', 'Expires') }}
					</label>

					<DatePicker v-show="poll.event.expiration"
						v-model="poll.event.expirationDate"
						v-bind="expirationDatePicker"
						:disabled="protect"
						:time-picker-options="{ start: '00:00', step: '00:05', end: '23:55' }"
						style="width:170px"
					/>
				</div>

				<div class="configBox">
					<label class="title icon-user">
						{{ t('polls', 'Access') }}
					</label>
					<input id="private" v-model="poll.event.access" :disabled="protect"
						type="radio" value="registered" class="radio"
					>
					<label for="private">
						{{ t('polls', 'Registered users only') }}
					</label>
					<input id="hidden" v-model="poll.event.access" :disabled="protect"
						type="radio" value="hidden" class="radio"
					>
					<label for="hidden">
						{{ t('polls', 'hidden') }}
					</label>
					<input id="public" v-model="poll.event.access" :disabled="protect"
						type="radio" value="public" class="radio"
					>
					<label for="public">
						{{ t('polls', 'Public access') }}
					</label>
					<input id="select" v-model="poll.event.access" :disabled="protect"
						type="radio" value="select" class="radio"
					>
					<label for="select">
						{{ t('polls', 'Only shared') }}
					</label>
				</div>
			</div>

			<ShareDiv	v-show="poll.event.access === 'select'"
				:active-shares="poll.shares"
				:placeholder="t('polls', 'Name of user or group')"
				hide-names="true"
				@update-shares="updateShares"
				@remove-share="removeShare"
			/>
		</SideBar>
		<div v-if="loadingPoll" class="loading-overlay">
			<span class="icon-loading" />
		</div>
	</div>
</template>

<script>
import axios from 'nextcloud-axios'
import moment from 'moment'
import sortBy from 'lodash/sortBy'
import DatePollItem from './components/datePollItem.vue'
import TextPollItem from './components/textPollItem.vue'

export default {
	name: 'CreatePoll',

	components: {
		'DatePollItem': DatePollItem,
		'TextPollItem': TextPollItem
	},

	data() {
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
					created: '',
					access: 'public',
					expiration: false,
					expirationDate: '',
					expired: false,
					isAnonymous: false,
					fullAnonymous: false,
					allowMaybe: false,
					owner: undefined
				},
				options: {
					pollDates: [],
					pollTexts: []
				}
			},
			system: [],
			lang: '',
			locale: '',
			placeholder: '',
			newPollDate: '',
			newPollTime: '',
			newPollText: '',
			nextPollDateId: 1,
			nextPollTextId: 1,
			protect: false,
			writingPoll: false,
			loadingPoll: true,
			sidebar: false,
			titleEmpty: false,
			indexPage: '',
			longDateFormat: '',
			dateTimeFormat: ''
		}
	},

	computed: {
		adminMode() {
			return (this.poll.event.owner !== OC.getCurrentUser().uid)
		},

		langShort() {
			return this.lang.split('-')[0]
		},

		title() {
			if (this.poll.event.title === '') {
				return t('polls', 'Create new poll')
			} else {
				return this.poll.event.title

			}
		},

		saveButtonTitle() {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				return t('polls', 'Update poll')
			} else {
				return t('polls', 'Create new poll')
			}
		},

		localeData() {
			return moment.localeData(moment.locale(this.locale))
		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 1,
				type: 'datetime',
				lang: this.lang.split('-')[0],
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
				placeholder: t('polls', 'Expiration date')
			}
		},

		optionDatePicker() {
			return {
				editable: false,
				minuteStep: 1,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
				lang: this.lang.split('-')[0],
				placeholder: t('polls', 'Click to add a date'),
				timePickerOptions: {
					start: '00:00',
					step: '00:05',
					end: '23:55'
				}
			}
		},

		isPollValid() {
			return !this.titleEmpty
		}

	},

	watch: {
		title() {
			// only used when the title changes after page load
			document.title = t('polls', 'Polls') + ' - ' + this.title
		}
	},

	created() {
		this.indexPage = OC.generateUrl('apps/polls/')
		this.getSystemValues()
		this.lang = OC.getLanguage()
		try {
			this.locale = OC.getLocale()
		} catch (e) {
			if (e instanceof TypeError) {
				this.locale = this.lang
			} else {
				/* eslint-disable-next-line no-console */
				console.log(e)
			}
		}
		moment.locale(this.locale)
		this.longDateFormat = moment.localeData().longDateFormat('L')
		this.dateTimeFormat = moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT')
		var urlArray = window.location.pathname.split('/')

		if (urlArray[urlArray.length - 1] === 'create') {
			this.poll.event.owner = OC.getCurrentUser().uid
			this.loadingPoll = false
		} else {
			this.loadPoll(urlArray[urlArray.length - 1])
			this.protect = true
			this.poll.mode = 'edit'
		}
		if (window.innerWidth > 1024) {
			this.sidebar = true
		}
	},

	methods: {

		switchSidebar() {
			this.sidebar = !this.sidebar
		},

		getSystemValues() {
			axios.get(OC.generateUrl('apps/polls/get/system'))
				.then((response) => {
					this.system = response.data.system
				}, (error) => {
					this.poll.event.hash = ''
					/* eslint-disable-next-line no-console */
					console.log(error.response)
				})
		},

		addShare(item) {
			this.poll.shares.push(item)
		},

		updateShares(share) {
			this.poll.shares = share.slice(0)
		},

		removeShare(item) {
			this.poll.shares.splice(this.poll.shares.indexOf(item), 1)
		},

		addNewPollDate(newPollDate) {
			if (newPollDate != null) {
				this.newPollDate = moment(newPollDate)
				this.poll.options.pollDates.push({
					id: this.nextPollDateId++,
					timestamp: moment(newPollDate).unix()
				})
				this.poll.options.pollDates = sortBy(this.poll.options.pollDates, 'timestamp')
			}
		},

		addNewPollText() {
			if (this.newPollText !== null & this.newPollText !== '') {
				this.poll.options.pollTexts.push({
					id: this.nextPollTextId++,
					text: this.newPollText
				})
			}
			this.newPollText = ''
		},

		validatePoll() {
			this.titleEmpty = (this.poll.event.title.length === 0)
		},

		writePoll(mode) {
			if (mode !== '') {
				this.poll.mode = mode
			}
			this.validatePoll()
			if (this.isPollValid) {
				this.writingPoll = true
				axios.post(OC.generateUrl('apps/polls/write'), this.poll)
					.then((response) => {
						this.poll.mode = 'edit'
						this.poll.event.hash = response.data.hash
						this.poll.event.id = response.data.id
						this.writingPoll = false
						// window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash)
					}, (error) => {
						this.poll.event.hash = ''
						/* eslint-disable-next-line no-console */
						console.log(error.response)
					})
			}
		},

		loadPoll(hash) {
			this.loadingPoll = true
			axios.get(OC.generateUrl('apps/polls/get/poll/' + hash))
				.then((response) => {
					this.poll = response.data.poll
					if (this.poll.event.expirationDate !== null) {
						this.poll.event.expirationDate = new Date(moment.utc(this.poll.event.expirationDate))
					} else {
						this.poll.event.expirationDate = ''
					}

					if (this.poll.event.type === 'datePoll') {
						var i
						for (i = 0; i < this.poll.options.pollTexts.length; i++) {
							this.addNewPollDate(new Date(moment.utc(this.poll.options.pollTexts[i].text)))
						}
						this.poll.options.pollTexts = []
					}
					this.loadingPoll = false
					this.newPollDate = ''
					this.newPollText = ''
				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
					this.poll.event.hash = ''
					this.loadingPoll = false
				})
		}
	}
}
</script>

<style lang="scss">
#create-poll {
    width: 100%;
    display: flex;
    input.hasTimepicker {
        width: 75px;
    }
}

.warning {
    color: var(--color-error);
    font-weight: bold;
}

.polls-content {
    display: flex;
    padding-top: 45px;
    flex-grow: 1;
}

input[type="text"] {
    display: block;
    width: 100%;
}

.workbench {
    margin-top: 45px;
    display: flex;
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

.loading-overlay {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: #fff;
    opacity: 0.9;
    z-index: 1001;
    .icon-loading {
        position: fixed;
        left: 50%;
        top: 50%;
        margin-left: -35px;
        margin-top: -10px;
        &::after {
            border: 10px solid var(--color-loading-light);
            border-top-color: var(--color-primary-element);
            height: 70px;
            width: 70px;
        }
    }
}

.polls-sidebar {
    margin-top: 45px;
    width: 40%;

    .configBox {
        display: flex;
        flex-direction: column;
        padding: 8px;
        & > * {
            padding-left: 21px;
        }
        & > .title {
            background-position: 0 2px;
            padding-left: 24px;
            opacity: 0.7;
            font-weight: bold;
            margin-bottom: 4px;
        }
    }
}

input,
textarea {
    &.error {
        border: 2px solid var(--color-error);
        box-shadow: 1px 0 var(--border-radius) var(--color-box-shadow);
    }
}

/* Transitions for inserting and removing list items */
.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}

.list-enter,
.list-leave-to {
    opacity: 0;
}

.list-move {
    transition: transform 0.5s;
}
/*  */

#poll-item-selector-text {
    > input {
        width: 100%;
    }
}

.poll-table {
    > li {
        display: flex;
        align-items: center;
        padding-left: 8px;
        padding-right: 8px;
        line-height: 2em;
        min-height: 4em;
        border-bottom: 1px solid var(--color-border);
        overflow: hidden;
        white-space: nowrap;

        &:active,
        &:hover {
            transition: var(--background-dark) 0.3s ease;
            background-color: var(--color-background-dark); //$hover-color;
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
    &.button-inline {
        border: 0;
        background-color: transparent;
    }
}

.tab {
    display: flex;
    flex-wrap: wrap;
}
</style>
