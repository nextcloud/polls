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
import { mapActions } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { t } from '@nextcloud/l10n'

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
	},

	data() {
		return {
			userItemProps: {
				label: t('polls', 'Add a new public link'),
				type: 'addPublicLink',
			},
		}
	},

	methods: {
		t,
		...mapActions({
			addShare: 'shares/add',
		}),

		async addPublicShare() {
			try {
				await this.addShare(user)
			} catch {
				showError(t('polls', 'Error adding public link'))
			}
		},
	},
}
</script>
