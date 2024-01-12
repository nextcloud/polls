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
	<NcSelect id="ajax"
		:options="users"
		:multiple="false"
		:user-select="true"
		:tag-width="80"
		:limit="30"
		:loading="isLoading"
		:searchable="true"
		:placeholder="placeholder"
		label="displayName"
		@option:selected="addShare"
		@search="loadUsersAsync">
		<template #selection="{ values, isOpen }">
			<span v-if="values.length &amp;&amp; !isOpen" class="multiselect__single">
				{{ values.length }} users selected
			</span>
		</template>
	</NcSelect>
</template>

<script>
import { debounce } from 'lodash'
import { showError } from '@nextcloud/dialogs'
import { NcSelect } from '@nextcloud/vue'
import { AppSettingsAPI } from '../../Api/index.js'

export default {
	name: 'UserSearch',

	components: {
		NcSelect,
	},

	data() {
		return {
			users: [],
			isLoading: false,
			placeholder: t('polls', 'Type to add an individual share'),
		}
	},

	computed: {
	},

	methods: {
		loadUsersAsync: debounce(async function(query) {
			if (!query) {
				this.users = []
				return
			}

			this.isLoading = true

			try {
				const response = await AppSettingsAPI.getUsers(query)
				this.users = response.data.siteusers
				this.isLoading = false
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				console.error(e.response)
				this.isLoading = false
			}
		}, 250),

		async addShare(payload) {
			try {
				await this.$store.dispatch('shares/add', {
					type: payload.type,
					id: payload.id,
					emailAddress: payload.emailAddress,
					displayName: payload.displayName,
				})
			} catch {
				showError(t('polls', 'Error while adding share'))
			}
		},
	},
}
</script>

<style lang="scss">
	.multiselect {
		width: 100% !important;
		max-width: 100% !important;
		margin-top: 4px !important;
		margin-bottom: 4px !important;
	}
</style>
