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
	<ConfigBox :title="t('polls', 'Add a date option')" icon-class="icon-add">
		<DatetimePicker v-model="lastOption"
			v-bind="optionDatePicker"
			style="width: inherit;"
			@change="addOption()">
			<template slot="footer">
				<CheckBoxDiv v-model="useRange" class="range" :label="t('polls', 'Select range')" />
				<!-- class="mx-btn mx-btn-text" -->
				<button v-if="!showTimePanel" class="mx-btn" @click="toggleTimePanel">
					{{ t('polls', 'Add time') }}
				</button>
				<button v-else class="mx-btn" @click="toggleTimePanel">
					{{ t('polls', 'Remove time') }}
				</button>
			</template>
		</DateTimePicker>
	</ConfigBox>
</template>

<script>

import CheckBoxDiv from '../Base/CheckBoxDiv'
import ConfigBox from '../Base/ConfigBox'
import moment from '@nextcloud/moment'
import { DatetimePicker } from '@nextcloud/vue'

export default {
	name: 'OptionsDateAdd',

	components: {
		CheckBoxDiv,
		ConfigBox,
		DatetimePicker,
	},

	data() {
		return {
			lastOption: '',
			useRange: false,
			showTimePanel: false,
		}
	},

	computed: {
		tempFormat() {
			if (this.showTimePanel) {
				return moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT')
			} else {
				return moment.localeData().longDateFormat('L')
			}
		},

		optionDatePicker() {
			return {
				editable: false,
				minuteStep: 5,
				type: this.showTimePanel ? 'datetime' : 'date',
				range: this.useRange,
				showTimePanel: this.showTimePanel,
				format: this.tempFormat,
				placeholder: t('polls', 'Click to add an option'),
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
		toggleTimePanel() {
			this.showTimePanel = !this.showTimePanel
		},

		useDay() {
			console.debug('Only days')
		},

		addOption() {
			const timeToAdd = this.showTimePanel ? 0 : 24
			this.showTimePanel = false

			let startDate
			let endDate
			let pollOptionText = ''
			let timestamp = 0
			if (this.useRange) {
				startDate = moment(this.lastOption[0]).format('LLL')
				endDate = moment(this.lastOption[1]).add(timeToAdd, 'hours').format('LLL')
				pollOptionText = this.lastOption[0]
				timestamp = moment(this.lastOption[0]).unix()
			} else {
				startDate = moment(this.lastOption).format('LLL')
				endDate = moment(this.lastOption).add(timeToAdd, 'hours').format('LLL')
				pollOptionText = this.lastOption
				timestamp = moment(this.lastOption).unix()
			}
			const duration = moment(endDate).diff(startDate) / 1000
			// const duration = moment(endDate).diff(startDate) / 1000
			console.debug('Start Date', startDate)
			console.debug('End Date', endDate)
			console.debug('End Date', duration)
			console.debug('End Date', duration)

			if (moment(pollOptionText).isValid()) {
				this.$store.dispatch('poll/options/add', {
					pollOptionText: pollOptionText,
					timestamp: timestamp,
					duration: duration,
				})
			}
		},
	},
}

</script>

<style lang="scss" scoped>

.range {
	flex: 1;
	text-align: left;
	margin: 8px;
}

</style>
