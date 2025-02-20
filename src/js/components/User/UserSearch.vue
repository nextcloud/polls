<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSelect id="ajax"
		:aria-label-combobox="t('polls', 'Add shares')"
		:options="users"
		:multiple="false"
		:user-select="true"
		:filterable="false"
		:tag-width="80"
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
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error(error.response)
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
