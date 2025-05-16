<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'

import { useVotesStore } from '../../../stores/votes.ts'

const votesStore = useVotesStore()
const caption = t('polls', 'Delete orphaned')
</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton
			:variant="'primary'"
			:aria-label="caption"
			@click="votesStore.removeOrphanedVotes()">
			{{ caption }}
		</NcButton>
	</div>
</template>
