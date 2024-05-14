<!--
  - @copyright Copyright (c) 2024 René Gieling <github@dartcafe.de>
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
		{{ t('polls', 'This poll is unpublished.') }}
		<span v-if="hasVotes">{{ t('polls', 'Existing participants will still have access.') }} </span>
		{{ t('polls', 'Invite users or allow internal access for all site users.') }}
		<template #button>
			<ActionOpenSharesSidebar />
		</template>
	</CardDiv>
</template>

<script>
import { mapState } from 'vuex'
import { CardDiv } from '../../Base/index.js'
import ActionOpenSharesSidebar from '../../Actions/modules/ActionOpenSharesSidebar.vue'

export default {
	name: 'CardUnpublishedPoll',
	components: {
		CardDiv,
		ActionOpenSharesSidebar,
	},

	data() {
		return {
			cardType: 'warning',
		}
	},
	computed: {
		...mapState({
			hasVotes: (state) => state.votes.list.length,
		}),
	},
}
</script>
