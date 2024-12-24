<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, ref, watch } from 'vue'
	import { showError, showSuccess } from '@nextcloud/dialogs'
	import { t, n } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'

	import CheckIcon from 'vue-material-design-icons/Check.vue'
	import CalendarStartIcon from 'vue-material-design-icons/CalendarStart.vue'
	import CalendarEndIcon from 'vue-material-design-icons/CalendarEnd.vue'

	import DateTimePicker from '../Base/modules/DateTimePicker.vue'
	import DateBox from '../Base/modules/DateBox.vue'
	import { useOptionsStore } from '../../stores/options'
	import { StatusResults } from '../../Types'

	const timeStepMinutes = 15
	const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success')
	const useRange = ref(false)
	const allDay = ref(true)
	const optionsStore = useOptionsStore()
	const result = ref(StatusResults.None)
	// set initial time mark to the next full quater of the hour
	const dateFrom = ref(new Date(new Date().setMinutes(Math.ceil((46/60)*(60/timeStepMinutes))*timeStepMinutes, 0, 0)))

	const storedDurationSec = ref(60 * 60) // 1 hour
	const dateTo = ref(new Date(dateFrom.value.getTime() + (1000 * storedDurationSec.value))) // 7 days later (in ms)

	const humanReadableDuration = computed(() => {
		// use duration information for blocked Option error message to avoid more areas
		if (blockedOption.value) {
			return t('polls', 'Option already exists')
		}

		if (durationComputedSec.value === 0) {
			return t('polls', 'No duration')
		}
		const result = convertSeconds(durationComputedSec.value)

		let durationString = ''

		if (result.years > 0) durationString += `${result.years} ${n('polls', 'year', 'years', result.years)}, `
		if (result.months > 0) durationString += `${result.months} ${n('polls', 'month', 'months', result.months)}, `
		if (result.days > 0) durationString += `${result.days} ${n('polls', 'day', 'days', result.days)}, `
		if (result.hours > 0) durationString += `${result.hours} ${n('polls', 'hour', 'hours', result.hours)}, `
		if (result.minutes > 0) durationString += `${result.minutes} ${n('polls', 'minute', 'minutes', result.minutes)}, `
		return durationString.replace(/, $/, '');
	})

	const finalFrom = computed(() => {
		const date = new Date(dateFrom.value)

		if (allDay.value) {
			// set time to 00:00:00.0 (Start of the day) in case of a day only option
			date.setHours(0, 0, 0, 0)
		} else {
			date.setSeconds(0, 0)
		}
		return {
			dateTime: date,
			ts: date.getTime(),
			unix: Math.floor(date.getTime() / 1000)
		}
	})

	const finalTo = computed(() => {
		let date = new Date(dateTo.value)

		// if only a moment is requested, set the due dateTime to dateFrom
		if (!useRange.value) {
			date = new Date(dateFrom.value)
		}

		if (allDay.value) {
			// set time to 23:59:59.999 (end of the day) in case of a day only option
			date.setHours(23, 59, 59, 999)
		} else {
			date.setSeconds(0, 0)
		}
		// return unix timestamp in seconds rounded up for full seconds
		return {
			dateTime: date,
			ts: date.getTime(),
			unix: Math.ceil(date.getTime() / 1000)
		}
	})

	const sameDaySpan = computed(() => dateFrom.value.getDate() === dateTo.value.getDate() && dateFrom.value.getMonth() === dateTo.value.getMonth() && dateFrom.value.getFullYear() === dateTo.value.getFullYear())
	const durationComputedSec = computed(() => finalTo.value.unix - finalFrom.value.unix)

	const finalDurationSec = computed(() => {
		if (!useRange.value) {
			return allDay.value ? (24 * 60 * 60) : 0 // 1 day
		}

		return durationComputedSec.value
	})

	const addable = computed(() => (!blockedOption.value && result.value !== StatusResults.Loading))

	const blockedOption = computed(() => {
		if (optionsStore.find(finalFrom.value.unix, finalDurationSec.value)) {
			return result.value !== StatusResults.Success
		}
		return false
	})

	watch(() => finalFrom.value, () => {
		dateTo.value = new Date(dateFrom.value.getTime() + storedDurationSec.value * 1000)
		onAnyChange()
	})

	watch(() => finalTo.value, () => {
		if (dateTo.value.getTime() < dateFrom.value.getTime()) {
			dateTo.value = new Date(dateFrom.value.getTime() + (1000 * 60 * 60)) // 1 hour
		}
		storedDurationSec.value = durationComputedSec.value
		onAnyChange()
	})

	watch(() => allDay.value, onAnyChange)
	watch(() => useRange.value, onAnyChange)

	function convertSeconds(seconds) {
		const years = Math.floor(seconds / (365 * 24 * 60 * 60));
		seconds %= (365 * 24 * 60 * 60);
		const months = Math.floor(seconds / (30 * 24 * 60 * 60));
		seconds %= (30 * 24 * 60 * 60);
		const days = Math.floor(seconds / (24 * 60 * 60));
		seconds %= (24 * 60 * 60);
		const hours = Math.floor(seconds / (60 * 60));
		seconds %= (60 * 60);
		const minutes = Math.floor(seconds / 60);
		seconds %= 60;

		return {
			years,
			months,
			days,
			hours,
			minutes,
			seconds
		};
	}

	function onAnyChange() {
		if (addable.value) {
			result.value = StatusResults.None
			return
		}

		result.value = StatusResults.Error
	}

	async function addOption() {
		result.value = StatusResults.Loading

		try {
			await optionsStore.add({
				text: '',
				timestamp: finalFrom.value.unix,
				duration: finalDurationSec.value,
			})
			result.value = StatusResults.Success
			showSuccess(t('polls', 'Option added'))
		} catch (error) {
			if (error.response.status === 409) {
				showError(t('polls', 'Option already exists'))
				result.value = StatusResults.Warning
				return
			}

			showError(t('polls', 'Error adding Option'))
			result.value = StatusResults.Error
		}
	}

