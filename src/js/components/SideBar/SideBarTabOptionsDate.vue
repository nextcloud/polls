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
	<div>
		<div class="config-box">
			<label class="title icon-add">
				{{ t('polls', 'Add a date option') }}
			</label>
			<DatetimePicker v-model="lastOption"
				v-bind="optionDatePicker"
				style="width:100%"
				confirm
				@change="addOption(lastOption)" />
		</div>

		<div class="config-box">
			<label class="title icon-history">
				{{ t('polls', 'Shift all date options') }}
			</label>
			<div>
				<div class="selectUnit">
					<input v-model="shift.step">
					<Multiselect
						v-model="shift.unit"
						:options="dateUnits"
						label="name"
						track-by="value" />
				</div>
			</div>
			<div>
				<ButtonDiv icon="icon-history" :title="t('polls', 'Shift')"
					@click="shiftDates(shift)" />
			</div>
		</div>

		<div class="config-box">
			<label class="title icon-calendar-000">
				{{ t('polls', 'Available Options') }}
			</label>
			<ul class="">
				<PollItemDate v-for="(option) in sortedOptions"
					:key="option.id"
					:option="option">
					<template v-slot:actions>
						<Actions v-if="acl.allowEdit" class="action">
							<ActionButton icon="icon-delete" @click="removeOption(option)">
								{{ t('polls', 'Delete option') }}
							</ActionButton>
						</Actions>

						<Actions v-if="acl.allowEdit" class="action">
							<ActionButton icon="icon-add" @click="cloneOptionModal(option)">
								{{ t('polls', 'Clone option') }}
							</ActionButton>
						</Actions>
					</template>
				</PollItemDate>
			</ul>
		</div>
		<Modal v-if="modal" :can-close="false">
			<div class="modal__content">
				<h2>{{ t('polls', 'Clone to option sequence') }}</h2>

				<p>{{ t('polls', 'Create a sequence of date options starting with {dateOption}.', { dateOption: moment.unix(sequence.baseOption.timestamp).format('LLLL')}) }}</p>
				<div>
					<h3> {{ t('polls', 'Step width: ') }} </h3>
					<input v-model="sequence.step">
					<h3> {{ t('polls', 'Step unit: ') }} </h3>
					<Multiselect
						v-model="sequence.unit"
						:options="dateUnits"
						label="name"
						track-by="value" />
					<h3> {{ t('polls', 'Number of items to create: ') }} </h3>
					<input v-model="sequence.amount">
				</div>

				<div class="modal__buttons">
					<ButtonDiv :title="t('polls', 'Cancel')" @click="closeModal" />
					<ButtonDiv :primary="true" :title="t('polls', 'OK')" @click="createSequence" />
				</div>
			</div>
		</Modal>
	</div>
</template>

<script>
import { Actions, ActionButton, DatetimePicker, Modal, Multiselect } from '@nextcloud/vue'
import { mapGetters, mapState } from 'vuex'
import PollItemDate from '../Base/PollItemDate'

export default {
	name: 'SideBarTabOptionsDate',

	components: {
		Actions,
		ActionButton,
		DatetimePicker,
		Modal,
		Multiselect,
		PollItemDate
	},

	data() {
		return {
			lastOption: '',
			modal: false,
			sequence: {
				baseOption: {},
				unit: { name: t('polls', 'Week'), value: 'week' },
				step: 1,
				amount: 1
			},
			dateUnits: [
				{ name: t('polls', 'Minute'), value: 'minute' },
				{ name: t('polls', 'Hour'), value: 'hour' },
				{ name: t('polls', 'Day'), value: 'day' },
				{ name: t('polls', 'Week'), value: 'week' },
				{ name: t('polls', 'Month'), value: 'month' },
				{ name: t('polls', 'Year'), value: 'year' }
			],
			shift: {
				step: 1,
				unit: { name: t('polls', 'Week'), value: 'week' }
			}
		}
	},

	computed: {
		...mapState({
			options: state => state.options,
			acl: state => state.acl
		}),

		...mapGetters(['sortedOptions']),

		firstDOW() {
			// vue2-datepicker needs 7 for sunday
			if (moment.localeData()._week.dow === 0) {
				return 7
			} else {
				return moment.localeData()._week.dow
			}
		},

		optionDatePicker() {
			return {
				editable: false,
				type: 'datetime',
				format: moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT'),

				// TODO: use this for version 2.x
				lang: OC.getLanguage().split('-')[0],
				firstDayOfWeek: this.firstDOW,

				// TODO: use this from version 3.x on
				// lang: {
				// 	formatLocale: {
				//		firstDayOfWeek: this.firstDOW,
				// 		months: moment.months(),
				// 		monthsShort: moment.monthsShort(),
				// 		weekdays: moment.weekdays(),
				// 		weekdaysMin: moment.weekdaysMin()
				// 	}
				// },
				placeholder: t('polls', 'Click to add a date'),
				timePickerOptions: {
					start: '00:00',
					step: '00:30',
					end: '23:30'
				}
			}
		}
	},

	methods: {

		addOption(pollOptionText) {
			this.$store.dispatch('addOptionAsync', { pollOptionText: pollOptionText })
		},

		shiftDates(payload) {
			const store = this.$store
			this.options.list.forEach(function(existingOption) {
				const option = Object.assign({}, existingOption)
				option.pollOptionText = moment(option.pollOptionText).add(payload.step, payload.unit.value).format('YYYY-MM-DD HH:mm:ss')
				option.timestamp = moment.utc(option.pollOptionText).unix()
				store.dispatch('updateOptionAsync', { option: option })
			})
		},

		closeModal() {
			this.modal = false
		},

		createSequence() {
			for (var i = 0; i < this.sequence.amount; i++) {
				this.$store.dispatch('addOptionAsync', {
					pollOptionText: moment.unix(this.sequence.baseOption.timestamp).add(
						this.sequence.step * (i + 1),
						this.sequence.unit.value
					).format('YYYY-MM-DD HH:mm:ss')
				})
			}
			this.modal = false
			this.sequence.baseOption = {}
		},

		cloneOptionModal(option) {
			this.modal = true
			this.sequence.baseOption = option
		},

		removeOption(option) {
			this.$store.dispatch('removeOptionAsync', { option: option })
		}

	}

}
</script>
<style lang="scss" scoped>
	.selectUnit {
		display: flex;
		input {
			width: 90px;
		}
		.multiselect {
			margin-top: 3px;
		}
	}
</style>
