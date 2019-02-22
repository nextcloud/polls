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
	<div id="app-content">
		<controls :intitle="title">
			<template slot="after">
				<button :disabled="writingPoll" class="button btn primary" @click="writePoll(poll.mode)">
					<span>{{ saveButtonTitle }}</span>
					<span v-if="writingPoll" class="icon-loading-small" />
				</button>
				<button class="button symbol icon-settings" @click="switchSidebar" />
			</template>
		</controls>

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

				<date-picker v-show="poll.event.type === 'datePoll'"
					v-model="newPollDate"
					v-bind="optionDatePicker"
					style="width:100%"
					confirm
					@change="addNewPollDate"
				/>
				<button v-show="poll.event.type === 'datePoll'" id="shiftDates" class="icon-history"
					@click="shiftDates()"
				>
					{{ t('polls', 'Shift dates') }}
				</button>

				<modal-dialog>
					<div>
						<div class="selectUnit">
							<!-- <label for="interval">
								{{ t('polls', 'Shift all dates for ') }}
							</label> -->
							<input id="moveStep" v-model="move.step">
							<Multiselect id="unit" v-model="move.unit" :options="move.units" />
						</div>
					</div>
				</modal-dialog>

				<transitionGroup
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
				</transitionGroup>

				<div v-show="poll.event.type === 'textPoll'" id="poll-item-selector-text">
					<input v-model="newPollText" :placeholder=" t('polls', 'Add option') " @keyup.enter="addNewPollText()">
				</div>

				<transitionGroup
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
				</transitionGroup>
			</div>
		</div>

		<side-bar v-if="sidebar">
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
					<label for="datePoll" class="title">
						<span>{{ t('polls', 'Event schedule') }}</span>
					</label>
					<input id="textPoll" v-model="poll.event.type" :disabled="protect"
						value="textPoll" type="radio" class="radio"
					>
					<label for="textPoll" class="title">
						<span>{{ t('polls', 'Text based') }}</span>
					</label>
				</div>

				<div class="configBox ">
					<label class="title icon-settings">
						{{ t('polls', 'Poll configurations') }}
					</label>

					<input id="allowMaybe" v-model="poll.event.allowMaybe" :disabled="protect"
						type="checkbox" class="checkbox"
					>
					<label for="allowMaybe" class="title">
						{{ t('polls', 'Allow "maybe" vote') }}
					</label>

					<input id="anonymous" v-model="poll.event.isAnonymous" :disabled="protect"
						type="checkbox" class="checkbox"
					>
					<label for="anonymous" class="title">
						{{ t('polls', 'Anonymous poll') }}
					</label>

					<input v-show="poll.event.isAnonymous" id="trueAnonymous" v-model="poll.event.fullAnonymous"
						:disabled="protect" type="checkbox" class="checkbox"
					>
					<label v-show="poll.event.isAnonymous" class="title" for="trueAnonymous">
						{{ t('polls', 'Hide user names for admin') }}
					</label>

					<input id="expiration" v-model="poll.event.expiration" :disabled="protect"
						type="checkbox" class="checkbox"
					>
					<label class="title" for="expiration">
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
					<label for="private" class="title">
						<div class="title icon-group" />
						<span>{{ t('polls', 'Registered users only') }}</span>
					</label>
					<input id="hidden" v-model="poll.event.access" :disabled="protect"
						type="radio" value="hidden" class="radio"
					>
					<label for="hidden" class="title">
						<div class="title icon-category-security" />
						<span>{{ t('polls', 'hidden') }}</span>
					</label>
					<input id="public" v-model="poll.event.access" :disabled="protect"
						type="radio" value="public" class="radio"
					>
					<label for="public" class="title">
						<div class="title icon-link" />
						<span>{{ t('polls', 'Public access') }}</span>
					</label>
					<input id="select" v-model="poll.event.access" :disabled="protect"
						type="radio" value="select" class="radio"
					>
					<label for="select" class="title">
						<div class="title icon-shared" />
						<span>{{ t('polls', 'Only shared') }}</span>
					</label>
				</div>
			</div>

			<share-div	v-show="poll.event.access === 'select'"
				:active-shares="poll.shares"
				:placeholder="t('polls', 'Name of user or group')"
				:hide-names="true"
				@update-shares="updateShares"
				@remove-share="removeShare"
			/>
		</side-bar>
		<loading-overlay v-if="loadingPoll" />
	</div>
</template>

