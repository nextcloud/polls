<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, ref, watch } from 'vue'
	import { showError, showSuccess } from '@nextcloud/dialogs'
	import { n, t } from '@nextcloud/l10n'

	import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'
	import NcSelect from '@nextcloud/vue/components/NcSelect'
	import { DateTime, Duration } from 'luxon';
	import CheckIcon from 'vue-material-design-icons/Check.vue'

	import { InputDiv } from '../Base/index.js'
	import DateTimePicker from '../Base/modules/DateTimePicker.vue'
	import DateBox from '../Base/modules/DateBox.vue'
	import { useSessionStore } from '../../stores/session'
	import { useOptionsStore, Sequence } from '../../stores/options'
	import { StatusResults } from '../../Types'
	import { dateOnlyUnits, dateUnits, DateUnitKeys, DurationType } from '../../constants/dateUnits.ts'
	import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

	const sessionStore = useSessionStore()
	const optionsStore = useOptionsStore()

	const timeStepMinutes = 15
	const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success')
	const result = ref(StatusResults.None)

	// *** refs for the inputs
	// allDay is a boolean to toggle between all day and time based options
	const allDay = ref(true)

	// set initial time mark to the next full quater of the hour
	const fromInput = ref(new Date(new Date().setMinutes(Math.ceil((new Date().getMinutes() / 60) * (60 / timeStepMinutes)) * timeStepMinutes, 0, 0)))

	// set initial duration to one Day
	const durationInput = ref<DurationType>({
		unit: dateUnits.find(unit => unit.key === DateUnitKeys.Day),
		amount: 0,
	})

	// set initial sequence to one week but disabled
	const sequenceInput = ref<Sequence>({
		unit: dateUnits.find(unit => unit.key === DateUnitKeys.Week),
		stepWidth: 1,
		repetitions: 0,
	})

	// computed from as DateTime from Luxon
	const from = computed(() => {
		const dateFrom = DateTime.fromJSDate(fromInput.value).setLocale(sessionStore.currentUser.languageCode)
		// if the option is an all day option, the time is set to 00:00
		if (allDay.value) {
			return dateFrom.startOf(DateUnitKeys.Day).setLocale(sessionStore.currentUser.languageCode)
		}
		return dateFrom
	})

	// computed duration as Duration from Luxon
	// Set duration to 1 Day if allDay is true and duration is 0
	const duration = computed(() => durationInput.value.amount < 1 && allDay.value
		? Duration.fromObject({ [DateUnitKeys.Day]: 1 })
		: Duration.fromObject({ [durationInput.value.unit.key]: durationInput.value.amount }))

	// computed sequence as Duration from Luxon
	// Set sequence to 0 if repetitions are 0
	const sequence = computed(() => sequenceInput.value.repetitions > 0
		? Duration.fromObject({ [sequenceInput.value.unit.key]: sequenceInput.value.stepWidth * sequenceInput.value.repetitions })
		: Duration.fromObject({ millisecond: 0 }))

	// True, if from and to dates are the same day
	const sameDay = computed(() => from.value.hasSame(to.value, DateUnitKeys.Day))

	// *** computed properties only used for display
	// computed to as DateTime from Luxon
	// remove one day to simulate the end of the prior day and not the start of the calculated day in case of allDay
	const to = computed(() => from.value.plus(duration.value).minus({ [DateUnitKeys.Day]: allDay.value ? 1 : 0 }))

	// computed last from dateTime repetition
	const lastFromDisplay = computed(() => from.value.plus(sequence.value))

	// computed last to dateTime repetition
	const lastToDisplay = computed(() => to.value.plus(sequence.value))

	// computed if the option is blocked by existing option
	const blockedOption = computed(() => optionsStore.find(from.value.toSeconds(), duration.value.as('seconds')) ? result.value !== StatusResults.Success :false)

	// computed if the option is addable
	const addable = computed(() => (!blockedOption.value && result.value !== StatusResults.Loading))
	const optionInfo = computed(() => blockedOption.value ? t('polls', 'Option already exists') : '')

	watch(() => allDay.value, () => {
		resetduratonUnits()
	})

	watch(() => fromInput.value, () => {
		onAnyChange()
	})

	function resetduratonUnits() {
		if (allDay.value) {
			// change date units, when switching from time based to all day, since minutes and hours are not valid anymore
			if (durationInput.value.unit.key === DateUnitKeys.Minute || durationInput.value.unit.key === DateUnitKeys.Hour) {
				durationInput.value.unit = dateUnits.find(unit => unit.key === DateUnitKeys.Day)
			}
		}
	}

	function onAnyChange() {
		result.value = addable.value ? StatusResults.None : StatusResults.Error
	}

	async function addOption() {
		result.value = StatusResults.Loading

		try {
			const newOption = await optionsStore.add({
				text: '',
				timestamp: from.value.toSeconds(),
				duration: duration.value.as('seconds'),
			})

			if (sequenceInput.value.repetitions > 0) {
				await optionsStore.sequence({option: newOption, sequence: sequenceInput.value})
			}
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
		<h2>{{ t('polls', 'Add times') }}</h2>
		<NcCheckboxRadioSwitch v-model="allDay">
			{{ t('polls', 'All day') }}
		</NcCheckboxRadioSwitch>
	</div>

	<div class="add-container">
		<div class="select-container">
			<div class="selection from">
				<DateTimePicker v-model="fromInput"
					:use-time="!allDay" />
			</div>

			<div class="selection duration">
				<div>
					<InputDiv v-model="durationInput.amount"
						:label="t('polls', 'Duaration')"
						type="number"
						inputmode="numeric"
						:num-min="0"
						use-num-modifiers />
					<NcSelect v-model="durationInput.unit"
						class="time-unit"
						:input-label="t('polls', 'Duration time unit')"
						:clearable="false"
						:filterable="false"
						:options="allDay ? dateOnlyUnits : dateUnits"
						label="name" />
				</div>
			</div>

			<div class="selection repetition">
				<div class="set-repetition">
					<InputDiv v-model="sequenceInput.repetitions"
						:label="t('polls', 'Repetitions')"
						type="number"
						inputmode="numeric"
						:num-min="0"
						use-num-modifiers />

					<NcSelect v-if="sequenceInput.repetitions > 0"
						v-model="sequenceInput.unit"
						class="time-unit"
						:input-label="t('polls', 'Step unit')"
						:clearable="false"
						:filterable="false"
						:options="dateUnits"
						label="name" />

					<InputDiv v-if="sequenceInput.repetitions > 0"
						v-model="sequenceInput.stepWidth"
						:label="t('polls', 'Step width')"
						type="number"
						inputmode="numeric"
						use-num-modifiers />
				</div>
			</div>
		</div>

	</div>


	<div class="preview-container">
		<h2>{{ t('polls', 'Preview') }}</h2>
		<div class="preview">

			<div class="preview-container">
				<div class="preview-group">
					<div class="preview___entry">
						<DateBox class="from"
							:date="from.toJSDate()"
							:duration-sec="sameDay ? duration.as('seconds') : 0"
							:hide-time="allDay" />

						<div v-if="durationInput.amount > 0 && !sameDay" class="preview___devider">
							<span>{{ ' - ' }} </span>
						</div>

						<DateBox v-if="!sameDay" class="to"
							:date="to.toJSDate()"
							:duration-sec="sameDay ? duration.as('seconds') : 0"
							:hide-time="allDay" />
					</div>
					<div v-if="sequenceInput.repetitions > 0" class="preview___repetitions">
						<span>{{ n('polls', '%n repetition until', '%n repetitions until', sequenceInput.repetitions) }}</span>
						<div class="preview___entry">
							<DateBox class="from"
								:date="lastFromDisplay.toJSDate()"
								:duration-sec="sameDay ? duration.as('seconds') : 0"
								:hide-time="allDay" />

							<div v-if="durationInput.amount > 0 && !sameDay" class="preview___devider">
								<span>{{ ' - ' }} </span>
							</div>

							<DateBox v-if="!sameDay" class="to"
								:date="lastToDisplay.toJSDate()"
								:duration-sec="sameDay ? duration.as('seconds') : 0"
								:hide-time="allDay" />
						</div>
					</div>

				</div>
				<div :class="['duration-info', { error: blockedOption }]">
					{{ optionInfo }}
				</div>
			</div>

			<CheckIcon v-if="result === StatusResults.Success"
				class="date-added"
				:title="t('polls', 'Added')"
				:fill-color="successColor"
				:size="26" />

			<NcButton v-else
				class="date-add-button"
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
	.header-container {
		display: flex;
		justify-content: space-between;
		column-gap: 1rem;

	}
	.select-container {
		.selection {
			display: flex;
			flex-wrap: wrap;
			align-items: start;

			> div {
				flex: 1;
				column-gap: 1rem;
				display: flex;
				flex-wrap: wrap;
				.v-select.select.time-unit {
					min-width: 11rem;
				}
			}
		}
	}

	.select-duration {
		display: flex;
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
		flex-wrap: wrap;

		.duration-info {
			font-size: 0.8em;
			color: var(--color-text-maxcontrast);
			font-weight: 600;
			text-align: center;
			&.error {
				color: var(--color-error-text);
			}
		}

		.preview-group {
			display: flex;
			flex: 1 auto;
			row-gap: 0.6rem;
			column-gap: 2rem;
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
		.preview___repetitions {
			display: flex;
			flex-direction: column;
			align-items: center;
			font-weight: bold;
			font-size: 0.8em;
			opacity: 0.6;
			background-color: var(--color-background-darker);
			padding: 1rem 1rem 0 1rem;
			border-radius: var(--border-radius-container-large);
		}

		.preview___devider {
			flex: 0 auto;
			display: flex;
			align-items: center;
		}

		button {
			flex: 1 0 4.5rem;
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
