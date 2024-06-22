<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'This poll is unpublished.') }}
		<span v-if="votesStore.list.length">{{ t('polls', 'Existing participants will still have access.') }} </span>
		{{ t('polls', 'Invite users or allow internal access for all site users.') }}
		<template #button>
			<ActionOpenSharesSidebar />
		</template>
	</CardDiv>
</template>

<script>
import { mapStores } from 'pinia'
import { CardDiv } from '../../Base/index.js'
import ActionOpenSharesSidebar from '../../Actions/modules/ActionOpenSharesSidebar.vue'
import { t } from '@nextcloud/l10n'
import { useVotesStore } from '../../../stores/votes.ts'

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
		...mapStores(useVotesStore),
	},
	
	methods: {
		t,
	},
}
</script>
