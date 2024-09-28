<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { NcButton } from '@nextcloud/vue'
	import { t } from '@nextcloud/l10n'
	import { useVotesStore } from '../../../stores/votes.ts'
	import { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js';

	const votesStore = useVotesStore()
	const caption = t('polls', 'Delete orphaned')

</script>

<template>
	<div class="action toggle-sidebar">
		<NcButton :type="ButtonType.Primary"
			:aria-label="caption"
			@click="votesStore.removeOrphanedVotes()">
			{{ caption }}
		</NcButton>
	</div>
</template>

