<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcButton @click="clickToggleClosed()">
			<template #icon>
				<OpenPollIcon v-if="pollStore.isClosed" />
				<ClosePollIcon v-else />
			</template>
			<template #default>
				{{ pollStore.isClosed ? t('polls', 'Reopen poll') : t('polls', 'Close poll') }}
			</template>
		</NcButton>
		<NcCheckboxRadioSwitch v-show="!pollStore.isClosed" :checked.sync="useExpire" type="switch">
			{{ t('polls', 'Poll closing date') }}
		</NcCheckboxRadioSwitch>
		<NcDateTimePicker v-show="useExpire && !pollStore.isClosed" v-model="expire" v-bind="expirationDatePicker" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import moment from '@nextcloud/moment'
import { NcButton, NcDateTimePicker, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import OpenPollIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import ClosePollIcon from 'vue-material-design-icons/Lock.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

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
		...mapStores(usePollStore),

		expire: {
			get() {
				return moment.unix(this.pollStore.configuration.expire)._d
			},
			set(value) {
				this.pollStore.configuration.expire = moment(value).unix()
				this.pollStore.write()
			},
		},

		useExpire: {
			get() {
				return !!this.pollStore.configuration.expire
			},
			set(value) {
				if (value) {
					this.pollStore.configuration.expire = moment().add(1, 'week').unix()
				} else {
					this.pollStore.configuration.expire = 0
				}
				this.pollStore.write()
			},
		},
	},

	methods: {
		t,
		clickToggleClosed() {
			if (this.pollStore.isClosed) {
				this.pollStore.reopen()
			} else {
				this.pollStore.close()
			}
		},
	},
}
</script>
