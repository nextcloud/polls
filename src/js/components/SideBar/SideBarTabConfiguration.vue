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
		<div class="configBox">
			<label v-if="writingPoll" class="icon-loading-small title">
				{{ t('polls', 'Saving') }}
			</label>
			<label v-else class="icon-checkmark title">
				{{ t('polls', 'Saved') }}
			</label>
		</div>

		<div v-if="acl.allowEdit" class="configBox">
			<label class="icon-sound title">
				{{ t('polls', 'Title') }}
			</label>
			<input v-model="eventTitle" :class="{ error: titleEmpty }" type="text">
		</div>

		<div v-if="acl.allowEdit" class="configBox">
			<label class="icon-edit title">
				{{ t('polls', 'Description') }}
			</label>
			<textarea v-model="eventDescription" />
			<!-- <textarea v-if="acl.allowEdit" :value="event.description" @input="updateDescription" /> -->
		</div>

		<div class="configBox">
			<label class="title icon-category-customization">
				{{ t('polls', 'Poll configurations') }}
			</label>

			<input id="allowMaybe"
				v-model="eventAllowMaybe"
				:disabled="!acl.allowEdit"
				type="checkbox"
				class="checkbox">
			<label for="allowMaybe" class="title">
				{{ t('polls', 'Allow "maybe" vote') }}
			</label>

			<input id="anonymous" v-model="eventIsAnonymous"
				:disabled="!acl.allowEdit"
				type="checkbox"
				class="checkbox">
			<label for="anonymous" class="title">
				{{ t('polls', 'Anonymous poll') }}
			</label>

			<input v-show="event.isAnonymous"
				id="trueAnonymous"
				v-model="eventFullAnonymous"
				:disabled="!acl.allowEdit"
				type="checkbox"
				class="checkbox">
			<label v-show="event.isAnonymous" class="title" for="trueAnonymous">
				{{ t('polls', 'Hide user names for admin') }}
			</label>

			<input id="expiration"
				v-model="eventExpiration"
				:disabled="!acl.allowEdit"
				type="checkbox"
				class="checkbox">
			<label class="title" for="expirtion">
				{{ t('polls', 'Expires') }}
			</label>

			<date-picker v-show="event.expire"
				v-model="eventExpiration"
				v-bind="expirationDatePicker"
				:disabled="!acl.allowEdit"
				:time-picker-options="{ start: '00:00', step: '00:05', end: '23:55' }"
				style="width:170px" />
		</div>

		<div class="configBox">
			<label class="title icon-category-auth">
				{{ t('polls', 'Access') }}
			</label>

			<input id="hidden"
				v-model="eventAccess"
				:disabled="!acl.allowEdit"
				type="radio"
				value="hidden"
				class="radio">
			<label for="hidden" class="title">
				<div class="title icon-category-security" />
				<span>{{ t('polls', 'Hidden to other users') }}</span>
			</label>

			<input id="public"
				v-model="eventAccess"
				:disabled="!acl.allowEdit"
				type="radio"
				value="public"
				class="radio">
			<label for="public" class="title">
				<div class="title icon-link" />
				<span>{{ t('polls', 'Visible to other users') }}</span>
			</label>
		</div>

		<button class="button btn primary" @click="$emit('deletePoll')">
			<span>{{ t('polls', 'Delete this poll') }}</span>
		</button>
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'

export default {
	name: 'SideBarTab',

	data() {
		return {
			writingPoll: false,
			sidebar: false,
			titleEmpty: false
		}
	},

	computed: {
		...mapState({
			event: state => state.event,
			acl: state => state.acl
		}),

		...mapGetters([
			'languageCodeShort'
		]),

		// Add bindings
		eventDescription: {
			get() {
				return this.event.description
			},
			set(value) {
				this.writeValueDebounced({ 'description': value })
			}
		},

		eventTitle: {
			get() {
				return this.event.title
			},
			set(value) {
				this.writeValueDebounced({ 'title': value })
			}
		},

		eventAccess: {
			get() {
				return this.event.access
			},
			set(value) {
				this.writeValue({ 'access': value })
			}
		},

		eventExpiration: {
			get() {
				return this.event.expire
			},
			set(value) {
				this.writeValue({ 'expire': value })
			}
		},

		eventFullAnonymous: {
			get() {
				return this.event.fullAnonymous
			},
			set(value) {
				this.writeValue({ 'fullAnonymous': value })
			}
		},

		eventIsAnonymous: {
			get() {
				return this.event.isAnonymous
			},
			set(value) {
				this.writeValue({ 'isAnonymous': value })
			}
		},

		eventAllowMaybe: {
			get() {
				return this.event.allowMaybe
			},
			set(value) {
				this.writeValue({ 'allowMaybe': value })
			}
		},

		// eventExpiration: {
		// 	get() {
		// 		return this.$store.state.event.expiration
		// 	},
		// 	set(value) {
		// 		this.writeValue({ 'expiration': value })
		// 	}
		// },

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
			} else if (this.acl.allowEdit) {
				return t('polls', 'Update poll')
			} else {
				return t('polls', 'Create new poll')
			}
		}
	},
	methods: {

		...mapMutations([ 'setEventProperty' ]),
		...mapActions([ 'writeEventPromise' ]),

		writeValueDebounced: debounce(function(e) {
			this.writeValue(e)
		}, 1500),

		writeValue(e) {
			this.$store.commit('setEventProperty', e)
			this.writingPoll = true
			this.writePoll()
		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'), { type: 'success' })
			} else {
				this.writeEventPromise()
				this.writingPoll = false
				OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.event.title), { type: 'success' })
			}
		},

		write() {
			if (this.acl.allowEdit) {
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
