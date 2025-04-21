<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { n, t } from '@nextcloud/l10n'

import NcButton, { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { DateTime, Duration } from 'luxon'
import CheckIcon from 'vue-material-design-icons/Check.vue'

import { InputDiv } from '../Base/index.ts'
import DateTimePicker from '../Base/modules/DateTimePicker.vue'
import { useSessionStore } from '../../stores/session'
import { useOptionsStore, Sequence } from '../../stores/options'
import { StatusResults } from '../../Types'
import { DurationType, dateTimeUnitsKeyed } from '../../constants/dateUnits.ts'

import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { AxiosError } from '@nextcloud/axios'

import { useResizeObserver } from '../../composables/elementWidth.ts'
import DateBox from '../Base/modules/DateBox.vue'

const { isBelowWidthOffset } = useResizeObserver('add-date-options-container', 355)

const sessionStore = useSessionStore()
const optionsStore = useOptionsStore()

const timeStepMinutes = 15
const successColor = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-success',
)
const result = ref(StatusResults.None)

const dateTimeOptions = Object.entries(dateTimeUnitsKeyed).map(([key, value]) => ({
	id: key,
	value: value.id,
	name: value.name,
	timeOption: value.timeOption,
}))

const dateTimeOptionsFiltered = computed(() => {
	if (allDay.value) {
		return dateTimeOptions.filter((unit) => !unit.timeOption)
	}
	return dateTimeOptions
})

// *** refs for the inputs
// allDay is a boolean to toggle between all day and time based options
const allDay = ref(true)

// Vote yes for new options
const voteYes = ref(true)

// set initial time mark to the next full quater of the hour
const fromInput = ref(
	new Date(
		new Date().setMinutes(
			Math.ceil((new Date().getMinutes() / 60) * (60 / timeStepMinutes))
				* timeStepMinutes,
			0,
			0,
		),
	),
)

// set initial duration to one Day
const durationInput = ref<DurationType>({
	unit: dateTimeUnitsKeyed.day,
	amount: 0,
})

// set initial sequence to one week but disabled
const sequenceInput = ref<Sequence>({
	unit: dateTimeUnitsKeyed.week,
	stepWidth: 1,
	repetitions: 0,
})

// computed from as DateTime from Luxon
const from = computed(() => {
	const dateFrom = DateTime.fromJSDate(fromInput.value).setLocale(
		sessionStore.currentUser.languageCode,
	)
	// if the option is an all day option, the time is set to 00:00
	if (allDay.value) {
		return dateFrom
			.startOf('day')
			.setLocale(sessionStore.currentUser.languageCode)
	}
	return dateFrom
})

// computed duration as Duration from Luxon
// Set duration to 1 Day if allDay is true and duration is 0
const duration = computed(() =>
	durationInput.value.amount < 1 && allDay.value
		? Duration.fromObject({ day: 1 })
		: Duration.fromObject({
				[durationInput.value.unit.id]: durationInput.value.amount,
			}),
)

// computed sequence as Duration from Luxon
// Set sequence to 0 if repetitions are 0
const sequence = computed(() =>
	sequenceInput.value.repetitions > 0
		? Duration.fromObject({
				[sequenceInput.value.unit.id]:
					sequenceInput.value.stepWidth * sequenceInput.value.repetitions,
			})
		: Duration.fromObject({ millisecond: 0 }),
)

// computed last from dateTime repetition
const lastFrom = computed(() => from.value.plus(sequence.value))

// computed if the option is blocked by an existing option
const blockedOption = computed(() => {
	const option = sameOption.value
	return option && !option.deleted
})

const sameOption = computed(() => {
	const option = optionsStore.find(
		from.value.toSeconds(),
		duration.value.as('seconds'),
	)
	return option
})

// computed if the option is addable
const addable = computed(
	() => !blockedOption.value && result.value !== StatusResults.Loading,
)
const optionInfo = computed(() =>
	blockedOption.value && result.value !== StatusResults.Success
		? t('polls', 'Option already exists')
		: '',
)

watch(
	() => allDay.value,
	() => {
		resetduratonUnits()
	},
)

watch(
	() => fromInput.value,
	() => {
		onAnyChange()
	},
)

function resetduratonUnits(): void {
	if (allDay.value) {
		// change date units, when switching from time based to all day, since minutes and hours are not valid anymore
		if (
			durationInput.value.unit.id === 'minute'
			|| durationInput.value.unit.id === 'hour'
		) {
			durationInput.value.unit = dateTimeUnitsKeyed.day
		}
	}
}

