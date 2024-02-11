<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<UserItem v-bind="userItemProps"
		class="add-public">
		<template #status>
			<div class="vote-status" />
		</template>
		<NcActions>
			<NcActionButton @click="addPublicShare()">
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
