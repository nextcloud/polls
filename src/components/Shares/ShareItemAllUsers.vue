<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import UserItem from '../User/UserItem.vue'
import { usePollStore } from '../../stores/poll.ts'
import { VirtualUserItemType } from '../../Types/index.ts'

const pollStore = usePollStore()

const userItemProps = computed<{
	label: string
	type: VirtualUserItemType
	disabled?: boolean
	description?: string
}>(() => ({
	label: t('polls', 'Internal access'),
	type: 'internalAccess',
	disabled: pollStore.configuration.access === 'private',
	description:
		pollStore.configuration.access === 'private'
			? t('polls', 'This poll is private')
			: t('polls', 'This is an openly accessible poll'),
}))

const pollAccess = computed({
	get() {
		return pollStore.configuration.access === 'open'
	},
	set(value) {
		pollStore.configuration.access = value ? 'open' : 'private'
		pollStore.write()
	},
})
</script>

<template>
	<UserItem v-bind="userItemProps">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcCheckboxRadioSwitch v-model="pollAccess" type="switch" />
	</UserItem>
</template>
