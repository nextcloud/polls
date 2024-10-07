<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import SortByOriginalOrderIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
	import SortByRankIcon from 'vue-material-design-icons/FormatListNumbered.vue'
	import SortByDateOptionIcon from 'vue-material-design-icons/SortClockAscendingOutline.vue'
	import { t } from '@nextcloud/l10n'
	import { useOptionsStore } from '../../../stores/options.ts'
	import { usePollStore, PollType } from '../../../stores/poll.ts'
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'

	const pollStore = usePollStore()
	const optionsStore = useOptionsStore()

	const caption = computed(() => {
		if (optionsStore.ranked && pollStore.type === PollType.Date) {
			return t('polls', 'Date order')
		}

		if (optionsStore.ranked && pollStore.type === PollType.Text) {
			return t('polls', 'Original order')
		}

		return t('polls', 'Ranked order')
	})
</script>

<template>
	<div class="action sort-options">
		<NcButton :type="ButtonType.Tertiary"
			:title="caption"
			:aria-label="caption"
			@click="optionsStore.ranked = !optionsStore.ranked">
			<template #icon>
				<SortByDateOptionIcon v-if="optionsStore.ranked && pollStore.type === PollType.Date" />
				<SortByOriginalOrderIcon v-else-if="optionsStore.ranked && pollStore.type === PollType.Text" />
				<SortByRankIcon v-else />
			</template>
		</NcButton>
	</div>
</template>
