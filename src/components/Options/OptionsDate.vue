<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

import OptionItem from './OptionItem.vue'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'
import OptionMenu from './OptionMenu.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()

const cssVar = {
	'--content-deleted': `" (${t('polls', 'deleted')})"`,
}
</script>

<template>
	<div
		v-if="optionsStore.options.length"
		:style="cssVar"
		class="options-list date">
		<TransitionGroup name="list">
			<OptionItem
				v-for="option in optionsStore.sortedOptions"
				:key="option.id"
				:option="option"
				show-owner>
				<template #actions>
					<OptionMenu
						v-if="pollStore.permissions.edit || option.isOwner"
						:option="option" />
				</template>
			</OptionItem>
		</TransitionGroup>
	</div>

	<NcEmptyContent
		v-else
		:name="t('polls', 'No vote options')"
		:description="t('polls', 'Add some!')">
		<template #icon>
			<DatePollIcon />
		</template>
	</NcEmptyContent>
</template>
