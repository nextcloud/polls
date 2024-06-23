<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

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

<script>
import { mapStores } from 'pinia'
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { t } from '@nextcloud/l10n'
import UserItem from '../User/UserItem.vue'
import { useSharesStore } from '../../stores/shares.ts'

const user = {
	user: {
		type: 'public',
		userId: '',
		displayName: '',
		emailAddress: '',
	},
}

export default {
	name: 'SharePublicAdd',

	components: {
		NcActions,
		NcActionButton,
		PlusIcon,
		UserItem,
	},

	data() {
		return {
			userItemProps: {
				label: t('polls', 'Add a new public link'),
				type: 'addPublicLink',
			},
		}
	},

	computed: {
		...mapStores(useSharesStore),
	},
	methods: {
		t,

		async addPublicShare() {
			try {
				await this.sharesStore.add(user)
			} catch {
				showError(t('polls', 'Error adding public link'))
			}
		},
	},
}
</script>
