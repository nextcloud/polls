<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action sort-options">
		<NcButton type="tertiary"
			:title="caption"
			:aria-label="caption"
			@click="optionsStore.setRankOrder()">
			<template #icon>
				<SortByDateOptionIcon v-if="optionsStore.ranked && pollStore.type === 'datePoll'" />
				<SortByOriginalOrderIcon v-else-if="optionsStore.ranked && pollStore.type === 'textPoll'" />
				<SortByRankIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcButton } from '@nextcloud/vue'
import SortByOriginalOrderIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import SortByRankIcon from 'vue-material-design-icons/FormatListNumbered.vue'
import SortByDateOptionIcon from 'vue-material-design-icons/SortClockAscendingOutline.vue'
import { t } from '@nextcloud/l10n'
import { useOptionsStore } from '../../../stores/options.ts'
import { usePollStore } from '../../../stores/poll.ts'

export default {
	name: 'ActionSortOptions',

	components: {
		SortByRankIcon,
		SortByOriginalOrderIcon,
		SortByDateOptionIcon,
		NcButton,
	},

	computed: {
		...mapStores(usePollStore, useOptionsStore),

		caption() {
			if (this.optionsStore.ranked && this.pollStore.type === 'datePoll') {
				return t('polls', 'Date order')
			}

			if (this.optionsStore.ranked && this.pollStore.type === 'textPoll') {
				return t('polls', 'Original order')
			}

			return t('polls', 'Ranked order')
		},
	},
}
</script>
