<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDateTimePicker v-model="pickerSelection"
		v-bind="pickerOptions"
		:open.sync="pickerOpen"
		style="width: inherit;"
		@change="changedDate"
		@pick="pickedDate">
		<template #input>
			<NcButton variant="primary" :aria-label="buttonAriaLabel">
				<template #icon>
					<AddDateIcon />
				</template>
				<template v-if="caption">
					{{ caption }}
				</template>
			</NcButton>
		</template>

		<template #header>
			<NcCheckboxRadioSwitch :checked.sync="useRange" class="range" type="switch">
				{{ t('polls', 'Select range') }}
			</NcCheckboxRadioSwitch>
			<div class="picker-buttons">
				<NcButton v-if="useTime"
					@click="toggleTimePanel">
					<template #default>
						{{ showTimePanel ? t('polls', 'Change date') : t('polls', 'Change time') }}
					</template>
				</NcButton>
				<NcButton v-if="useTime"
					@click="removeTime">
					<template #default>
						{{ t('polls', 'Remove time') }}
					</template>
				</NcButton>
				<NcButton v-else
					:disabled="!dateOption.isValid"
					@click="addTime">
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
				<NcButton v-if="dateOption.option.duration >= 0 && !added" variant="primary" @click="addOption">
					{{ t('polls', 'Add') }}
				</NcButton>
				<CheckIcon v-if="added"
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

<script>

import { showError, showSuccess } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { NcButton, NcCheckboxRadioSwitch, NcDateTimePicker } from '@nextcloud/vue'
import { FlexSpacer } from '../Base/index.js'
import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

export default {
	name: 'OptionsDateAdd',

	components: {
		AddDateIcon,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDateTimePicker,
		FlexSpacer,
		CheckIcon,
	},

	props: {
		caption: {
			type: String,
			default: undefined,
		},
	},

	data() {
		return {
			pickerSelection: null,
			changed: false,
			pickerOpen: false,
			useRange: false,
			useTime: false,
			showTimePanel: false,
			lastPickedDate: moment(null),
			added: false,
			successColor: getComputedStyle(document.documentElement).getPropertyValue('--color-success'),
		}
	},

	computed: {
		buttonAriaLabel() {
			return this.caption ?? t('polls', 'Add date')
		},
		dateOption() {
			let from = moment()
			let to = moment()
			let text = ''

			if (Array.isArray(this.pickerSelection)) {
				from = moment(this.pickerSelection[0])
				to = moment(this.pickerSelection[1])

				// if a sigle day is selected while useRange is true and the paicker did not return a
				// valid selection, use the single selected day
				if (this.useRange && this.lastPickedDate) {
					from = moment(this.lastPickedDate).hour(from.hour()).minute(from.minute())
					to = moment(this.lastPickedDate).hour(to.hour()).minute(to.minute())
				}
			} else {
				from = moment(this.pickerSelection).startOf(this.useTime ? 'minute' : 'day')
				to = moment(this.pickerSelection).startOf(this.useTime ? 'minute' : 'day')
			}

			if (this.useRange) {
				if (this.useTime) {
					if (moment(from).startOf('day').valueOf() === moment(to).startOf('day').valueOf()) {
						text = `${from.format('ll LT')} - ${to.format('LT')}`
					} else {
						text = `${from.format('ll LT')} - ${to.format('ll LT')}`
					}
				} else {
					from = from.startOf('day')
					to = to.startOf('day')
					if (moment(from).startOf('day').valueOf() === moment(to).startOf('day').valueOf()) {
						text = from.format('ll')
					} else {
						text = `${from.format('ll')} - ${to.format('ll')}`
					}
				}
			} else if (this.useTime) {
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
					duration: moment(to).add(this.useTime ? 0 : 1, 'day').unix() - from.unix(),
				},
			}
		},

		tempFormat() {
			if (this.useTime) {
				return moment.localeData().longDateFormat('L LT')
			}
			return moment.localeData().longDateFormat('L')

		},

		firstDOW() {
			// vue2-datepicker needs 7 for sunday
			if (moment.localeData()._week.dow === 0) {
				return 7
			}
			return moment.localeData()._week.dow

		},

		pickerOptions() {
			return {
				appendToBody: true,
				editable: false,
				minuteStep: 5,
				type: this.useTime ? 'datetime' : 'date',
				range: this.useRange,
				key: this.useRange ? 'range-on' : 'range-off',
				showSecond: false,
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
			if (this.useRange && !Array.isArray(this.pickerSelection)) {
				this.pickerSelection = [this.pickerSelection, this.pickerSelection]
			} else if (!this.useRange && Array.isArray(this.pickerSelection)) {
				this.pickerSelection = this.pickerSelection[0]
			}
		},
	},

	methods: {
		// if picker returned a valid selection
		changedDate(value, type) {
			this.added = false
			this.changed = true
		},

		// The date picker does not update the values, if useRange is true and
		// a single day is selected without a second click. Therfore we store
		// the picked day to define the correct date selection inside the
		// computed dateOptions property
		pickedDate(value) {
			// we rely on the behavior, that the changed event is fired before the picked event
			// if the picker already returned a valid selection before, ignore picked date
			this.added = false
			if (this.changed) {
				// reset changed status
				this.changed = false
				// reset the last picked date
				this.lastPickedDate = null
			} else {
				// otherwise store the selection of the picked date
				this.lastPickedDate = moment(value)
			}
			// keep picker open
			this.pickerOpen = true
		},

		addTime() {
			this.added = false
			if (this.useRange) {
				// make sure, the pickerSelection is set to the last displayed status
				this.pickerSelection = [this.dateOption.from.valueOf(), this.dateOption.to.valueOf()]
			}
			this.useTime = true
			this.showTimePanel = true
		},

		removeTime() {
			this.added = false
			if (this.useRange) {
				// make sure, the pickerSelection is set to the last displayed status
				this.pickerSelection = [this.dateOption.from.valueOf(), this.dateOption.to.valueOf()]
			}
			this.useTime = false
			this.showTimePanel = false
		},

		toggleTimePanel() {
			if (this.showTimePanel) {
				this.changed = false
			} else if (this.useRange) {
				// make sure, the pickerSelection is set to the last displayed status
				this.pickerSelection = [this.dateOption.from.valueOf(), this.dateOption.to.valueOf()]
			}
			this.showTimePanel = !this.showTimePanel
		},

		async addOption() {
			if (this.useRange) {
				// make sure, the pickerSelection is set to the last displayed status
				this.pickerSelection = [this.dateOption.from.valueOf(), this.dateOption.to.valueOf()]
			}
			try {
				await this.$store.dispatch('options/add', this.dateOption.option)
				this.added = true
				showSuccess(t('polls', '{optionText} added', { optionText: this.dateOption.text }))
			} catch (error) {
				if (error.response.status === 409) {
					showError(t('polls', '{optionText} already exists', { optionText: this.dateOption.text }))
				} else {
					showError(t('polls', 'Error adding {optionText}', { optionText: this.dateOption.text }))
				}
			}
		},
	},
}

</script>

<style lang="scss">

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
