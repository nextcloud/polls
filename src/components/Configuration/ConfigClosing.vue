<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import moment from '@nextcloud/moment'
	import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
	import NcDateTimePicker from '@nextcloud/vue/dist/Components/NcDateTimePicker.js'
	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	import OpenPollIcon from 'vue-material-design-icons/LockOpenVariant.vue'
	import ClosePollIcon from 'vue-material-design-icons/Lock.vue'
	import { t } from '@nextcloud/l10n'
	import { usePollStore } from '../../stores/poll.ts'

	const pollStore = usePollStore()

	const expirationDatePicker = {
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
	}

	const expire = computed({
		get: () => moment.unix(pollStore.configuration.expire)._d,
		set: (value) => {
			pollStore.configuration.expire = moment(value).unix()
			pollStore.write()
		},
	})

	const useExpire = computed({
		get: () => !!pollStore.configuration.expire,
		set: (value) => {
			if (value) {
				pollStore.configuration.expire = moment().add(1, 'week').unix()
			} else {
				pollStore.configuration.expire = 0
			}
			pollStore.write()
		},
	})

	function clickToggleClosed() {
		if (pollStore.isClosed) {
			pollStore.reopen()
		} else {
			pollStore.close()
		}
	}
</script>

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
		<NcCheckboxRadioSwitch v-show="!pollStore.isClosed" v-model="useExpire" type="switch">
			{{ t('polls', 'Poll closing date') }}
		</NcCheckboxRadioSwitch>
		<NcDateTimePicker v-show="useExpire && !pollStore.isClosed" v-model="expire" v-bind="expirationDatePicker" />
	</div>
</template>
