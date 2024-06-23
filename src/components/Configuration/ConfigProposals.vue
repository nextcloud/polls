<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcCheckboxRadioSwitch :checked.sync="allowProposals" type="switch">
			{{ t('polls', 'Allow Proposals') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-show="pollStore.isProposalAllowed" :checked.sync="proposalExpiration" type="switch">
			{{ t('polls', 'Proposal closing date') }}
		</NcCheckboxRadioSwitch>

		<NcDateTimePicker v-show="proposalExpiration && pollStore.isProposalAllowed" v-model="pollExpire" v-bind="expirationDatePicker" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import moment from '@nextcloud/moment'
import { NcCheckboxRadioSwitch, NcDateTimePicker } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ConfigProposals',

	components: {
		NcCheckboxRadioSwitch,
		NcDateTimePicker,
	},

	computed: {
		...mapStores(usePollStore),

		// Add bindings
		allowProposals: {
			get() {
				return (this.pollStore.configuration.allowProposals === 'allow')
			},
			set(value) {
				this.pollStore.configuration.allowProposals = value ? 'allow' : 'disallow'
				this.pollStore.write()
			},
		},

		pollExpire: {
			get() {
				return moment.unix(this.pollStore.configuration.proposalsExpire)._d
			},
			set(value) {
				this.pollStore.configuration.proposalsExpire = moment(value).unix()
				this.pollStore.write()
			},
		},

		proposalExpiration: {
			get() {
				return !!this.pollStore.configuration.proposalsExpire
			},
			set(value) {
				if (value) {
					this.pollStore.configuration.proposalsExpire = moment().add(1, 'week').unix()
				} else {
					this.pollStore.configuration.proposalsExpire= 0
				}
				this.pollStore.write()
			},
		},

		firstDOW() {
			// vue2-datepicker needs 7 for sunday
			if (moment.localeData()._week.dow === 0) {
				return 7
			}
			return moment.localeData()._week.dow

		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 5,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L LT'),
				placeholder: t('polls', 'Proposals possible until'),
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
		t,
	},
}
</script>
