<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
