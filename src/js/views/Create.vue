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
	<app-content>
		<div class="main-container">
			<controls :intitle="event.title" />

			<div class="workbench">
				<div>
					<h2>{{ t('polls', 'Poll description') }}</h2>

					<label>{{ t('polls', 'Title') }}</label>
					<input id="pollTitle" v-model="eventTitle" :class="{ error: titleEmpty }"
						type="text"
					>

					<label>{{ t('polls', 'Description') }}</label>
					<textarea id="pollDesc" :value="event.description" @input="updateDescription" />
				</div>

				<div>
					<h2>{{ t('polls', 'Vote options') }}</h2>

					<date-picker v-show="event.type === 'datePoll'" v-bind="optionDatePicker" style="width:100%"
						confirm @change="addNewPollDate($event)"
					/>

					<transitionGroup v-show="event.type === 'datePoll'" id="date-poll-list" name="list"
						tag="ul" class="poll-table"
					>
						<date-poll-item v-for="(option, index) in sortedOptions" :key="option.id" :option="option"
							@remove="options.splice(index, 1)"
						/>
					</transitionGroup>

					<div v-show="event.type === 'textPoll'" id="poll-item-selector-text">
						<input v-model="newPollText" :placeholder=" t('polls', 'Add option') " @keyup.enter="addNewPollText(newPollText)">
					</div>

					<transitionGroup v-show="event.type === 'textPoll'" id="text-poll-list" name="list"
						tag="ul" class="poll-table"
					>
						<text-poll-item v-for="(option, index) in options" :key="option.id" :option="option"
							@remove="options.splice(index, 1)"
						/>
					</transitionGroup>
				</div>
			</div>
		</div>

		<app-sidebar :title="t('polls', 'Settings')">
			<template slot="primary-actions">
				<button :disabled="writingPoll" class="button btn primary" :class="{ warning: adminMode }"
					@click="writePoll()"
				>
					<span>{{ saveButtonTitle }}</span>
					<span v-if="writingPoll" class="icon-loading-small" />
				</button>
			</template>

			<app-sidebar-tab :name="t('polls', 'Configuration')" icon="icon-settings">
				<configuration-tab />
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Information')" icon="icon-info">
				<information-tab />
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Comments')" icon="icon-comment">
				<comments-tab />
			</app-sidebar-tab>

			<app-sidebar-tab :name="t('polls', 'Invitations')" icon="icon-share">
				<div />
			</app-sidebar-tab>
		</app-sidebar>
		<loading-overlay v-if="loading" />
	</app-content>
</template>

<script>
// import { Multiselect } from 'nextcloud-vue'
import DatePollItem from '../components/datePoll/createItem'
import TextPollItem from '../components/textPoll/createItem'
import InformationTab from '../components/tabs/information'
import ConfigurationTab from '../components/tabs/configuration'
import CommentsTab from '../components/tabs/comments'
import ShiftDates from '../components/datesShift'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'

