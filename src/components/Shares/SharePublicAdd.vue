<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { showError } from '@nextcloud/dialogs'
	import { t } from '@nextcloud/l10n'

	import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
	import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'

	import PlusIcon from 'vue-material-design-icons/Plus.vue'

	import UserItem from '../User/UserItem.vue'
	import { User, UserType, VirtualUserItemType } from '../../Types/index.ts'
	import { useSharesStore } from '../../stores/shares.ts'

	const sharesStore = useSharesStore()

	const userItemProps = {
		label: t('polls', 'Add a new public link'),
		type: VirtualUserItemType.AddPublicLink,
	}

	const user: User = {
		id: '',
		displayName: '',
		emailAddress: '',
		isNoUser: false,
		type: UserType.None,
		subName: null,
		subtitle: null,
		desc: null,
		organisation: null,
		languageCode: null,
		localeCode: null,
		timeZone: null,
		categories: null,
	}

	async function addPublicShare() {
		try {
			await sharesStore.add(user)
		} catch {
			showError(t('polls', 'Error adding public link'))
		}
	}

</script>

<template>
	<UserItem v-bind="userItemProps"
		class="add-public">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcActions>
			<NcActionButton :name="t('polls', 'Add a new public link')" 
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
