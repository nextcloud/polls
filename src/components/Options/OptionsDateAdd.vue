<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'
import NcButton, { ButtonVariant } from '@nextcloud/vue/components/NcButton'

import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

import { FlexSpacer } from '../Base/index.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { AxiosError } from '@nextcloud/axios'

type DateOption = {
	isValid: boolean
	from: moment.Moment
	to: moment.Moment
	text: string
	option: {
		timestamp: number
		duration: number
		text: string
	}
}

const optionsStore = useOptionsStore()
const props = defineProps({
	caption: {
		type: String,
		default: undefined,
	},
})

const pickerSelection = ref<[moment.Moment, moment.Moment]>([moment(), moment()])
const changed = ref(false)
const pickerOpen = ref(false)
const useRange = ref(false)
const useTime = ref(false)
const showTimePanel = ref(false)
const lastPickedDate = ref(moment(null))
const added = ref(false)
const successColor = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-success',
)

const tempFormat = computed(() => {
	if (useTime.value) {
		return moment.localeData().longDateFormat('L LT')
	}
	return moment.localeData().longDateFormat('L')
})

const firstDOW = computed<number>(() => {
	// vue2-datepicker needs 7 for sunday
	if (moment.localeData()._week.dow === 0) {
		return 7
	}
	return moment.localeData()._week.dow
})

const pickerOptions = computed(() => ({
	appendToBody: true,
	editable: false,
	minuteStep: 5,
	type: useTime.value ? 'datetime' : 'date',
	range: useRange.value,
	key: useRange.value ? 'range-on' : 'range-off',
	showSecond: false,
	showTimePanel: showTimePanel.value,
	valueType: 'timestamp',
	format: tempFormat.value,
	placeholder: t('polls', 'Click to add an option'),
	lang: {
		formatLocale: {
			firstDayOfWeek: firstDOW.value,
			months: moment.months(),
			monthsShort: moment.monthsShort(),
			weekdays: moment.weekdays(),
			weekdaysMin: moment.weekdaysMin(),
		},
	},
}))

const dateOption = computed<DateOption>(() => {
	let from = moment()
	let to = moment()
	let text = ''

	if (Array.isArray(pickerSelection.value)) {
		from = moment(pickerSelection.value[0])
		to = moment(pickerSelection.value[1])

		// if a sigle day is selected while useRange is true and the paicker did not return a
		// valid selection, use the single selected day
		if (useRange.value && lastPickedDate.value) {
			from = moment(lastPickedDate.value)
				.hour(from.hour())
				.minute(from.minute())
			to = moment(lastPickedDate.value).hour(to.hour()).minute(to.minute())
		}
	} else {
		from = moment(pickerSelection.value).startOf(
			useTime.value ? 'minute' : 'day',
		)
		to = moment(pickerSelection.value).startOf(useTime.value ? 'minute' : 'day')
	}

	if (useRange.value) {
		if (useTime.value) {
			if (
				moment(from).startOf('day').valueOf()
				=== moment(to).startOf('day').valueOf()
			) {
				text = `${from.format('ll LT')} - ${to.format('LT')}`
			} else {
				text = `${from.format('ll LT')} - ${to.format('ll LT')}`
			}
		} else {
			from = from.startOf('day')
			to = to.startOf('day')
			if (
				moment(from).startOf('day').valueOf()
				=== moment(to).startOf('day').valueOf()
			) {
				text = from.format('ll')
			} else {
				text = `${from.format('ll')} - ${to.format('ll')}`
			}
		}
	} else if (useTime.value) {
		text = from.format('ll LT')
	} else {
		text = from.startOf('day').format('ll')
	}

	return {
		isValid: from._isValid && to._isValid,
		from,
		to,
		text,
		option: {
			timestamp: from.unix(),
			duration:
				moment(to)
					.add(useTime.value ? 0 : 1, 'day')
					.unix() - from.unix(),
			text: '',
		},
	}
})

const buttonAriaLabel = computed(() => props.caption ?? t('polls', 'Add date'))

watch(
	() => useRange.value,
	() => {
		if (useRange.value && !Array.isArray(pickerSelection.value)) {
			pickerSelection.value = [pickerSelection.value, pickerSelection.value]
		} else if (!useRange.value && Array.isArray(pickerSelection.value)) {
			pickerSelection.value = pickerSelection.value[0]
		}
	},
)

/**
 *
 */
function changedDate() {
	added.value = false
	changed.value = true
}

/**
 * The date picker does not update the values, if useRange is true and
 * a single day is selected without a second click. Therfore we store
 * the picked day to define the correct date selection inside the
 * computed dateOptions property
 *
 * @param value - the picked date
 */
