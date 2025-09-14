<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import OptionItem from './OptionItem.vue'
import OptionMenu from './OptionMenu.vue'

import { usePollStore } from '../../stores/poll'
import { useOptionsStore } from '../../stores/options'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()

const componentStyle = {
	'--content-deleted': `" (${t('polls', 'deleted')})"`,
}
</script>

<template>
	<TransitionGroup tag="ul" name="list" :style="componentStyle">
		<OptionItem
			v-for="option in optionsStore.sortedOptions"
			:key="option.id"
			:option="option"
			tag="li"
			show-owner>
			<template #actions>
				<OptionMenu
					v-if="pollStore.permissions.edit || option.isOwner"
					:option="option" />
			</template>
		</OptionItem>
	</TransitionGroup>
</template>
