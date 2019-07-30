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

<template lang="html">
	<div>
		<button class="shift-dates icon-history" @click="shiftDatesDlg()">
			{{ t('polls', 'Shift dates') }}
		</button>

		<modal-dialog>
			<div>
				<div class="selectUnit">
					<input v-model="move.step">
					<Multiselect v-model="move.unit" :options="move.units" />
				</div>
			</div>
		</modal-dialog>
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue'
import { mapMutations } from 'vuex'

export default {
	name: 'ShiftDates',
	components: {
		Multiselect
	},

	data() {
		return {
			move: {
				step: 1,
				unit: 'week',
				units: ['minute', 'hour', 'day', 'week', 'month', 'year']
			}
		}
	},

	methods: {
		...mapMutations(['datesShift']),

		shiftDatesDlg() {
			const params = {
				title: t('polls', 'Shift all date options'),
				text: t('polls', 'Shift all dates for '),
				buttonHideText: t('polls', 'Cancel'),
				buttonConfirmText: t('polls', 'Apply'),
				onConfirm: () => {
					this.datesShift(this.move)
				}
			}
			this.$modal.show(params)
		}
	}
}
</script>

<style lang="scss" scoped>
.shift-dates {
	background-repeat: no-repeat;
	background-position: 10px center;
	min-width: 16px;
	min-height: 16px;
	padding: 10px;
	padding-left: 34px;
	text-align: left;
	margin: 0;
}
	.selectUnit {
		display: flex;
		align-items: center;
		flex-wrap: nowrap;
		> label {
			padding-right: 4px;
		}
	}
</style>
