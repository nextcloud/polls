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
	</ConfigBox>
</template>

<script>

import ConfigBox from '../Base/ConfigBox'
import moment from '@nextcloud/moment'
import { DatetimePicker } from '@nextcloud/vue'

export default {
	name: 'OptionAddDate',

	components: {
		ConfigBox,
		DatetimePicker,
	},

	data() {
		return {
			lastOption: '',
		}
	},

	computed: {
		optionDatePicker() {
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
		},
	},

	methods: {
		addOption(pollOptionText) {
			if (moment(pollOptionText).isValid()) {
				this.$store.dispatch('poll/options/add', {
					pollOptionText: pollOptionText,
					timestamp: moment(pollOptionText).unix(),
				})
			}
		},
	},
}

</script>
