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
	<div>
		<RadioGroupDiv v-model="pollAccess" :options="accessOptions" />
		<CheckBoxDiv v-model="pollImportant"
			class="indented"
			:disabled="pollAccess !== 'public'"
			:label="t('polls', 'Relevant for all users')" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import CheckBoxDiv from '../Base/CheckBoxDiv'
import RadioGroupDiv from '../Base/RadioGroupDiv'

export default {
	name: 'ConfigAccess',

	components: {
		CheckBoxDiv,
		RadioGroupDiv,
	},

	data() {
		return {
			accessOptions: [
				{ value: 'hidden', label: t('polls', 'Only invited users') },
				{ value: 'public', label: t('polls', 'All users') },
			],
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		pollAccess: {
			get() {
				return this.poll.access
			},
			set(value) {
				this.$store.commit('poll/setProperty', { access: value })
				this.$emit('change')
			},
		},

		pollImportant: {
			get() {
				return (this.poll.important > 0)
			},
			set(value) {
				this.$store.commit('poll/setProperty', { important: +value })
				this.$emit('change')
			},
		},
	},
}
</script>
