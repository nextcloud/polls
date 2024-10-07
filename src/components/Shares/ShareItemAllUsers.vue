<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

	import UserItem from '../User/UserItem.vue'
	import { VirtualUserItemType } from '../../Types/index.ts'
	import { usePollStore, AccessType } from '../../stores/poll.ts'


	const pollStore = usePollStore()

	const userItemProps = computed(() => ({
		label: t('polls', 'Internal access'),
		type: VirtualUserItemType.InternalAccess,
		disabled: pollStore.configuration.access === AccessType.Private,
		description: pollStore.configuration.access === AccessType.Private ? t('polls', 'This poll is private') : t('polls', 'This is an openly accessible poll'),
	}))

	const pollAccess = computed({
		get() {
			return pollStore.configuration.access === AccessType.Open
		},
		set(value) {
			pollStore.configuration.access = value ? AccessType.Open : AccessType.Private
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
