<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import moment from '@nextcloud/moment'
	import { t } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
	import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'

	import { usePollStore, AllowProposals } from '../../stores/poll.ts'

	const pollStore = usePollStore()

	const allowProposals = computed({
		get: () => pollStore.configuration.allowProposals === AllowProposals.Allow,
		set: (value) => {
			pollStore.configuration.allowProposals = value ? AllowProposals.Allow : AllowProposals.Disallow
			pollStore.write()
		},
	})

	const pollExpire = computed({
		get: () => moment.unix(pollStore.configuration.proposalsExpire)._d,
		set: (value) => {
			pollStore.configuration.proposalsExpire = moment(value).unix()
			pollStore.write()
		},
	})

	const proposalExpiration = computed({
		get: () => !!pollStore.configuration.proposalsExpire,
		set: (value) => {
			if (value) {
				pollStore.configuration.proposalsExpire = moment().add(1, 'week').unix()
			} else {
				pollStore.configuration.proposalsExpire = 0
			}
			pollStore.write()
		},
	})

	const firstDOW = moment.localeData()._week.dow === 0 ? 7 : moment.localeData()._week.dow

	const expirationDatePicker = {
		editable: true,
		minuteStep: 5,
		type: 'datetime',
		format: moment.localeData().longDateFormat('L LT'),
		placeholder: t('polls', 'Proposals possible until'),
		confirm: true,
		lang: {
			formatLocale: {
				firstDayOfWeek: firstDOW,
				months: moment.months(),
				monthsShort: moment.monthsShort(),
				weekdays: moment.weekdays(),
				weekdaysMin: moment.weekdaysMin(),
			},
		},
	}
</script>

<template>
	<div>
		<NcCheckboxRadioSwitch v-model="allowProposals" type="switch">
			{{ t('polls', 'Allow Proposals') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-show="pollStore.isProposalAllowed" v-model="proposalExpiration" type="switch">
			{{ t('polls', 'Proposal closing date') }}
		</NcCheckboxRadioSwitch>

		<NcDateTimePicker v-show="proposalExpiration && pollStore.isProposalAllowed" v-model="pollExpire" v-bind="expirationDatePicker" />
	</div>
</template>
