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
			:open.sync="pickerOpen"
			style="width: inherit;"
			@pick="pickedDate">
			<template slot="footer">
				<CheckBoxDiv v-model="useRange" class="range" :label="t('polls', 'Select range')" />
				<button v-if="!showTimePanel" class="mx-btn" @click="toggleTimePanel">
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
			startDate: moment(),
			endDate: moment(),
			pickerOpen: false,
			useRange: false,
			showTimePanel: false,
		}
	},

	computed: {
		tempFormat() {
			if (this.showTimePanel) {
				return moment.localeData().longDateFormat('L LT')
			} else {
				return moment.localeData().longDateFormat('L')
			}
		},

		dateOption() {
			const timeToAdd = this.showTimePanel ? 0 : 86400

			const startDate = this.useRange ? moment(this.pickedOption[0]) : moment(this.pickedOption)
			const endDate = this.useRange ? moment(this.pickedOption[1]).add(timeToAdd, 'seconds') : moment(this.pickedOption).add(timeToAdd, 'seconds')
			const pollOptionTextStart = startDate.utc().format(moment.defaultFormat)
			const pollOptionTextEnd = startDate === endDate ? '' : ' - ' + endDate.utc().format(moment.defaultFormat)

			return {
				timestamp: startDate.unix(),
				pollOptionText: pollOptionTextStart + pollOptionTextEnd,
				duration: endDate.unix() - startDate.unix(),
			}
		},

		optionDatePicker() {
			return {
				editable: false,
				minuteStep: 5,
				type: this.showTimePanel ? 'datetime' : 'date',
				range: this.useRange,
				showTimePanel: this.showTimePanel,
				valueType: 'timestamp',
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
			if (this.useRange) {
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
			if (this.useRange) {
				if (Array.isArray(this.pickedOption)) {
					if (this.lastPickedOption !== this.pickedOption[0]
						&& this.lastPickedOption !== this.pickedOption[1]) {
						this.pickedOption = [this.lastPickedOption, this.lastPickedOption]
					}
				} else {
					if (this.lastPickedOption) {
						this.pickedOption = [this.lastPickedOption, this.lastPickedOption]
					}
				}
			}
			this.showTimePanel = !this.showTimePanel
		},

		pickedDate(value) {
			if (this.optionDatePicker.valueType === 'timestamp') {
				this.lastPickedOption = moment(value).valueOf()
			} else {
				this.lastPickedOption = value
			}
			this.pickerOpen = true
		},

		addOption() {
			this.pickerOpen = false
			this.$store.dispatch('options/add', this.dateOption)
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
