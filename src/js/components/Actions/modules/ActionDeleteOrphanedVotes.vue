<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action toggle-sidebar">
		<NcButton variant="primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
	</div>
</template>

<script>
import { mapActions } from 'vuex'
import { NcButton } from '@nextcloud/vue'

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

	methods: {
		...mapActions({
			deleteOrphanedVotes: 'votes/removeOrphanedVotes',
		}),

		clickAction() {
			this.deleteOrphanedVotes()
		},
	},
}
</script>