export default {
	name: 'Create',

	components: {
		DatePollItem,
		TextPollItem,
		InformationTab,
		ConfigurationTab,
		CommentsTab,
		ShiftDates
		// Multiselect
	},

	data() {
		return {
			// move: {
			// 	step: 1,
			// 	unit: 'week',
			// 	units: ['minute', 'hour', 'day', 'week', 'month', 'year']
			// },
			newPollDate: '',
			newPollTime: '',
			newPollText: '',
			nextPollDateId: 1,
			nextPollTextId: 1,
			protect: false,
			writingPoll: false,
			sidebar: false
		}
	},

	computed: {
		// Add store mappings
		...mapState({
			poll: state => state.poll,
			comments: state => state.poll.comments,
			event: state => state.event,
			shares: state => state.poll.shares,
			options: state => state.options
		}),

		// Add bindings
		eventTitle: {
			get() {
				return this.event.title
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'property': 'title', 'value': value })
			}
		},

		...mapGetters([
			'accessType',
			// 'countParticipants',
			'sortedOptions',
			'longDateFormat',
			'dateTimeFormat',
			'languageCode',
			'languageCodeShort',
			'localeCode'
		]),

		adminMode() {
			return (this.event.owner !== OC.getCurrentUser().uid && OC.isUserAdmin())
		},

		// Local computed
		voteUrl() {
			return OC.generateUrl('apps/polls/poll/') + this.event.hash
		},

		titleEmpty() {
			return (this.event.title.trim().length === 0)
		},

		title() {
			if (this.event.title === '') {
				return t('polls', 'Create new poll')
			} else {
				return this.event.title

			}
		},

		saveButtonTitle() {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				if (this.adminMode) {
					return t('polls', 'Update poll as admin')
				} else {
					return t('polls', 'Update poll')
				}

			} else {
				return t('polls', 'Create new poll')
			}
		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 1,
				type: 'datetime',
				format: this.dateTimeFormat,
				lang: this.languageCodeShort,
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
				format: this.dateTimeFormat,
				lang: this.languageCodeShort,
				placeholder: t('polls', 'Click to add a date'),
				timePickerOptions: {
					start: '00:00',
					step: '00:30',
					end: '23:30'
				}
			}
		}

	},

	// watch: {
	// 	title() {
	// 		// only used when the title changes after page load
	// 		document.title = t('polls', 'Polls') + ' - ' + this.title
	// 	}
	// },

	created() {
		this.loadPoll({
			hash: this.$route.params.hash,
			mode: this.$route.name
		})

		this.sidebar = (window.innerWidth > 1024)
	},

	methods: {
		...mapMutations([
			'eventSetProperty',
			'dateAdd',
			// 'datesShift',
			'setLocale'
		]),

		...mapMutations({
			addNewPollDate: 'dateAdd',
			addNewPollText: 'textAdd'
		}),

		...mapActions([
			'addShare',
			'loadPoll',
			'updateShares',
			'removeShare',
			'writePollPromise'
		]),

		// updateTitle(e) {
		// 	this.$store.commit('eventSetProperty', { 'property': 'title', 'value': e.target.value })
		// },

		updateDescription(e) {
			this.$store.commit('eventSetProperty', { 'property': 'description', 'value': e.target.value })
		},

		switchSidebar() {
			this.sidebar = !this.sidebar
		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'))
			} else {
				this.writingPoll = true
				this.writePollPromise()
				this.writingPoll = false
				OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.event.title))
				// this.writingPoll = false
				// OC.Notification.showTemporary(t('polls', 'Error on saving poll, see console'))
			}
		}
	}
}

</script>

<style lang="scss" scoped>
#app-content {
    // input.hasTimepicker {
    //     width: 75px;
    // }
}

#body-user button.btn.primary.warning {
	// override button style with warning colors
	padding: 6px 22px;
	border-radius: var(--border-radius-pill);
	border-color: var(--color-warning);
}

.polls-content {
    display: flex;
    padding-top: 45px;
    flex: 1;
}

input[type="text"] {
    display: block;
    width: 100%;
}

.workbench {
    margin-top: 45px;
    display: flex;
	justify-content: center;
    flex: 1;
    flex-wrap: wrap;
    overflow-x: hidden;

    > div {
        min-width: 245px;
        max-width: 540px;
        display: flex;
        flex-direction: column;
        flex: 1;
        padding: 8px;
    }
}

input,
textarea {
    &.error {
        border: 2px solid var(--color-error);
        box-shadow: 1px 0 var(--border-radius) var(--color-box-shadow);
    }
}

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
            flex: 1;
            font-size: 1.2em;
            opacity: 0.7;
            white-space: normal;
            padding-right: 4px;
            &.avatar {
                flex: 0;
            }
        }

        > div:nth-last-child(1) {
            justify-content: center;
            flex: 0 0;
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

#datesShift {
	background-repeat: no-repeat;
	background-position: 10px center;
	min-width: 16px;
	min-height: 16px;
	padding: 10px;
	padding-left: 34px;
	text-align: left;
	margin: 0;
}

#pollDesc {
	resize: vertical;
	width: 100%;
}

</style>
