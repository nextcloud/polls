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
	<UserItem type="internalAccess"
		:user-id="t('polls', 'Openly accessible poll')"
		:display-name="t('polls', 'Openly accessible poll')"
		:disabled="access==='private'"
		show-email
		is-no-user>
		<template #status>
			<div class="vote-status" />
		</template>
		<CheckboxRadioSwitch :checked.sync="pollAccess" type="switch" />
	</UserItem>
</template>

<script>
import { mapState } from 'vuex'
import { CheckboxRadioSwitch } from '@nextcloud/vue'
import { writePoll } from '../../mixins/writePoll'

export default {
	name: 'ShareItemAllUsers',

	components: {
		CheckboxRadioSwitch,
	},

	mixins: [writePoll],

	computed: {
		...mapState({
			access: (state) => state.poll.access,
		}),

		pollAccess: {
			get() {
				return this.access === 'open'
			},
			set(value) {
				this.$store.commit('poll/setProperty', { important: +value })
				this.$store.commit('poll/setProperty', { access: value ? 'open' : 'private' })
				this.writePoll()
			},
		},
	},
}
</script>