function pickedDate(value: Date) {
	// we rely on the behavior, that the changed event is fired before the picked event
	// if the picker already returned a valid selection before, ignore picked date
	added.value = false
	if (changed.value) {
		// reset changed status
		changed.value = false
		// reset the last picked date
		lastPickedDate.value = null
	} else {
		// otherwise store the selection of the picked date
		lastPickedDate.value = moment(value)
	}
	// keep picker open
	pickerOpen.value = true
}

/**
 *
 */
function addTime() {
	added.value = false
	if (useRange.value) {
		// make sure, the pickerSelection is set to the last displayed status
		pickerSelection.value = [
			dateOption.value.from.valueOf(),
			dateOption.value.to.valueOf(),
		]
	}
	useTime.value = true
	showTimePanel.value = true
}

/**
 *
 */
function removeTime() {
	added.value = false
	if (useRange.value) {
		// make sure, the pickerSelection is set to the last displayed status
		pickerSelection.value = [
			dateOption.value.from.valueOf(),
			dateOption.value.to.valueOf(),
		]
	}
	useTime.value = false
	showTimePanel.value = false
}

/**
 *
 */
function toggleTimePanel() {
	if (showTimePanel.value) {
		changed.value = false
	} else if (useRange.value) {
		// make sure, the pickerSelection is set to the last displayed status
		pickerSelection.value = [
			dateOption.value.from.valueOf(),
			dateOption.value.to.valueOf(),
		]
	}
	showTimePanel.value = !showTimePanel.value
}

/**
 *
 */
async function addOption() {
	if (useRange.value) {
		// make sure, the pickerSelection is set to the last displayed status
		pickerSelection.value = [
			dateOption.value.from.valueOf(),
			dateOption.value.to.valueOf(),
		]
	}
	try {
		await optionsStore.add(dateOption.value.option)
		added.value = true
		showSuccess(
			t('polls', '{optionText} added', { optionText: dateOption.value.text }),
		)
	} catch (error) {
		if ((error as AxiosError).response?.status === 409) {
			showError(
				t('polls', '{optionText} already exists', {
					optionText: dateOption.value.text,
				}),
			)
		} else {
			showError(
				t('polls', 'Error adding {optionText}', {
					optionText: dateOption.value.text,
				}),
			)
		}
	}
}
</script>

<template>
	<NcDateTimePicker
		v-model="pickerSelection"
		v-bind="pickerOptions"
		v-model:open="pickerOpen"
		style="width: inherit"
		@change="changedDate"
		@pick="pickedDate">
		<template #input>
			<NcButton :variant="ButtonVariant.Primary" :aria-label="buttonAriaLabel">
				<template #icon>
					<AddDateIcon />
				</template>
				<template v-if="caption">
					{{ caption }}
				</template>
			</NcButton>
		</template>

		<template #header>
			<NcCheckboxRadioSwitch v-model="useRange" class="range" type="switch">
				{{ t('polls', 'Select range') }}
			</NcCheckboxRadioSwitch>
			<div class="picker-buttons">
				<NcButton v-if="useTime" @click="toggleTimePanel">
					<template #default>
						{{
							showTimePanel
								? t('polls', 'Change date')
								: t('polls', 'Change time')
						}}
					</template>
				</NcButton>
				<NcButton v-if="useTime" @click="removeTime">
					<template #default>
						{{ t('polls', 'Remove time') }}
					</template>
				</NcButton>
				<NcButton v-else :disabled="!dateOption.isValid" @click="addTime">
					<template #default>
						{{ t('polls', 'Add time') }}
					</template>
				</NcButton>
			</div>
		</template>

		<template #footer>
			<div v-if="dateOption.isValid" class="selection">
				<div>
					{{ dateOption.text }}
				</div>
				<FlexSpacer />
				<NcButton
					v-if="dateOption.option.duration >= 0 && !added"
					:variant="ButtonVariant.Primary"
					@click="addOption">
					{{ t('polls', 'Add') }}
				</NcButton>
				<CheckIcon
					v-if="added"
					class="date-added"
					:title="t('polls', 'Added')"
					:fill-color="successColor"
					:size="26" />
			</div>
			<div v-else>
				{{ t('polls', 'Pick a day.') }}
			</div>
		</template>
	</NcDateTimePicker>
</template>

<style lang="scss" scoped>
.mx-input-wrapper .material-design-icon__svg {
	width: initial;
	height: initial;
}

.mx-input-wrapper {
	.mx-icon-calendar {
		display: none;
	}
}
</style>

<style lang="scss">
.picker-buttons {
	display: flex;
	justify-content: flex-end;
}

// overwrite default color
.mx-datepicker-main .date-added svg {
	fill: var(--color-success);
}

.selection {
	display: flex;
	align-items: center;
}

.range {
	flex: 1;
	justify-content: flex-end;
	margin: 8px;
}
</style>
