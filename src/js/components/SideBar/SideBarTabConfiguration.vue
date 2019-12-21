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
			<label v-if="writingPoll" class="icon-loading-small title"> {{ t('polls', 'Saving') }} </label>
			<label v-else class="icon-checkmark title"> {{ t('polls', 'Saved') }} </label>
		</div>

		<div v-if="acl.allowEdit" class="configBox">
			<label class="icon-sound title"> {{ t('polls', 'Title') }} </label>
			<input v-model="eventTitle" :class="{ error: titleEmpty }" type="text">
		</div>

		<div v-if="acl.allowEdit" class="configBox">
			<label class="icon-edit title"> {{ t('polls', 'Description') }} </label>
			<textarea v-model="eventDescription" />
		</div>

		<div class="configBox">
			<label class="title icon-category-customization"> {{ t('polls', 'Poll configurations') }} </label>

			<input id="allowMaybe" v-model="eventAllowMaybe"
				type="checkbox" class="checkbox">
			<label for="allowMaybe" class="title"> {{ t('polls', 'Allow "maybe" vote') }} </label>

			<input id="anonymous" v-model="eventIsAnonymous"
				type="checkbox" class="checkbox">
			<label for="anonymous" class="title"> {{ t('polls', 'Anonymous poll') }} </label>

			<input v-show="event.isAnonymous" id="trueAnonymous" v-model="eventFullAnonymous"
				type="checkbox" class="checkbox">
			<label v-show="event.isAnonymous" class="title" for="trueAnonymous"> {{ t('polls', 'Hide user names for admin') }} </label>

			<input id="expiration" v-model="eventExpiration"
				type="checkbox" class="checkbox">
			<label class="title" for="expiration"> {{ t('polls', 'Expires') }} </label>

			<DatePicker v-show="eventExpiration"
				v-model="eventExpire" v-bind="expirationDatePicker" style="width:170px" />
		</div>

		<div class="configBox">
			<label class="title icon-category-auth"> {{ t('polls', 'Access') }} </label>

			<input id="hidden" v-model="eventAccess" value="hidden"
				type="radio" class="radio">
			<label for="hidden" class="title">{{ t('polls', 'Hidden to other users') }} </label>

			<input id="public" v-model="eventAccess" value="public"
				type="radio" class="radio">
			<label for="public" class="title">{{ t('polls', 'Visible to other users') }} </label>
		</div>

		<button class="button btn primary" @click="switchDeleted()">
			<span v-if="event.deleted">{{ t('polls', 'Restore poll') }}</span>
			<span v-else>{{ t('polls', 'Delete poll') }}</span>
		</button>
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import { mapState, mapMutations, mapActions } from 'vuex'

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

		eventExpire: {
			get() {
				return moment.utc(this.event.expire).local()
			},
			set(value) {
				this.writeValue({ 'expire': moment.local(value).utc().format() })
			}
		},

		eventExpiration: {
			get() {
				return this.event.expiration
			},
			set(value) {
				this.writeValue({ 'expiration': value })
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

		langPicker() {
			return {
				formatLocale: {
					months: moment.months(),
					monthsShort: moment.monthsShort(),
					weekdays: moment.weekdays(),
					weekdaysMin: moment.weekdaysMin(),
					firstDayOfWeek: moment.localeData()._week.dow
				}
			}
		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 1,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),

				// TODO: use this for version 2.x
				lang: OC.getLanguage().split('-')[0],
				firstDayOfWeek: moment.localeData()._week.dow,

				// TODO: use this from version 3.x on
				// lang: {
				// 	formatLocale: {
				//		firstDayOfWeek: moment.localeData()._week.dow,
				// 		months: moment.months(),
				// 		monthsShort: moment.monthsShort(),
				// 		weekdays: moment.weekdays(),
				// 		weekdaysMin: moment.weekdaysMin()
				// 	}
				// },
				placeholder: t('polls', 'Expiration date'),
				timePickerOptions: {
					start: '00:00',
					step: '01:00',
					end: '23:30'
				}
			}
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

		switchDeleted() {
			this.writeValue({ 'deleted': !this.event.deleted })

		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'), { type: 'success' })
			} else {
				this.$store.dispatch('writeEventPromise')
					.then(() => {
						OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.event.title), { type: 'success' })
						this.$root.$emit('updatePolls')
					})
				this.writingPoll = false
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
