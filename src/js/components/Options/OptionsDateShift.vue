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
				<InputDiv v-model="shift.step"
					use-num-modifiers
					@add="shift.step++"
					@subtract="shift.step--" />
				<Multiselect v-model="shift.unit"
					:options="dateUnits"
					label="name"
					track-by="value" />
				<ButtonDiv submit
					@click="shiftDates(shift)" />
			</div>
		</div>
	</ConfigBox>
</template>

<script>

import { mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import InputDiv from '../Base/InputDiv'
import moment from '@nextcloud/moment'
import { Multiselect } from '@nextcloud/vue'
import { dateUnits } from '../../mixins/dateMixins'

export default {
	name: 'OptionsDateShift',

	components: {
		ConfigBox,
		InputDiv,
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
			let options = []
			if (payload.step < 0) {
				options = this.options
			} else if (payload.step > 0) {
				options = this.options.slice().reverse()
			}
			options.forEach(function(existingOption) {
				const option = Object.assign({}, existingOption)
				option.timestamp = moment.unix(option.timestamp).add(payload.step, payload.unit.value).unix()
				option.pollOptionText = moment.unix(option.timestamp).format('YYYY-MM-DD HH:mm:ss')
				store.dispatch('poll/options/update', { option: option })
			})
		},
	},
}

</script>

<style lang="scss" scoped>
.selectUnit {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	.multiselect {
		margin: 0 8px;
		width: unset !important;
		min-width: 75px;
		flex: 1;
	}
}

</style>
