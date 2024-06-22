<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action toggle-sidebar">
		<NcButton type="primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcButton } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { useVotesStore } from '../../../stores/votes.ts'
export default {
	name: 'ActionDeleteOrphanedVotes',

	components: {
		NcButton,
	},

	data() {
		return {
			caption: t('polls', 'Delete orphaned'),
		}
	},

	computed: {
		...mapStores(useVotesStore),
	},

	methods: {
		clickAction() {
			this.votesStore.removeOrphanedVotes()
		},
	},
}
</script>