function onAnyChange(): void {
	result.value = addable.value ? StatusResults.None : StatusResults.Error
}

async function addOption(): Promise<void> {
	result.value = StatusResults.Loading

	try {
		await optionsStore.add(
			{
				text: '',
				timestamp: from.value.toSeconds(),
				duration: duration.value.as('seconds'),
			},
			sequenceInput.value,
			voteYes.value,
		)

		result.value = StatusResults.Success
		showSuccess(t('polls', 'Option added'))
	} catch (error) {
		if ((error as AxiosError).response?.status === 409) {
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

	<div id="add-date-options-container" class="add-container">
		<div class="select-container">
			<div class="selection from">
				<DateTimePicker
					v-model="fromInput"
					:use-day-buttons="!isBelowWidthOffset"
					hide-label
					:label="t('polls', 'Add a new date/time')"
					:type="allDay ? 'date' : 'datetime-local'" />
			</div>

			<div class="selection duration">
				<InputDiv
					v-model="durationInput.amount"
					:label="t('polls', 'Duration')"
					type="number"
					inputmode="numeric"
					:num-min="0"
					:use-num-modifiers="!isBelowWidthOffset" />
				<NcSelect
					v-model="durationInput.unit"
					class="time-unit"
					:input-label="t('polls', 'Duration time unit')"
					:clearable="false"
					:filterable="false"
					:options="dateTimeOptionsFiltered"
					label="name" />
			</div>

			<div class="selection repetition">
				<InputDiv
					v-model="sequenceInput.repetitions"
					:label="t('polls', 'Repetitions')"
					type="number"
					inputmode="numeric"
					:num-min="0"
					:use-num-modifiers="!isBelowWidthOffset" />

				<div v-if="sequenceInput.repetitions > 0" class="set-repetition">
					<InputDiv
						v-model="sequenceInput.stepWidth"
						:label="t('polls', 'Step width')"
						type="number"
						inputmode="numeric"
						:use-num-modifiers="!isBelowWidthOffset" />

					<NcSelect
						v-model="sequenceInput.unit"
						class="time-unit"
						:input-label="t('polls', 'Step unit')"
						:clearable="false"
						:filterable="false"
						:options="dateTimeOptions"
						label="name" />
				</div>
			</div>
			<div>
				<NcCheckboxRadioSwitch v-model="voteYes">
					{{ t('polls', 'Automatically vote "yes" for new option.') }}
				</NcCheckboxRadioSwitch>
			</div>
		</div>
	</div>

	<div class="preview-container">
		<h2>{{ t('polls', 'Preview') }}</h2>
		<div class="preview">
			<div class="preview-container">
				<div class="preview-group">
					<div class="preview___entry">
						<DateBox
							class="from"
							:luxon-date="from"
							:luxon-duration="duration" />
					</div>
					<div
						v-if="sequenceInput.repetitions > 0"
						class="preview___repetitions">
						<span>{{
							n(
								'polls',
								'%n repetition until',
								'%n repetitions until',
								sequenceInput.repetitions,
							)
						}}</span>
						<div class="preview___entry">
							<DateBox
								class="from"
								:luxon-date="lastFrom"
								:luxon-duration="duration" />
						</div>
					</div>
				</div>
				<div :class="['duration-info', { error: blockedOption }]">
					{{ optionInfo }}
				</div>
			</div>

			<CheckIcon
				v-if="result === StatusResults.Success && blockedOption"
				class="date-added"
				:title="t('polls', 'Added')"
				:fill-color="successColor"
				:size="26" />

			<NcButton
				v-else
				class="date-add-button"
				:variant="ButtonVariant.Primary"
				:disabled="!addable"
				@click="addOption">
				{{ t('polls', 'Add') }}
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss">
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
	display: flex;
	flex-direction: column;
	min-height: 5.4rem;
	flex: 1 18rem;
	.to,
	.from {
		display: flex;
		align-items: center;
	}

	.selection {
		display: flex;
		flex-wrap: wrap;
		column-gap: 1rem;
		align-items: start;
		padding: 0 1rem;
		margin: 0.2rem 0;

		.v-select.select.time-unit {
			min-width: 11rem;
		}

		&.repetition {
			border-radius: var(--border-radius-container-large);
			background-color: rgb(from var(--color-background-darker) r g b / 0.6);
			padding: 1rem 1rem;
		}

		.set-repetition {
			display: flex;
			column-gap: 1rem;
			flex-wrap: wrap;
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
	> * {
		flex: 1 auto;
	}

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
		// flex: 1 auto;
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
</style>
