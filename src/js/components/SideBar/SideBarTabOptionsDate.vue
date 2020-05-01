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
				confirm
				style="width: inherit;"
				@change="addOption(lastOption)" />
		</div>

		<div class="config-box">
			<label class="title icon-history">
				{{ t('polls', 'Shift all date options') }}
			</label>
			<div>
				<div class="selectUnit">
					<Actions>
						<ActionButton icon="icon-play-previous" @click="shift.step--">
							{{ t('polls', 'Decrease unit') }}
						</ActionButton>
					</Actions>
					<input v-model="shift.step">
					<Actions>
						<ActionButton icon="icon-play-next" @click="shift.step++">
							{{ t('polls', 'Increase unit') }}
						</ActionButton>
					</Actions>
					<Multiselect
						v-model="shift.unit"
						:options="dateUnits"
						label="name"
						track-by="value" />
					<ButtonDiv icon="icon-history"
						:title="t('polls', 'Shift')"
						@click="shiftDates(shift)" />
				</div>
			</div>
		</div>

		<div class="config-box">
			<label class="title icon-calendar-000">
				{{ t('polls', 'Available Options') }}
			</label>
			<transition-group is="ul">
				<OptionItem v-for="(option) in sortedOptions"
					:key="option.id"
					:option="option"
					type="datePoll"
					tag="li">
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
				</OptionItem>
			</transition-group>
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
import OptionItem from '../Base/OptionItem'

export default {
	name: 'SideBarTabOptionsDate',

	components: {
		Actions,
		ActionButton,
		DatetimePicker,
		Modal,
		Multiselect,
		OptionItem
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
				minuteStep: 1,
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
						weekdaysMin: moment.weekdaysMin()
					}
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
	.option-item {
		border-bottom: 1px solid var(--color-border);
	}

	.selectUnit {
		display: flex;
		align-items: center;
		input {
			margin: 0 4px;
			width: 40px;
		}
		.multiselect {
			margin: 0 8px;
			width: unset !important;
			flex: 1;
		}
	}
</style>
