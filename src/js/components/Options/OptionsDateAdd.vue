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
			confirm
			style="width: inherit;"
			@change="addOption(lastOption)" />
		<CheckBoxDiv v-model="useDuration" :label="t('polls', 'With end date')" />
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
			useDuration: false,
		}
	},

	computed: {
		optionDatePicker() {
			if (this.useDuration) {
				return {
					editable: false,
					minuteStep: 5,
					type: 'datetime',
					range: true,
					format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
					placeholder: t('polls', 'Click to add a date'),
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

			} else {
				return {
					editable: false,
					minuteStep: 5,
					type: 'datetime',
					format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),
					placeholder: t('polls', 'Click to add a date'),
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
			}
		},
	},

	methods: {
		addOption(dateOption) {
			let pollOptionText = ''
			let duration = 0
			if (this.useDuration) {
				pollOptionText = dateOption[0]
				duration = moment(dateOption[1]).diff(moment(dateOption[0]), 'seconds')
			} else {
				pollOptionText = dateOption
				duration = 0
			}

			if (moment(pollOptionText).isValid()) {
				this.$store.dispatch('poll/options/add', {
					pollOptionText: pollOptionText,
					timestamp: moment(pollOptionText).unix(),
					duration: duration,
				})
			}
		},
	},
}

</script>
