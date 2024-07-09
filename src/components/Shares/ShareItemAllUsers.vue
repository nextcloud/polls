<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<UserItem v-bind="userItemProps">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcCheckboxRadioSwitch v-model="pollAccess" type="switch" />
	</UserItem>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import UserItem from '../User/UserItem.vue'
import { usePollStore } from '../../stores/poll.ts'

export default {
	name: 'ShareItemAllUsers',

	components: {
		NcCheckboxRadioSwitch,
		UserItem,
	},

	computed: {
		...mapStores(usePollStore),

		userItemProps() {
			return {
				label: t('polls', 'Internal access'),
				type: 'internalAccess',
				disabled: this.pollStore.configuration.access === 'private',
				description: this.pollStore.configuration.access === 'private' ? t('polls', 'This poll is private') : t('polls', 'This is an openly accessible poll'),
			}
		},

		pollAccess: {
			get() {
				return this.pollStore.configuration.access === 'open'
			},
			set(value) {
				this.pollStore.access = value ? 'open' : 'private' 
				this.pollStore.write()
			},
		},

	},
}
</script>
