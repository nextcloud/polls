<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue';
	import { t } from '@nextcloud/l10n'
	import moment from '@nextcloud/moment'
	import NcDateTimePicker from '@nextcloud/vue/dist/Components/NcDateTimePicker.js'

	const model = defineModel({
		required: true,
		type: Object,
	})

	const emit = defineEmits(['delete', 'change'])

	const props = defineProps({
		useTime: {
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
</script>

<template>
	<div class="date-time-picker">
		<slot name="label" />
		<div class="picker-wrapper">
			<slot name="icon" />

			<div class="picker-input">
				<NcDateTimePicker v-model="model"
					v-bind="datePickerOptions"
					class="date-picker"
					:aria-label="t('polls', 'Enter a date')"
					@change="emit('change')">
				</NcDateTimePicker>

				<NcDateTimePicker v-if="props.useTime"
					v-model="model"
					v-bind="timePickerOptions"
					class="time-picker"
					:aria-label="t('polls', 'Enter a time')"
					@change="emit('change')">
				</NcDateTimePicker>
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
			color: var(--color-error)
		}
	}

	.picker-wrapper {
		display: flex;
		column-gap: 8px;
	}

	.picker-input {
		display: flex;
		// flex-wrap: wrap;
		column-gap: 4px;
	}

	.mx-datepicker {
		&.date-picker {
			max-width: 9rem;
		}
		&.time-picker {
			max-width: 6rem;
		}
	}
</style>
