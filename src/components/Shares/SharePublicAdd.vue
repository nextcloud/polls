<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import PlusIcon from 'vue-material-design-icons/Plus.vue'

import UserItem from '../User/UserItem.vue'
import { useSharesStore } from '../../stores/shares.ts'
import { VirtualUserItemType } from '../../Types/index.ts'

const sharesStore = useSharesStore()

const userItemProps: {
	label: string
	type: VirtualUserItemType
} = {
	label: t('polls', 'Add a new public link'),
	type: 'addPublicLink',
}

/**
 *
 */
async function addPublicShare() {
	try {
		await sharesStore.addPublicShare()
	} catch {
		showError(t('polls', 'Error adding public link'))
	}
}
</script>

<template>
	<UserItem v-bind="userItemProps" class="add-public">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcActions>
			<NcActionButton
				:name="t('polls', 'Add a new public link')"
				:aria-label="t('polls', 'Add a new public link')"
				@click="addPublicShare()">
				<template #icon>
					<PlusIcon />
				</template>
				{{ t('polls', 'Add a new public link') }}
			</NcActionButton>
		</NcActions>
	</UserItem>
</template>
