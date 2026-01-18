<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { DateTime, Duration } from 'luxon'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'

import { dateTimeUnitsKeyed, ceilDate } from '@/helpers/modules/dateHelpers'
import { useResizeObserver } from '../../composables/elementWidth'

import CheckIcon from 'vue-material-design-icons/Check.vue'

import InputDiv from '../Base/modules/InputDiv.vue'
import LuxonPicker from '../Base/modules/LuxonPicker.vue'
import OptionsPreviewBox from './OptionsPreviewBox.vue'

import type { DurationType, TimeZoneOption } from '../../Types/dateTime'
import type { AxiosError } from '@nextcloud/axios'
import type { Sequence, SimpleOption } from '@/stores/options.types'

import { useOptionsStore } from '@/stores/options'
import { useSessionStore } from '@/stores/session'
import { usePollStore } from '@/stores/poll'

const { isBelowWidthOffset } = useResizeObserver('add-date-options-container', 355)

const optionsStore = useOptionsStore()
const pollStore = usePollStore()
const sessionStore = useSessionStore()

const successColor = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-success',
)

/**
 * Ref to store the result of adding an option
 */
const result = ref('')

// *** refs for the inputs

/**
 * The sequence input used for the input fields
 * Initially set to 1 Week, but with 0 repetitions
 */
const sequenceInput = ref<Sequence>({
	unit: dateTimeUnitsKeyed.week,
	stepWidth: 1,
	repetitions: 0,
})

/**
 * The available dateTime options for the selects
 */
const dateTimeOptions = Object.entries(dateTimeUnitsKeyed).map(([key, value]) => ({
	id: key,
	value: value.id,
	name: value.name,
	timeOption: value.timeOption,
}))

/**
 * The available dateTime options filtered based on allDay
 */
const dateTimeOptionsFiltered = computed(() => {
	if (allDay.value) {
		return dateTimeOptions.filter((unit) => !unit.timeOption)
	}
	return dateTimeOptions
})

/**
 * The actual start dateTime used for the input
 * Initially set to the next quarter hour in the current timezone
 * @return ref DateTime
 */
const startDateTime = ref(
	ceilDate(DateTime.now().setZone(sessionStore.currentTimezoneName), 15),
)

/**
 * Resultin option based on the inputs
 */
const newOption = computed<SimpleOption>(() => {
	const dateTime = startDateTime.value.startOf(allDay.value ? 'day' : 'minute')

	return {
		text: '',
		duration: duration.value.as('seconds') || 0,
		timestamp: dateTime.toUnixInteger(),
		isoTimestamp:
			dateTime.setZone(sessionStore.currentTimezoneName).toISO() || '',
		isoDuration: duration.value.toISO() || 'PT0S',
	}
})

/**
 * The duration input used for the input fields
 * Initially set to 0 Days, which currently is equal to 1 Day if allDay is true
 * @return ref DurationType
 */
const durationInput = ref<DurationType>({
	unit: dateTimeUnitsKeyed.day,
	amount: 0,
})

/**
 * Reset duration units when switching between all day and time based options
 */
function resetduratonUnits(): void {
	if (allDay.value) {
		if (
			durationInput.value.unit.id === 'minute'
			|| durationInput.value.unit.id === 'hour'
		) {
			durationInput.value.unit = dateTimeUnitsKeyed.day
		}
	}
}

/**
 * Computed if the user's timezone is different from the poll's timezone
 */
const differentTimezones = computed(
	() =>
		pollStore.getTimezoneName
		!== Intl.DateTimeFormat().resolvedOptions().timeZone,
)

function setZone(): void {
	startDateTime.value = startDateTime.value.setZone(
		sessionStore.currentTimezoneName,
		{ keepLocalTime: true },
	)
}

/**
 * Computed duration as Duration from Luxon
 * Set duration to 1 Day if allDay is true and duration is 0
 */
const duration = computed(() =>
	durationInput.value.amount < 1 && allDay.value
		? Duration.fromObject({ day: 1 })
		: Duration.fromObject({
				[durationInput.value.unit.id]: durationInput.value.amount,
			}),
)

/**
 * Computed as boolean if the option is blocked by an existing option
 */
