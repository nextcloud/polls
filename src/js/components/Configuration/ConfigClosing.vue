<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcButton @click="clickToggleClosed()">
			<template #icon>
				<OpenPollIcon v-if="isPollClosed" />
				<ClosePollIcon v-else />
			</template>
			<template #default>
				{{ isPollClosed ? t('polls', 'Reopen poll') : t('polls', 'Close poll') }}
			</template>
		</NcButton>
		<NcCheckboxRadioSwitch v-show="!isPollClosed" :checked.sync="useExpire" type="switch">
			{{ t('polls', 'Poll closing date') }}
		</NcCheckboxRadioSwitch>
		<NcDateTimePicker v-show="useExpire && !isPollClosed" v-model="expire" v-bind="expirationDatePicker" />
	</div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import moment from '@nextcloud/moment'
import { NcButton, NcDateTimePicker, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import OpenPollIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import ClosePollIcon from 'vue-material-design-icons/Lock.vue'

export default {
	name: 'ConfigClosing',

	components: {
		OpenPollIcon,
		ClosePollIcon,
		NcCheckboxRadioSwitch,
		NcDateTimePicker,
		NcButton,
	},

	data() {
		return {
			expirationDatePicker: {
				editable: true,
				minuteStep: 5,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L LT'),
				placeholder: t('polls', 'Poll closing date'),
				confirm: true,
				lang: {
					formatLocale: {
						firstDayOfWeek: moment.localeData()._week.dow === 0 ? 7 : moment.localeData()._week.dow,
						months: moment.months(),
						monthsShort: moment.monthsShort(),
						weekdays: moment.weekdays(),
						weekdaysMin: moment.weekdaysMin(),
					},
				},
			},
		}
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
		}),

		expire: {
			get() {
				return moment.unix(this.pollConfiguration.expire)._d
			},
			set(value) {
				this.$store.commit('poll/setProperty', { expire: moment(value).unix() })
				this.$emit('change')
			},
		},

		useExpire: {
			get() {
				return !!this.pollConfiguration.expire
			},
			set(value) {
				if (value) {
					this.$store.commit('poll/setProperty', { expire: moment().add(1, 'week').unix() })
				} else {
					this.$store.commit('poll/setProperty', { expire: 0 })
				}
				this.$emit('change')
			},
		},
	},

	methods: {
		...mapActions({
			closePoll: 'poll/close',
			reopenPoll: 'poll/reopen',
		}),
		clickToggleClosed() {
			if (this.isPollClosed) {
				this.reopenPoll()
			} else {
				this.closePoll()
			}
		},
	},
}
</script>
