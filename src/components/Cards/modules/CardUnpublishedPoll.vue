<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import CardDiv from '../../Base/modules/CardDiv.vue'
import ActionOpenSharesSidebar from '../../Actions/modules/ActionOpenSharesSidebar.vue'
import { t } from '@nextcloud/l10n'
import { useVotesStore } from '../../../stores/votes.ts'

const votesStore = useVotesStore()
const cardType = 'warning'
</script>

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'This poll is unpublished.') }}
		<span v-if="votesStore.votes.length"
			>{{ t('polls', 'Existing participants will still have access.') }}
		</span>
		{{ t('polls', 'Invite users or allow internal access for all site users.') }}
		<template #button>
			<ActionOpenSharesSidebar />
		</template>
	</CardDiv>
</template>