<script>
import moment from 'moment'
import sortBy from 'lodash/sortBy'
import { Multiselect } from 'nextcloud-vue'
import DatePollItem from '../components/datePollItem'
import TextPollItem from '../components/textPollItem'

export default {
	name: 'Create',

	components: {
		DatePollItem,
		TextPollItem,
		Multiselect
	},

	data() {
		return {
			move: {
				step: 1,
				unit: 'week',
				units: ['minute', 'hour', 'day', 'week', 'month', 'year']
			},
			poll: {
				mode: 'create',
				comments: [],
				votes: [],
				shares: [],
				grantedAs: 'owner',
				id: 0,
				result: 'new',
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
			return (this.poll.event.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
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
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
				lang: this.lang.split('-')[0],
				placeholder: t('polls', 'Expiration date'),
				timePickerOptions: {
					start: '00:00',
					step: '00:30',
					end: '23:30'
				}
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
					step: '00:30',
					end: '23:30'
				}
			}
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

		if (this.$route.name === 'create') {
			this.poll.event.owner = OC.getCurrentUser().uid
			this.loadingPoll = false
		} else if (this.$route.name === 'edit') {
			this.loadPoll(this.$route.params.hash)
			this.protect = true
			this.poll.mode = 'edit'
		} else if (this.$route.name === 'clone') {
			this.loadPoll(this.$route.params.hash)
		}
		if (window.innerWidth > 1024) {
			this.sidebar = true
		}
	},

	methods: {
		shiftDates() {
			var i = 0
			const params = {
				title: t('polls', 'Shift all date options'),
				text: t('polls', 'Shift all dates for '),
				buttonHideText: t('polls', 'Cancel'),
				buttonConfirmText: t('polls', 'Apply'),
				onConfirm: () => {
					for (i = 0; i < this.poll.options.pollDates.length; i++) {
						this.poll.options.pollDates[i].timestamp = parseInt(moment(this.poll.options.pollDates[i].timestamp * 1000).add(this.move.step, this.move.unit).format('X'))

					}

				}
			}

			this.$modal.show(params)

		},

		switchSidebar() {
			this.sidebar = !this.sidebar
		},

		getSystemValues() {
			this.$http.get(OC.generateUrl('apps/polls/get/system'))
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

		writePoll(mode) {
			if (mode !== '') {
				this.poll.mode = mode
			}
			if (this.poll.event.title.length === 0) {
				this.titleEmpty = true
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'))
			} else {
				this.writingPoll = true
				this.titleEmpty = false
				// this.poll.event.expirationDate = moment(this.poll.event.expirationDate).utc()

				this.$http.post(OC.generateUrl('apps/polls/write/poll'), this.poll)
					.then((response) => {
						this.poll.mode = 'edit'
						this.poll.event.hash = response.data.hash
						this.poll.event.id = response.data.id
						this.writingPoll = false
						OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.poll.event.title))
						// window.location.href = OC.generateUrl('apps/polls/edit/' + this.poll.event.hash)
					}, (error) => {
						this.poll.event.hash = ''
						this.writingPoll = false
						OC.Notification.showTemporary(t('polls', 'Error on saving poll, see console'))
						/* eslint-disable-next-line no-console */
						console.log(error.response)
					})
			}
		},

		loadPoll(hash) {
			this.loadingPoll = true
			this.$http.get(OC.generateUrl('apps/polls/get/poll/' + hash))
				.then((response) => {
					this.poll = response.data
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

					if (this.$route.name === 'clone') {
						this.poll.event.owner = OC.getCurrentUser().uid
						this.poll.event.title = t('polls', 'Clone of %n', 1, this.poll.event.title)
						this.poll.event.id = 0
						this.poll.id = 0
						this.poll.event.hash = ''
						this.poll.grantedAs = 'owner'
						this.poll.result = 'new'
						this.poll.mode = 'create'
						this.poll.comments = []
						this.poll.votes = []
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
#app-content {
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
			display: flex;
            background-position: 0 2px;
            padding-left: 24px;
            opacity: 0.7;
            font-weight: bold;
            margin-bottom: 4px;
			& > span {
				padding-left: 4px;
			}
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
.selectUnit {
	display: flex;
	align-items: center;
	flex-wrap: nowrap;
	> label {
		padding-right: 4px;
	}
}

#shiftDates {
	background-repeat: no-repeat;
	background-position: 10px center;
	min-width: 16px;
	min-height: 16px;
	padding: 10px;
	padding-left: 34px;
	text-align: left;
	margin: 0;
}
</style>
