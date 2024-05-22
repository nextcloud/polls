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
		:aria-label-combobox="t('polls', 'Add shares')"
		:options="users"
		:multiple="false"
		:user-select="true"
		:tag-width="80"
		:limit="30"
		:loading="isLoading"
		:searchable="true"
		:placeholder="placeholder"
		:close-on-select="false"
		label="displayName"
		@option:selected="clickAdd"
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
import { mapActions } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcSelect } from '@nextcloud/vue'
import { AppSettingsAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

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

	methods: {
		...mapActions({
			addShare: 'shares/add',
		}),

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
				Logger.error(e.response)
				this.isLoading = false
			}
		}, 250),

		async clickAdd(payload) {
			try {
				await this.addShare({
					user: {
						...payload,
					},
				},
				)
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
