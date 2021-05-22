<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<CheckBoxDiv v-model="useNo" :label="label" />
</template>

<script>
import { mapState } from 'vuex'
import CheckBoxDiv from '../Base/CheckBoxDiv'

export default {
	name: 'ConfigUseNo',

	components: {
		CheckBoxDiv,
	},

	data() {
		return {
			label: t('polls', 'Delete vote when switched to "no"'),
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		useNo: {
			get() {
				return !this.poll.useNo
			},
			set(value) {
				this.$store.commit('poll/setProperty', { useNo: +(!value) })
				this.$emit('change')
			},
		},

	},
}
</script>