</script>

<template>
	<div class="header-container">
		<h2>{{ t('polls', 'Add option') }}</h2>
	</div>

	<div class="add-container">
		<div class="select-container">
			<div class="from">
				<DateTimePicker v-model="dateFrom"
					:use-time="!allDay"
					focus>
					<template #icon>
						<CalendarStartIcon />
					</template>
				</DateTimePicker>
			</div>

			<div v-if="useRange" class="to">
				<DateTimePicker v-model="dateTo"
					:use-time="!allDay">
					<template #icon>
						<CalendarEndIcon />
					</template>
				</DateTimePicker>
			</div>
		</div>

		<div class="switch-container">
			<NcCheckboxRadioSwitch v-model="allDay"
				type="switch">
				{{ t('polls', 'All day') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="useRange"
				type="switch">
				{{ t('polls', 'Time range') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>

	<div class="preview-container">
		<h2>{{ t('polls', 'Preview') }}</h2>
		<div class="preview">

			<div class="preview-container">
				<div class="preview___entry">
					<DateBox class="from"
						:date="dateFrom"
						:duration-sec="sameDaySpan ? durationComputedSec : 0"
						:hide-time="allDay" />

					<div v-if="useRange && !sameDaySpan" class="preview___devider">
						<span>{{ ' - ' }} </span>
					</div>

					<DateBox v-if="useRange && !sameDaySpan"
						class="to"
						:date="dateTo"
						:hide-time="allDay" />
				</div>
				<div :class="['duration-info', { error: blockedOption }]">
					{{ humanReadableDuration }}
				</div>
			</div>

			<CheckIcon v-if="result === StatusResults.Success"
				class="date-added"
				:title="t('polls', 'Added')"
				:fill-color="successColor"
				:size="26" />

			<NcButton v-else
				:type="ButtonType.Primary"
				:disabled="!addable"
				@click="addOption">{{ t('polls', 'Add') }}</NcButton>
		</div>
	</div>
</template>

<style lang="scss">
	.modal-container__content {
		padding: 0 24px;
	}

	.add-container {
		display: flex;
		flex-wrap: wrap-reverse;
	}
	.select-duration {
		display: flex;
		column-gap: 1rem;
		align-items: center;
	}

	.lock-duration {
		position: relative;
		top: 1.3rem;
		left: -0.8rem;
	}

	.time-lock {
		border-style: solid;
		width: 22px;
		height: 0.5rem;
		margin-left: 0.5rem;

		&.upper {
			border-width: 2px 2px 0 0;
			border-top-right-radius: 25%;
		}

		&.lower {
			border-width: 0 2px 2px 0;
			border-bottom-right-radius: 25%;
		}
	}

	.preview {
		display: flex;
		align-items: center;

		.duration-info {
			font-size: 0.8em;
			color: var(--color-text-maxcontrast);
			font-weight: 600;
			text-align: center;
			&.error {
				color: var(--color-error-text);
			}
		}

		.preview-container {
			flex: 1 auto;
			align-items: center;
			display: flex;
			flex-direction: column;
			min-height: 6.5rem;
			margin-bottom: 1rem;
		}
		.preview___entry {
			display: flex;
			flex: 1 auto;
			justify-content: center;
			column-gap: 0.6rem;
			min-height: 5.5rem;
		}

		.preview___devider {
			flex: 0 auto;
			display: flex;
			align-items: center;
		}

		button {
			flex: 0 auto;
			min-height: 40px;
		}

	}

	.select-container {
		display: flex;
		flex-direction: column;
		min-height: 5.4rem;
		flex: 1 18rem;
		.to, .from {
			display: flex;
			align-items: center;
		}
	}

	.switch-container {
		display: flex;
		flex: 1;
		flex-wrap: wrap;
		justify-content: end;
		& > * {
			flex: 1 fit-content;
			text-wrap: nowrap;
		}
	}

	.mx-datepicker-main.mx-datepicker-popup {
		// TODO: Hack to force the date picker to be on top of the modal
		z-index: 20000;
	}

</style>
