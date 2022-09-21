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
	<UserItem type="public"
		class="add-public"
		user-id="addPublic"
		:display-name="t('polls', 'Add a new public link')"
		is-no-user>
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
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

export default {
	name: 'SharePublicAdd',

	components: {
		NcActions,
		NcActionButton,
		PlusIcon,
	},

	methods: {
		async addPublicShare() {
			try {
				await this.$store.dispatch('shares/add', {
					share: { type: 'public', userId: '' },
				})
			} catch {
				showError(t('polls', 'Error adding public link'))
			}
		},
	},
}
</script>
