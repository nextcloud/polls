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
		<div v-if="participantsVoted" class="warning">
			{{ t('polls', 'Please be careful when changing options, because it can affect existing votes in an unwanted manner.') }}
		</div>

		<ConfigBox v-if="!acl.isOwner" :title="t('polls', 'As an admin you may edit this poll')" icon-class="icon-checkmark" />

		<ConfigBox :title="t('polls', 'Title')" icon-class="icon-sound">
			<input v-model="pollTitle" :class="{ error: titleEmpty }" type="text">
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Description')" icon-class="icon-edit">
			<textarea v-model="pollDescription" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Poll configurations')" icon-class="icon-category-customization">
      <CheckBoxDiv v-model="allowComment" :label="t('polls', 'Allow Comments')" />
      <CheckBoxDiv v-model="pollAllowMaybe" :label="t('polls', 'Allow \'maybe\' vote')" />

			<div v-if="(useVoteLimit || useOptionLimit) && pollAllowMaybe" class="indented warning">
				{{ t('polls', 'If vote limits are used, \'maybe\' shouldn\'t be allowed.') }}
			</div>

			<CheckBoxDiv v-model="pollAnonymous" :label="t('polls', 'Anonymous poll')" />
			<CheckBoxDiv v-model="useVoteLimit" :label="t('polls', 'Limit yes votes per user')" />
			<InputDiv v-if="pollVoteLimit" v-model="pollVoteLimit" class="selectUnit indented"
				use-num-modifiers
				@add="pollVoteLimit++"
				@substract="pollVoteLimit--" />

			<CheckBoxDiv v-model="useOptionLimit" :label="t('polls', 'Limit yes votes per option')" />
			<InputDiv v-if="pollOptionLimit" v-model="pollOptionLimit" class="selectUnit indented"
				use-num-modifiers
				@add="pollOptionLimit++"
				@substract="pollOptionLimit--" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Poll closing status')" :icon-class="closed ? 'icon-polls-closed' : 'icon-polls-open'">
			<ButtonDiv
				:icon="closed ? 'icon-polls-open' : 'icon-polls-closed'"
				:title="closed ? t('polls', 'Reopen poll'): t('polls', 'Close poll')"
				@click="switchClosed()" />
			<CheckBoxDiv v-show="!closed" v-model="pollExpiration" :label="t('polls', 'Closing date')" />
			<DatetimePicker v-show="pollExpiration && !closed" v-model="pollExpire" v-bind="expirationDatePicker" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Access')" icon-class="icon-category-auth">
			<CheckBoxDiv v-if="acl.isOwner" v-model="pollAdminAccess" :label="t('polls', 'Allow admins to edit this poll')" />
			<RadioGroupDiv v-model="pollAccess" :options="accessOptions" />
			<CheckBoxDiv v-model="pollImportant" class="indented" :disabled="pollAccess !== 'public'"
				:label="t('polls', 'Relevant for all users')" />
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Result display')" icon-class="icon-screen">
			<RadioGroupDiv v-model="pollShowResults" :options="pollShowResultsOptions" />
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
import CheckBoxDiv from '../Base/CheckBoxDiv'
import InputDiv from '../Base/InputDiv'
import RadioGroupDiv from '../Base/RadioGroupDiv'

export default {
	name: 'SideBarTabConfiguration',

	components: {
		ConfigBox,
		CheckBoxDiv,
		DatetimePicker,
		InputDiv,
		RadioGroupDiv,
	},

	data() {
		return {
			writingPoll: false,
			sidebar: false,
			titleEmpty: false,
			setExpiration: false,
			accessOptions: [
				{ value: 'hidden', label: t('polls', 'Only invited users') },
				{ value: 'public', label: t('polls', 'All users') },
			],
			pollShowResultsOptions: [
				{ value: 'always', label: t('polls', 'Always show results') },
				{ value: 'closed', label: t('polls', 'Hide results until poll is closed') },
				{ value: 'never', label: t('polls', 'Never show results') },
			],
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
			countOptions: state => state.poll.options.list.length,
		}),

		...mapGetters({
			closed: 'poll/closed',
			participantsVoted: 'poll/participantsVoted',
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

		useVoteLimit: {
			get() {
				return (this.poll.voteLimit !== 0)
			},
			set(value) {
				this.writeValue({ voteLimit: value ? 1 : 0 })
			},
		},

		pollVoteLimit: {
			get() {
				return this.poll.voteLimit
			},
			set(value) {
				if (!this.useVoteLimit) {
					value = 0
				} else if (value < 1) {
					value = this.countOptions
				} else if (value > this.countOptions) {
					value = 1
				}
				this.writeValue({ voteLimit: value })
			},
		},

		useOptionLimit: {
			get() {
				return (this.poll.optionLimit !== 0)
			},
			set(value) {
				this.writeValue({ optionLimit: value ? 1 : 0 })
			},
		},

		pollOptionLimit: {
			get() {
				return this.poll.optionLimit
			},
			set(value) {
				if (!this.useOptionLimit) {
					value = 0
				} else if (value < 1) {
					value = 1
				}
				this.writeValue({ optionLimit: value })
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
				this.writeValue({ anonymous: +value })
			},
		},

		pollImportant: {
			get() {
				return (this.poll.important > 0)
			},
			set(value) {
				this.writeValue({ important: +value })
			},
		},

		pollAdminAccess: {
			get() {
				return (this.poll.adminAccess > 0)
			},
			set(value) {
				this.writeValue({ adminAccess: +value })
			},
		},

		pollAllowComment: {
			get() {
				return this.poll.allowComment
			},
			set(value) {
				this.writeValue({ allowComment: +value })
			},
		},

		pollAllowMaybe: {
			get() {
				return this.poll.allowMaybe
			},
			set(value) {
				this.writeValue({ allowMaybe: +value })
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

		successDebounced: debounce(function(response) {
			showSuccess(t('polls', '"{pollTitle}" successfully saved', { pollTitle: response.data.title }))
			emit('update-polls')
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
					this.$router.push({ name: 'list', params: { type: 'relevant' } })
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
						this.successDebounced(response)
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

<style lang="scss" scoped>
.selectUnit {
	display: flex;
	align-items: center;
	input {
		margin: 0 4px;
		width: 40px;
	}
}

</style>
