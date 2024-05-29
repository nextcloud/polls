<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcCheckboxRadioSwitch :checked.sync="allowProposals" type="switch">
			{{ t('polls', 'Allow Proposals') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-show="isProposalAllowed" :checked.sync="proposalExpiration" type="switch">
			{{ t('polls', 'Proposal closing date') }}
		</NcCheckboxRadioSwitch>

		<NcDateTimePicker v-show="proposalExpiration && isProposalAllowed" v-model="pollExpire" v-bind="expirationDatePicker" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import moment from '@nextcloud/moment'
import { NcCheckboxRadioSwitch, NcDateTimePicker } from '@nextcloud/vue'
import { writePoll } from '../../mixins/writePoll.js'

export default {
	name: 'ConfigProposals',

	components: {
		NcCheckboxRadioSwitch,
		NcDateTimePicker,
	},

	mixins: [writePoll],

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		...mapGetters({
			isProposalAllowed: 'poll/isProposalAllowed',
		}),

		// Add bindings
		allowProposals: {
			get() {
				return (this.pollConfiguration.allowProposals === 'allow')
			},
			set(value) {
				this.writeValue({ allowProposals: value ? 'allow' : 'disallow' })
			},
		},

		pollExpire: {
			get() {
				return moment.unix(this.pollConfiguration.proposalsExpire)._d
			},
			set(value) {
				this.writeValue({ proposalsExpire: moment(value).unix() })
			},
		},

		proposalExpiration: {
			get() {
				return !!this.pollConfiguration.proposalsExpire
			},
			set(value) {
				if (value) {
					this.writeValue({ proposalsExpire: moment().add(1, 'week').unix() })
				} else {
					this.writeValue({ proposalsExpire: 0 })
				}
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
		writeValue(error) {
			this.$store.commit('poll/setProperty', error)
			this.writePoll() // from mixin
		},
	},
}
</script>
