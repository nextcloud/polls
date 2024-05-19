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
	<InputDiv v-model="pollTitle"
		:signaling-class="checkTitle"
		type="text"
		@change="$emit('change')" />
</template>

<script>
import { mapState } from 'vuex'
import { InputDiv } from '../Base/index.js'

export default {
	name: 'ConfigTitle',

	components: {
		InputDiv,
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		checkTitle() {
			return this.pollConfiguration.title ? '' : 'error'
		},

		pollTitle: {
			get() {
				return this.pollConfiguration.title
			},
			set(value) {
				this.$store.commit('poll/setProperty', { title: value })
			},
		},
	},
}
</script>
