<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

import OptionItem from './OptionItem.vue'
import { usePollStore, PollType } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { BoxType } from '../../Types/index.ts'
import OptionMenu from './OptionMenu.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()

const pollType = ref(PollType.Date)

const cssVar = {
	'var(--content-deleted)': `" (${t('polls', 'deleted')})"`,
}
</script>

<template>
	<div :style="cssVar">
		<TransitionGroup v-if="optionsStore.list.length" tag="ul" name="list">
			<OptionItem
				v-for="option in optionsStore.sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="pollType"
				:display="BoxType.Date"
				tag="li">
				<template #actions>
					<div class="menu-wrapper">
						<OptionMenu
							v-if="pollStore.permissions.edit || option.isOwner"
							:option="option" />
					</div>
				</template>
			</OptionItem>
		</TransitionGroup>

		<NcEmptyContent
			v-else
			:name="t('polls', 'No vote options')"
			:description="t('polls', 'Add some!')">
			<template #icon>
				<DatePollIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>
