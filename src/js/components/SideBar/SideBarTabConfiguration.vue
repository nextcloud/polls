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
		<div class="config-box">
			<label v-if="writingPoll" class="icon-loading-small title"> {{ t('polls', 'Saving') }} </label>
			<label v-else class="icon-checkmark title"> {{ t('polls', 'Saved') }} </label>
		</div>

		<div v-if="acl.allowEdit" class="config-box">
			<label class="icon-sound title"> {{ t('polls', 'Title') }} </label>
			<input v-model="pollTitle" :class="{ error: titleEmpty }" type="text">
		</div>

		<div v-if="acl.allowEdit" class="config-box">
			<label class="icon-edit title"> {{ t('polls', 'Description') }} </label>
			<textarea v-model="pollDescription" />
		</div>

		<div class="config-box">
			<label class="title icon-category-customization"> {{ t('polls', 'Poll configurations') }} </label>

			<input id="allowMaybe" v-model="pollAllowMaybe"
				type="checkbox" class="checkbox">
			<label for="allowMaybe" class="title"> {{ t('polls', 'Allow "maybe" vote') }} </label>

			<input id="anonymous" v-model="pollAnonymous"
				type="checkbox" class="checkbox">
			<label for="anonymous" class="title"> {{ t('polls', 'Anonymous poll') }} </label>

			<input v-show="poll.anonymous" id="trueAnonymous" v-model="pollFullAnonymous"
				type="checkbox" class="checkbox">
			<label v-show="poll.anonymous" class="title" for="trueAnonymous"> {{ t('polls', 'Hide user names for admin') }} </label>

			<input id="expiration" v-model="pollExpiration"
				type="checkbox" class="checkbox">
			<label class="title" for="expiration"> {{ t('polls', 'Expires') }} </label>

			<DatePicker v-show="pollExpiration"
				v-model="pollExpire" v-bind="expirationDatePicker" style="width:170px" />
		</div>

		<div class="config-box">
			<label class="title icon-category-auth"> {{ t('polls', 'Access') }} </label>

			<input id="hidden" v-model="pollAccess" value="hidden"
				type="radio" class="radio">
			<label for="hidden" class="title">{{ t('polls', 'Hidden to other users') }} </label>

			<input id="public" v-model="pollAccess" value="public"
				type="radio" class="radio">
			<label for="public" class="title">{{ t('polls', 'Visible to other users') }} </label>
		</div>

		<button class="button btn primary" @click="switchDeleted()">
			<span v-if="poll.deleted">{{ t('polls', 'Restore poll') }}</span>
			<span v-else>{{ t('polls', 'Delete poll') }}</span>
		</button>
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import { mapState, mapMutations, mapActions } from 'vuex'

export default {
	name: 'SideBarTabConfiguration',

	data() {
		return {
			writingPoll: false,
			sidebar: false,
			titleEmpty: false,
			setExpiration: false
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl
		}),

		// Add bindings
		pollDescription: {
			get() {
				return this.poll.description
			},
			set(value) {
				this.writeValueDebounced({ description: value })
			}
		},

		pollTitle: {
			get() {
				return this.poll.title
			},
			set(value) {
				this.writeValueDebounced({ title: value })
			}
		},

		pollAccess: {
			get() {
				return this.poll.access
			},
			set(value) {
				this.writeValue({ access: value })
			}
		},

		pollExpire: {
			get() {
				return moment.unix(this.poll.expire)
			},
			set(value) {

				this.writeValue({ expire: moment(value).unix() })
			}
		},

		pollExpiration: {
			get() {
				return this.poll.expire
			},
			set(value) {
				if (value) {
					this.writeValue({ expire: moment().unix() })
				} else {
					this.writeValue({ expire: 0 })

				}
			}
		},

		pollFullAnonymous: {
			get() {
				return (this.poll.Fullanonymous > 0)
			},
			set(value) {
				this.writeValue({ fullAnonymous: value })
			}
		},

		pollAnonymous: {
			get() {
				return (this.poll.anonymous > 0)
			},
			set(value) {
				this.writeValue({ anonymous: value })
			}
		},

		pollAllowMaybe: {
			get() {
				return this.poll.allowMaybe
			},
			set(value) {
				this.writeValue({ allowMaybe: value })
				if (value) {
					this.writeValue({ options: ['yes', 'no', 'maybe'] })
				}
			}
		},

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

		...mapMutations(['setPollProperty']),
		...mapActions(['writePollPromise']),

		writeValueDebounced: debounce(function(e) {
			this.writeValue(e)
		}, 1500),

		writeValue(e) {
			this.$store.commit('setPollProperty', e)
			this.writingPoll = true
			this.writePoll()
		},

		switchDeleted() {
			if (this.poll.deleted) {
				this.writeValue({ deleted: 0 })
			} else {
				this.writeValue({ deleted: moment.utc().unix() })
			}

		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'), { type: 'success' })
			} else {
				this.$store.dispatch('writePollPromise')
					.then(() => {
						OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.poll.title), { type: 'success' })
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
