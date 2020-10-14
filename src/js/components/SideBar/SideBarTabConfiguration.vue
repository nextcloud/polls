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
		<ConfigBox v-if="!acl.isOwner" :title="t('polls', 'As an admin you may edit this poll')" icon-class="icon-checkmark" />

		<ConfigBox :title="t('polls', 'Title')" icon-class="icon-sound">
			<input v-model="pollTitle" :class="{ error: titleEmpty }" type="text">
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Description')" icon-class="icon-edit">
			<textarea v-model="pollDescription" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Poll configurations')" icon-class="icon-category-customization">
			<input v-if="acl.isOwner" id="adminAccess" v-model="pollAdminAccess"
				type="checkbox" class="checkbox">
			<label v-if="acl.isOwner" for="adminAccess"> {{ t('polls', 'Allow admins to edit this poll') }}</label>

			<input id="allowMaybe"
				v-model="pollAllowMaybe"
				type="checkbox"
				class="checkbox">
			<label for="allowMaybe"> {{ t('polls', 'Allow "maybe" vote') }}</label>

			<input id="anonymous"
				v-model="pollAnonymous"
				type="checkbox"
				class="checkbox">
			<label for="anonymous"> {{ t('polls', 'Anonymous poll') }}</label>
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Poll closing status')" :icon-class="closed ? 'icon-polls-closed' : 'icon-polls-open'">
			<ButtonDiv
				:icon="closed ? 'icon-polls-open' : 'icon-polls-closed'"
				:title="closed ? t('polls', 'Reopen poll'): t('polls', 'Close poll')"
				@click="switchClosed()" />

			<input v-show="!closed"
				id="expiration"
				v-model="pollExpiration"
				type="checkbox"
				class="checkbox">
			<label v-show="!closed" for="expiration"> {{ t('polls', 'Closing date') }}</label>

			<DatetimePicker v-show="pollExpiration && !closed" v-model="pollExpire" v-bind="expirationDatePicker" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Access')" icon-class="icon-category-auth">
			<input id="hidden"
				v-model="pollAccess"
				value="hidden"
				type="radio"
				class="radio">
			<label for="hidden">{{ t('polls', 'Hidden to other users') }}</label>

			<input id="public"
				v-model="pollAccess"
				value="public"
				type="radio"
				class="radio">
			<label for="public">{{ t('polls', 'Visible to other users') }}</label>

			<input id="important"
				v-model="pollImportant"
				type="checkbox"
				class="checkbox">
			<label for="important"> {{ t('polls', 'Relevant for all users') }}</label>
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Result display')" icon-class="icon-screen">
			<input id="always"
				v-model="pollShowResults"
				value="always"
				type="radio"
				class="radio">
			<label for="always">{{ t('polls', 'Always show results') }}</label>

			<input id="closed"
				v-model="pollShowResults"
				value="closed"
				type="radio"
				class="radio">
			<label for="closed">{{ t('polls', 'Hide results until poll is closed') }}</label>

			<input id="never"
				v-model="pollShowResults"
				value="never"
				type="radio"
				class="radio">
			<label for="never">{{ t('polls', 'Never show results') }}</label>
		</ConfigBox>

		<ButtonDiv :icon="poll.deleted ? 'icon-history' : 'icon-delete'"
			:title="poll.deleted ? t('polls', 'Restore poll') : t('polls', 'Delete poll')"
			@click="switchDeleted()" />
		<ButtonDiv v-if="poll.deleted"
			icon="icon-delete"
			class="error"
			:title="t('polls', 'Delete poll permanently')"
			@click="deletePermanently()" />
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import { mapGetters, mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import moment from '@nextcloud/moment'
import { DatetimePicker } from '@nextcloud/vue'
import ConfigBox from '../Base/ConfigBox'

export default {
	name: 'SideBarTabConfiguration',

	components: {
		DatetimePicker,
		ConfigBox,
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
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			closed: 'poll/closed',
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
					this.writeValue({ expire: moment().add(1, 'week').unix() })
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

		pollImportant: {
			get() {
				return (this.poll.important > 0)
			},
			set(value) {
				this.writeValue({ important: value })
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
				minuteStep: 5,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
				placeholder: t('polls', 'Closing date'),
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
	},

	methods: {

		writeValueDebounced: debounce(function(e) {
			this.writeValue(e)
		}, 1500),

		writeValue(e) {
			this.$store.commit('poll/setProperty', e)
			this.writingPoll = true
			this.updatePoll()
		},

		switchDeleted() {
			if (this.poll.deleted) {
				this.writeValue({ deleted: 0 })
			} else {
				this.writeValue({ deleted: moment.utc().unix() })
			}

		},

		switchClosed() {
			if (this.closed) {
				this.writeValue({ expire: 0 })
			} else {
				this.writeValue({ expire: moment.utc().unix() })
			}

		},

		deletePermanently() {
			if (!this.poll.deleted) return

			this.$store
				.dispatch('poll/delete', { pollId: this.poll.id })
				.then(() => {
					emit('update-polls')
				})
				.catch(() => {
					showError(t('polls', 'Error deleting poll.'))
				})
		},

		updatePoll() {
			if (this.titleEmpty) {
				showError(t('polls', 'Title must not be empty!'))
			} else {
				this.$store.dispatch('poll/update')
					.then((response) => {
						showSuccess(t('polls', '"{pollTitle}" successfully saved', { pollTitle: response.data.title }))
						emit('update-polls')
					})
					.catch(() => {
						showError(t('polls', 'Error writing poll'))
					})
				this.writingPoll = false
			}
		},

	},
}
</script>
