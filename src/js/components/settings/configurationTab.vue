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
	<div>
		<button v-if="poll.mode === 'edit'" :disabled="writingPoll" class="button btn primary"
			@click="write()">
			<span>{{ saveButtonTitle }}</span>
			<span v-if="writingPoll" class="icon-loading-small" />
		</button>

		<div v-if="poll.mode === 'edit'" class="configBox">
			<label class="icon-sound title">
				{{ t('polls', 'Title')}}
			</label>
			<input v-model="eventTitle" :class="{ error: titleEmpty }" type="text">
		</div>

		<div v-if="poll.mode === 'edit'" class="configBox">
			<label class="icon-edit title">
				{{ t('polls', 'Description')}}
			</label>
			<textarea v-if="poll.mode === 'edit'" :value="event.description" @input="updateDescription" />
		</div>

		<div class="configBox">
			<label class="title icon-category-customization">
				{{ t('polls', 'Poll configurations') }}
			</label>

			<input id="allowMaybe"
				v-model="eventAllowMaybe"
				:disabled="protect"
				type="checkbox"
				class="checkbox">
			<label for="allowMaybe" class="title">
				{{ t('polls', 'Allow "maybe" vote') }}
			</label>

			<input id="anonymous"
				v-model="eventIsAnonymous"
				:disabled="protect"
				type="checkbox"
				class="checkbox">
			<label for="anonymous" class="title">
				{{ t('polls', 'Anonymous poll') }}
			</label>

			<input v-show="event.isAnonymous"
				id="trueAnonymous"
				v-model="eventFullAnonymous"
				:disabled="protect"
				type="checkbox"
				class="checkbox">
			<label v-show="event.isAnonymous" class="title" for="trueAnonymous">
				{{ t('polls', 'Hide user names for admin') }}
			</label>

			<input id="expiration"
				v-model="eventExpiration"
				:disabled="protect"
				type="checkbox"
				class="checkbox">
			<label class="title" for="expiration">
				{{ t('polls', 'Expires') }}
			</label>

			<date-picker v-show="event.expiration"
				v-model="eventExpirationDate"
				v-bind="expirationDatePicker"
				:disabled="protect"
				:time-picker-options="{ start: '00:00', step: '00:05', end: '23:55' }"
				style="width:170px" />
		</div>

		<div class="configBox">
			<label class="title icon-category-auth">
				{{ t('polls', 'Access') }}
			</label>
			<input id="private"
				v-model="eventAccess"
				:disabled="protect"
				type="radio"
				value="registered"
				class="radio">
			<label for="private" class="title">
				<div class="title icon-group" />
				<span>{{ t('polls', 'Registered users only') }}</span>
			</label>
			<input id="hidden"
				v-model="eventAccess"
				:disabled="protect"
				type="radio"
				value="hidden"
				class="radio">
			<label for="hidden" class="title">
				<div class="title icon-category-security" />
				<span>{{ t('polls', 'hidden') }}</span>
			</label>
			<input id="public"
				v-model="eventAccess"
				:disabled="protect"
				type="radio"
				value="public"
				class="radio">
			<label for="public" class="title">
				<div class="title icon-link" />
				<span>{{ t('polls', 'Public access') }}</span>
			</label>
			<input id="select"
				v-model="eventAccess"
				:disabled="protect"
				type="radio"
				value="select"
				class="radio">
			<label for="select" class="title">
				<div class="title icon-shared" />
				<span>{{ t('polls', 'Only shared') }}</span>
			</label>
		</div>

		<div v-if="!protect && event.type === 'datePoll'" class="configBox">
			<label class="title icon-calendar">
				{{ t('polls', 'Add a date option') }}
			</label>
			<date-picker v-bind="optionDatePicker" style="width:100%" confirm
				@change="addOption($event)" />
			<shift-dates />
		</div>
	</div>
</template>

<script>
import ShiftDates from '../create/datesShift'
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'

export default {
	name: 'ConfigurationTab',

	components: {
		ShiftDates
	},

	data() {
		return {
			// protect: false,
			nextPollDateId: 1,
			nextPollTextId: 1,
			writingPoll: false,
			sidebar: false,
			titleEmpty: false
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			event: state => state.event
		}),

		...mapGetters([ 'languageCodeShort' ]),

		// Add bindings
		eventTitle: {
			get() {
				return this.event.title
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'title': value })
			}
		},

		eventAccess: {
			get() {
				return this.event.access
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'access': value })
			}
		},

		eventExpiration: {
			get() {
				return this.event.expiration
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'expiration': value })
			}
		},

		eventFullAnonymous: {
			get() {
				return this.event.fullAnonymous
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'fullAnonymous': value })
			}
		},

		eventIsAnonymous: {
			get() {
				return this.event.isAnonymous
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'isAnonymous': value })
			}
		},

		eventAllowMaybe: {
			get() {
				return this.event.allowMaybe
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'allowMaybe': value })
			}
		},

		eventExpirationDate: {
			get() {
				return this.$store.state.event.expirationDate
			},
			set(value) {
				this.$store.commit('eventSetProperty', { 'expirationDate': value })
			}
		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 1,
				type: 'datetime',
				format: this.dateTimeFormat,
				lang: this.langShort,
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
		},

		protect: function() {
			return this.poll.mode === 'vote'
		},

		saveButtonTitle: function() {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				return t('polls', 'Update poll')
			} else {
				return t('polls', 'Create new poll')
			}
		}
	},
	methods: {

		...mapMutations([ 'eventSetProperty', 'pollSetProperty' ]),
		...mapActions([
			'writeOptionsPromise',
			'writeEventPromise'
		]),



		updateDescription(e) {
			this.$store.commit('eventSetProperty', { 'description': e.target.value })
		},

		addOption(option) {
			this.$store.dispatch({ type: 'addOption', option: option })
		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'), { type: 'success' })
			} else {
				this.writingPoll = true
				this.writeEventPromise()
				this.writeOptionsPromise()
				this.writingPoll = false
				OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.event.title), { type: 'success' })
			}
		},

		write() {
			if (this.poll.mode === 'edit') {
				this.writePoll()
			}

		}
	}
}
</script>

<style lang="scss">
	.configBox {
		display: flex;
		flex-direction: column;
		padding: 8px;
		& > * {
			padding-left: 21px;
		}

		& > input {
			margin-left: 24px;
			width: auto;

		}

		& > textarea {
			margin-left: 24px;
			width: auto;
			padding: 7px 6px;
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
</style>
