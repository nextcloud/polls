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
	<UserItem v-bind="userItemProps">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcCheckboxRadioSwitch :checked.sync="pollAccess" type="switch" />
	</UserItem>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { writePoll } from '../../mixins/writePoll.js'

export default {
	name: 'ShareItemAllUsers',

	components: {
		NcCheckboxRadioSwitch,
	},

	mixins: [writePoll],

	computed: {
		...mapState({
			access: (state) => state.poll.configuration.access,
		}),

		userItemProps() {
			return {
				label: t('polls', 'Internal access'),
				type: 'internalAccess',
				disabled: this.access === 'private',
				description: this.access === 'private' ? t('polls', 'This poll is private') : t('polls', 'This is an openly accessible poll'),
			}
		},

		pollAccess: {
			get() {
				return this.access === 'open'
			},
			set(value) {
				this.$store.commit('poll/setProperty', { access: value ? 'open' : 'private' })
				this.writePoll()
			},
		},
	},
}
</script>