const blockedOption = computed(() => {
	const option = duplicateOption.value
	return option && !option.deleted
})

/**
 * Computed to find an existing option with the same dateTime and duration
 * @return found option or undefined
 */
const duplicateOption = computed(() => optionsStore.find(newOption.value))

/**
 * Ref as boolean to toggle between all day and time based options
 */
const allDay = ref(true)

/**
 * Ref as boolean to automatically vote yes for the new option
 */
const voteYes = ref(true)

/**
 * Computed boolean if the option is addable
 * An option is addable if it is not blocked and the result is not loading
 * Used to enable/disable the add button
 * @return computed boolean
 */
const addable = computed(() => !blockedOption.value && result.value !== 'loading')

// *** Miscellaneous captions

const optionInfoCaption = computed(() =>
	blockedOption.value && result.value !== 'success'
		? t('polls', 'Option already exists')
		: '',
)

const thisTimezoneCaption = computed(() =>
	t('polls', 'Your timezone ({timezoneName})', {
		timezoneName: Intl.DateTimeFormat().resolvedOptions().timeZone,
	}),
)
const thatTimezoneCaption = computed(() =>
	t('polls', 'Original timezone ({timezoneName})', {
		timezoneName: pollStore.getTimezoneName,
	}),
)

async function addOption(): Promise<void> {
	result.value = 'loading'

	try {
		await optionsStore.add(newOption.value, sequenceInput.value, voteYes.value)

		result.value = 'success'
	} catch (error) {
		if ((error as AxiosError).response?.status === 409) {
			showError(t('polls', 'Option already exists'))
			result.value = 'warning'
			return
		}

		showError(t('polls', 'Error adding Option'))
		result.value = 'error'
	}
}

// *** Props for the input components

const LuxonPickerProps = computed(() => ({
	useDayButtons: !isBelowWidthOffset.value,
	hideLabel: true,
	label: t('polls', 'Add a new date/time'),
	type: allDay.value ? 'date' : 'datetime-local',
}))

const TimeZoneSelectProps = computed(() => ({
	options: [
		{
			label: pollStore.getTimezoneName,
			value: 'poll',
		},
		{
			label: Intl.DateTimeFormat().resolvedOptions().timeZone,
			value: 'local',
		},
	],
	label: 'label',
	clearable: false,
	reduce: (option: TimeZoneOption) => option.value,
}))

const DurationInputProps = computed(() => ({
	numMin: 0,
	useNumModifiers: !isBelowWidthOffset.value,
	label: t('polls', 'Duration'),
}))

const DurationTimeUnitSelectProps = computed(() => ({
	inputLabel: t('polls', 'Duration time unit'),
	clearable: false,
	filterable: false,
	options: dateTimeOptionsFiltered.value,
	label: 'name',
}))

const SequenceRepetitionsInputProps = computed(() => ({
	numMin: 0,
	useNumModifiers: !isBelowWidthOffset.value,
	label: t('polls', 'Repetitions'),
}))

const SequenceStepWidthInputProps = computed(() => ({
	numMin: 0,
	useNumModifiers: !isBelowWidthOffset.value,
	label: t('polls', 'Step width'),
}))

const SequenceUnitSelectProps = computed(() => ({
	inputLabel: t('polls', 'Step unit'),
	clearable: false,
	filterable: false,
	options: dateTimeOptionsFiltered.value,
	label: 'name',
}))
</script>

