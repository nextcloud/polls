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
		<div v-if="!acl.isOwner" class="config-box">
			<label class="icon-checkmark title"> {{ t('polls', 'As an admin you may edit this poll') }} </label>
		</div>

		<div class="config-box">
			<label class="icon-sound title"> {{ t('polls', 'Title') }} </label>
			<input v-model="pollTitle" :class="{ error: titleEmpty }" type="text">
		</div>

		<div class="config-box">
			<label class="icon-edit title"> {{ t('polls', 'Description') }} </label>
			<textarea v-model="pollDescription" />
		</div>

		<div class="config-box">
			<label class="title icon-category-customization"> {{ t('polls', 'Poll configurations') }} </label>

			<input v-if="acl.isOwner" id="adminAccess" v-model="pollAdminAccess"
				type="checkbox" class="checkbox">
			<label v-if="acl.isOwner" for="adminAccess" class="title"> {{ t('polls', 'Allow admins to edit this poll') }} </label>

			<input id="allowMaybe" v-model="pollAllowMaybe"
				type="checkbox" class="checkbox">
			<label for="allowMaybe" class="title"> {{ t('polls', 'Allow "maybe" vote') }} </label>

			<input id="anonymous" v-model="pollAnonymous"
				type="checkbox" class="checkbox">
			<label for="anonymous" class="title"> {{ t('polls', 'Anonymous poll') }} </label>

			<input id="expiration" v-model="pollExpiration"
				type="checkbox" class="checkbox">
			<label class="title" for="expiration"> {{ t('polls', 'Expires') }} </label>

			<DatetimePicker v-show="pollExpiration"
				v-model="pollExpire" v-bind="expirationDatePicker" />
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

		<div class="config-box">
			<label class="title icon-screen"> {{ t('polls', 'Result display') }} </label>

			<input id="always" v-model="pollShowResults" value="always"
				type="radio" class="radio">
			<label for="always" class="title">{{ t('polls', 'Always show results') }} </label>

			<input id="expired" v-model="pollShowResults" value="expired"
				type="radio" class="radio">
			<label for="expired" class="title">{{ t('polls', 'Hide results until poll is expired') }} </label>

			<input id="never" v-model="pollShowResults" value="never"
				type="radio" class="radio">
			<label for="never" class="title">{{ t('polls', 'Never show results') }} </label>
		</div>

		<ButtonDiv :icon="poll.deleted ? 'icon-history' : 'icon-delete'" :title="poll.deleted ? t('polls', 'Restore poll') : t('polls', 'Delete poll')"
			@click="switchDeleted()" />
		<ButtonDiv v-if="poll.deleted" icon="icon-delete" class="error"
			:title="t('polls', 'Delete poll permanently')"
			@click="deletePermanently()" />
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import { mapState, mapMutations, mapActions } from 'vuex'
import { DatetimePicker } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'

export default {
	name: 'SideBarTabConfiguration',

	components: {
		DatetimePicker,
	},

	data() {
		return {
			writingPoll: false,
			sidebar: false,
			titleEmpty: false,
			setExpiration: false,
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl,
		}),

		// Add bindings
		pollDescription: {
			get() {
				return this.poll.description
			},
			set(value) {
				this.writeValueDebounced({ description: value })
			},
		},

		pollTitle: {
			get() {
				return this.poll.title
			},
			set(value) {
				this.writeValueDebounced({ title: value })
			},
		},

		pollAccess: {
			get() {
				return this.poll.access
			},
			set(value) {
				this.writeValue({ access: value })
			},
		},

		pollShowResults: {
			get() {
				return this.poll.showResults
			},
			set(value) {
				this.writeValue({ showResults: value })
			},
		},

		pollExpire: {
			get() {
				return moment.unix(this.poll.expire)._d
			},
			set(value) {

				this.writeValue({ expire: moment(value).unix() })
			},
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
			},
		},

		pollAnonymous: {
			get() {
				return (this.poll.anonymous > 0)
			},
			set(value) {
				this.writeValue({ anonymous: value })
			},
		},

		pollAdminAccess: {
			get() {
				return (this.poll.adminAccess > 0)
			},
			set(value) {
				this.writeValue({ adminAccess: value })
			},
		},

		pollAllowMaybe: {
			get() {
				return this.poll.allowMaybe
			},
			set(value) {
				this.writeValue({ allowMaybe: value })
			},
		},

		firstDOW() {
			// vue2-datepicker needs 7 for sunday
			if (moment.localeData()._week.dow === 0) {
				return 7
			} else {
				return moment.localeData()._week.dow
			}
		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 1,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
				placeholder: t('polls', 'Expiration date'),
				confirm: true,
				lang: {
					formatLocale: {
						firstDayOfWeek: this.firstDOW,
						months: moment.months(),
						monthsShort: moment.monthsShort(),
						weekdays: moment.weekdays(),
						weekdaysMin: moment.weekdaysMin(),
					},
				},
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
		},
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

		deletePermanently() {
			if (!this.poll.deleted) return

			this.$store
				.dispatch('deletePermanently', { pollId: this.poll.id })
				.then((response) => {
					this.$router.push({ name: 'list', params: { type: 'deleted' } })
				})
		},

		writePoll() {
			if (this.titleEmpty) {
				OC.Notification.showTemporary(t('polls', 'Title must not be empty!'), { type: 'success' })
			} else {
				this.$store.dispatch('writePollPromise')
					.then(() => {
						OC.Notification.showTemporary(t('polls', '%n successfully saved', 1, this.poll.title), { type: 'success' })
						emit('update-polls')
					})
				this.writingPoll = false
			}
		},

		write() {
			if (this.acl.allowEdit) {
				this.writePoll()
			}

		},
	},
}
</script>
