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
	<ConfigBox :title="t('polls', 'Shift all date options')" icon-class="icon-polls-move">
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
				<Multiselect v-model="shift.unit"
					:options="dateUnits"
					label="name"
					track-by="value" />
				<ButtonDiv icon="icon-history"
					:title="t('polls', 'Shift')"
					@click="shiftDates(shift)" />
			</div>
		</div>
	</ConfigBox>
</template>

<script>

import { mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import moment from '@nextcloud/moment'
import { Actions, ActionButton, Multiselect } from '@nextcloud/vue'
import { dateUnits } from '../../mixins/dateMixins'

export default {
	name: 'OptionAddDate',

	components: {
		Actions,
		ActionButton,
		ConfigBox,
		Multiselect,
	},

	mixins: [dateUnits],

	data() {
		return {
			sequence: {
				baseOption: {},
				unit: { name: t('polls', 'Week'), value: 'week' },
				step: 1,
				amount: 1,
			},
			shift: {
				step: 1,
				unit: { name: t('polls', 'Week'), value: 'week' },
			},
		}
	},

	computed: {
		...mapState({
			options: state => state.poll.options.list,
		}),

		firstDOW() {
			// vue2-datepicker needs 7 for sunday
			if (moment.localeData()._week.dow === 0) {
				return 7
			} else {
				return moment.localeData()._week.dow
			}
		},
	},

	methods: {
		shiftDates(payload) {
			const store = this.$store
			this.options.forEach(function(existingOption) {
				const option = Object.assign({}, existingOption)
				option.pollOptionText = moment(option.pollOptionText).add(payload.step, payload.unit.value).format('YYYY-MM-DD HH:mm:ss')
				option.timestamp = moment.utc(option.pollOptionText).unix()
				store.dispatch('options/updateSingle', { option: option })
			})
		},
	},
}

</script>

<style lang="scss" scoped>
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
		min-width: 75px;
		flex: 1;
	}
}

</style>
