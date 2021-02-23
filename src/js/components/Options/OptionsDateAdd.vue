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
		<DatetimePicker v-model="pickedOption"
			v-bind="optionDatePicker"
			:open.sync="picker.open"
			style="width: inherit;"
			@pick="pickedDate">
			<template slot="footer">
				<CheckBoxDiv v-model="picker.useRange" class="range" :label="t('polls', 'Select range')" />
				<button v-if="!picker.showTimePanel" class="mx-btn" @click="toggleTimePanel">
					{{ t('polls', 'Add time') }}
				</button>
				<button v-else class="mx-btn" @click="toggleTimePanel">
					{{ t('polls', 'Remove time') }}
				</button>
				<button class="mx-btn" @click="addOption">
					{{ t('polls', 'OK') }}
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
			pickedOption: null,
			lastPickedOption: null,
			picker: {
				useRange: false,
				showTimePanel: false,
				open: false,
			},
		}
	},

	computed: {
		tempFormat() {
			if (this.picker.showTimePanel) {
				return moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT')
			} else {
				return moment.localeData().longDateFormat('L')
			}
		},

		optionDatePicker() {
			return {
				editable: false,
				minuteStep: 5,
				type: this.picker.showTimePanel ? 'datetime' : 'date',
				range: this.picker.useRange,
				showTimePanel: this.picker.showTimePanel,
				format: this.tempFormat,
				placeholder: t('polls', 'Click to add an option'),
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

	watch: {
		useRange() {
			if (this.picker.useRange) {
				if (!Array.isArray(this.pickedOption)) {
					this.pickedOption = [this.pickedOption, this.pickedOption]
				}
			} else {
				if (Array.isArray(this.pickedOption)) {
					this.pickedOption = this.pickedOption[0]
				}
			}
		},
	},

	methods: {
		toggleTimePanel() {
			if (this.picker.useRange) {
				if (Array.isArray(this.pickedOption)) {
					if (this.lastPickedOption !== this.pickedOption[0]
						&& this.lastPickedOption !== this.pickedOption[0]) {
						this.pickedOption = [this.lastPickedOption, this.lastPickedOption]
					}
				} else {
					if (this.lastPickedOption) {
						this.pickedOption = [this.lastPickedOption, this.lastPickedOption]
					}
				}
			}
			this.picker.showTimePanel = !this.picker.showTimePanel
		},

		pickedDate(value) {
			this.lastPickedOption = value
			this.picker.open = true
		},

		addOption() {
			this.picker.open = false
			this.picker.showTimePanel = false

			const timeToAdd = this.picker.showTimePanel ? 0 : 24

			console.debug('picker.showTimePanel', this.picker.showTimePanel)
			console.debug('timeToAdd', timeToAdd)

			let startDate
			let endDate
			let pollOptionText = ''
			let timestamp = 0
			if (this.picker.useRange) {
				startDate = moment(this.pickedOption[0]).format('LLL')
				endDate = moment(this.pickedOption[1]).add(timeToAdd, 'hours').format('LLL')
				pollOptionText = this.pickedOption[0]
				timestamp = moment(this.pickedOption[0]).unix()
			} else {
				startDate = moment(this.pickedOption).format('LLL')
				endDate = moment(this.pickedOption).add(timeToAdd, 'hours').format('LLL')
				pollOptionText = this.pickedOption
				timestamp = moment(this.pickedOption).unix()
			}
			const duration = moment(endDate).diff(startDate) / 1000

			console.debug('Start Date', startDate)
			console.debug('End Date', endDate)
			console.debug('duration', duration)

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