<template>
	<div class="header-container">
		<h2>{{ t('polls', 'Add times') }}</h2>
		<NcCheckboxRadioSwitch
			v-model="allDay"
			@update:model-value="resetduratonUnits">
			{{ t('polls', 'All day') }}
		</NcCheckboxRadioSwitch>
	</div>

	<div id="add-date-options-container" class="add-container">
		<div class="select-container">
			<div class="selection from">
				<LuxonPicker v-model="startDateTime" v-bind="LuxonPickerProps" />
				<NcSelect
					v-if="differentTimezones"
					v-model="sessionStore.sessionSettings.timezoneName"
					v-bind="TimeZoneSelectProps"
					@update:model-value="setZone()" />
			</div>

			<div class="selection duration">
				<InputDiv
					v-model="durationInput.amount"
					v-bind="DurationInputProps"
					type="number"
					inputmode="numeric" />
				<NcSelect
					v-model="durationInput.unit"
					v-bind="DurationTimeUnitSelectProps"
					class="time-unit"
					label="name" />
			</div>

			<div class="selection repetition">
				<InputDiv
					v-model="sequenceInput.repetitions"
					v-bind="SequenceRepetitionsInputProps"
					type="number"
					inputmode="numeric" />

				<div v-if="sequenceInput.repetitions > 0" class="set-repetition">
					<InputDiv
						v-model="sequenceInput.stepWidth"
						v-bind="SequenceStepWidthInputProps"
						type="number"
						inputmode="numeric" />

					<NcSelect
						v-model="sequenceInput.unit"
						v-bind="SequenceUnitSelectProps"
						class="time-unit" />
				</div>
			</div>
			<div>
				<NcCheckboxRadioSwitch v-model="voteYes">
					{{ t('polls', 'Automatically vote "Yes" for new option.') }}
				</NcCheckboxRadioSwitch>
			</div>
		</div>
	</div>

	<div class="preview-container">
		<h2>{{ t('polls', 'Preview') }}</h2>

		<div class="preview">
			<OptionsPreviewBox
				class="local"
				:option="newOption"
				:sequence="sequenceInput"
				:timezone="Intl.DateTimeFormat().resolvedOptions().timeZone"
				:title="differentTimezones ? thisTimezoneCaption : undefined" />

			<OptionsPreviewBox
				v-if="differentTimezones"
				class="poll"
				:option="newOption"
				:sequence="sequenceInput"
				:timezone="pollStore.getTimezoneName"
				:title="differentTimezones ? thatTimezoneCaption : undefined" />

			<div :class="['duration-info', { error: blockedOption }]">
				{{ optionInfoCaption }}
			</div>

			<CheckIcon
				v-if="result === 'success' && blockedOption"
				class="date-added"
				:title="t('polls', 'Added')"
				:fill-color="successColor"
				:size="26" />

			<NcButton
				v-else
				class="date-add-button"
				:variant="'primary'"
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
		gap: 1rem;
		align-items: end;
		padding: 0 1rem;
		margin: 0.2rem 0;

		.v-select.select {
			// TODO: temporary tweak to fix width issue
			min-width: 12rem;
			max-width: 16rem;

			&.time-unit {
				min-width: 11rem;
			}
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
	inset-inline-start: -0.8rem;
}

.time-lock {
	border-style: solid;
	width: 22px;
	height: 0.5rem;
	margin-inline-start: 0.5rem;

	&.upper {
		border-width: 2px 2px 0 0;
		border-top-right-radius: 25%;
	}

	&.lower {
		border-width: 0 2px 2px 0;
		border-bottom-right-radius: 25%;
	}
}

.date-add-button {
	grid-row: 1 / span 4;
}

.preview {
	display: grid;
	align-items: center;
	grid-template-rows: auto;
	grid-template-columns: auto auto;
	grid-template-areas:
		'dateLocal repetitionLocal button'
		'timezoneLocal timezoneLocal button'
		'datePoll repetitionPoll button'
		'timezonePoll timezonePoll button'
		'info info .';

	> * {
		flex: 1 auto;
	}

	& .local {
		grid-row: 1;
		grid-column: 1;
	}
	& .poll {
		grid-row: 2;
		grid-column: 1;
		background-color: rgb(from var(--color-background-darker) r g b/0.6);
		border-radius: var(--border-radius-container-large);
		padding: 1rem;
	}
	& .date-added,
	.date-add-button {
		grid-row: 1 / span 4;
		grid-column: 3;
		align-self: center;
		justify-self: center;
	}
	.duration-info {
		grid-row: 3;
		grid-column: 1 / span 2;
		font-size: 0.8em;
		color: var(--color-text-maxcontrast);
		font-weight: 600;
		text-align: center;
		&.error {
			color: var(--color-error-text);
		}
	}

	// button {
	// 	flex: 1 0 4.5rem;
	// }
}
</style>
