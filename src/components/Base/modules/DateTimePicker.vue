<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'
import { NcButton, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { ButtonType } from '@nextcloud/vue/components/NcButton'

import ChevronLeftIcon from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'

const model = defineModel({
	required: true,
	type: Object,
})

const timeSelected = defineModel('timeSelected', {
	type: Boolean,
})

const props = defineProps({
	useTime: {
		type: Boolean,
		default: false,
	},
	selectTime: {
		type: Boolean,
		default: false,
	},
	useDayButtons: {
		type: Boolean,
		default: false,
	},
})

const firstDOW = computed(() => {
	// vue2-datepicker needs 7 for sunday
	if (moment.localeData()._week.dow === 0) {
		return 7
	}
	return moment.localeData()._week.dow
})

const formatLocale = computed(() => ({
	firstDayOfWeek: firstDOW.value,
	months: moment.months(),
	monthsShort: moment.monthsShort(),
	weekdays: moment.weekdays(),
	weekdaysMin: moment.weekdaysMin(),
}))

const datePickerOptions = computed(() => ({
	type: 'datetime',
	showTimePanel: false,
	appendToBody: true,
	editable: true,
	format: moment.localeData().longDateFormat('L'),
	placeholder: moment.localeData().longDateFormat('L'),
	lang: {
		formatLocale,
	},
}))

const timePickerOptions = computed(() => ({
	type: 'time',
	editable: true,
	minuteStep: 15,
	appendToBody: true,
	showSecond: false,
	format: moment.localeData().longDateFormat('LT'),
	placeholder: moment.localeData().longDateFormat('LT'),
	lang: {
		formatLocale,
	},
}))

function previousDay() {
	if (model.value) {
		const date = moment(model.value).subtract(1, 'day')
		model.value = date.toDate()
	}
}

function nextDay() {
	if (model.value) {
		const date = moment(model.value).add(1, 'day')
		model.value = date.toDate()
	}
}
</script>

<template>
	<div class="date-time-picker">
		<slot name="label" />
		<div class="picker-wrapper">
			<slot name="icon" />

			<div class="picker-input">
				<NcButton
					v-if="props.useDayButtons"
					:title="t('polls', 'Previous day')"
					:type="ButtonType.TertiaryNoBackground"
					@click="previousDay">
					<template #icon>
						<ChevronLeftIcon />
					</template>
				</NcButton>
				<NcDateTimePicker
					v-model="model"
					v-bind="datePickerOptions"
					class="date-picker"
					:aria-label="t('polls', 'Enter a date')" />
				<NcButton
					v-if="props.useDayButtons"
					:title="t('polls', 'Next day')"
					:type="ButtonType.TertiaryNoBackground"
					@click="nextDay">
					<template #icon>
						<ChevronRightIcon />
					</template>
				</NcButton>
				<div class="time-picker">
					<NcCheckboxRadioSwitch
						v-if="props.selectTime"
						v-model="timeSelected"
						:aria-label="t('polls', 'Select a time')" />

					<NcDateTimePicker
						v-if="props.useTime || props.selectTime"
						v-model="model"
						:disabled="props.selectTime && !timeSelected"
						v-bind="timePickerOptions"
						:aria-label="t('polls', 'Enter a time')" />
				</div>
			</div>
		</div>

		<slot name="helper" class="helper" />
	</div>
</template>

<style lang="scss" scoped>
.helper {
	min-height: 1.5rem;
	font-size: 0.8em;
	opacity: 0.8;
	&.error {
		opacity: 1;
		color: var(--color-error);
	}
}

.picker-wrapper {
	display: flex;
	.checkbox-radio-switch-checkbox {
		flex: 0 0 auto;
	}
}

.picker-input {
	display: flex;
	flex-wrap: wrap;
	column-gap: 0.25rem;
}

.time-picker {
	display: flex;
	align-items: center;
}
// .mx-datepicker {
// 	&.date-picker {
// 		max-width: 9rem;
// 	}
// 	&.time-picker {
// 		max-width: 6.5rem;
// 	}
// }
</style>
