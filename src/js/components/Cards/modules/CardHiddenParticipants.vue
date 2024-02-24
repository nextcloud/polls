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
	<CardDiv :type="cardType">
		{{ t('polls', 'Due to possible performance issues {countHiddenParticipants} voters are hidden.', { countHiddenParticipants }) }}
		{{ t('polls', 'You can reveal them, but you may expect an unwanted long loading time.') }}
		<template #button>
			<ActionSwitchSafeTable />
		</template>
	</CardDiv>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { CardDiv } from '../../Base/index.js'

export default {
	name: 'CardHiddenParticipants',
	components: {
		CardDiv,
		ActionSwitchSafeTable: () => import('../../Actions/modules/ActionSwitchSafeTable.vue'),
	},

	data() {
		return {
			cardType: 'warning',
		}
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			countHiddenParticipants: 'poll/countHiddenParticipants',
		}),
	},
}
</script>
