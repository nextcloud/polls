<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import {
	SortDirection,
	sortTitlesMapping,
	usePollsStore,
} from '../../stores/polls.ts'
import { SortType } from '../../Types'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'

import SortAscendingIcon from 'vue-material-design-icons/SortAscending.vue'
import SortDescendingIcon from 'vue-material-design-icons/SortDescending.vue'
import AlphabeticalIcon from 'vue-material-design-icons/Alphabetical.vue'
import GestureDoubleTapIcon from 'vue-material-design-icons/GestureDoubleTap.vue'
import CreationIcon from 'vue-material-design-icons/ClockPlusOutline.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'
import AccountCircleOutlineIcon from 'vue-material-design-icons/AccountCircleOutline.vue'
import { NcActionButtonGroup } from '@nextcloud/vue'
import { computed } from 'vue'

const pollsStore = usePollsStore()

const sortDirection = computed({
	get() {
		return pollsStore.sort.reverse ? SortDirection.Desc : SortDirection.Asc
	},
	set(direction: SortDirection) {
		direction === SortDirection.Asc
			? (pollsStore.sort.reverse = false)
			: (pollsStore.sort.reverse = true)
	},
})

function setSort(sort: { by?: SortType; reverse?: boolean }) {
	if (sort.by !== undefined) {
		pollsStore.sort.by = sort.by
	}
	if (sort.reverse !== undefined) {
		pollsStore.sort.reverse = sort.reverse
	}
}
</script>

<template>
	<NcActions primary :menu-name="sortTitlesMapping[pollsStore.sort.by]">
		<template #icon>
			<SortDescendingIcon
				v-if="pollsStore.sort.reverse"
				:size="20"
				decorative />
			<SortAscendingIcon v-else :size="20" decorative />
		</template>

		<NcActionButton
			:name="sortTitlesMapping[SortType.Title]"
			:aria-label="t('polls', 'Sort by title')"
			@click="setSort({ by: SortType.Title })">
			<template #icon>
				<AlphabeticalIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			:name="sortTitlesMapping[SortType.Interaction]"
			:aria-label="t('polls', 'Sort by last interaction')"
			@click="setSort({ by: SortType.Interaction })">
			<template #icon>
				<GestureDoubleTapIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			:name="sortTitlesMapping[SortType.Created]"
			:aria-label="t('polls', 'Sort by creation date')"
			@click="setSort({ by: SortType.Created })">
			<template #icon>
				<CreationIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			:name="sortTitlesMapping[SortType.Expire]"
			:aria-label="t('polls', 'Sort by expiration date')"
			@click="setSort({ by: SortType.Expire })">
			<template #icon>
				<ExpirationIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			:name="sortTitlesMapping[SortType.Owner]"
			:aria-label="t('polls', 'Sort by owner name')"
			@click="setSort({ by: SortType.Expire })">
			<template #icon>
				<AccountCircleOutlineIcon />
			</template>
		</NcActionButton>

		<NcActionSeparator />

		<NcActionButtonGroup :name="t('polls', 'Direction')">
			<NcActionButton
				v-model="sortDirection"
				:value="SortDirection.Desc"
				type="radio"
				:aria-label="t('polls', 'Descending')">
				<template #icon>
					<SortDescendingIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-model="sortDirection"
				:value="SortDirection.Asc"
				type="radio"
				:aria-label="t('polls', 'Ascending')">
				<template #icon>
					<SortAscendingIcon />
				</template>
			</NcActionButton>
		</NcActionButtonGroup>
	</NcActions>
</template>
