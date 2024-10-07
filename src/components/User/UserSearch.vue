<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref } from 'vue'
	import { debounce } from 'lodash'
	import { showError } from '@nextcloud/dialogs'
	import { t } from '@nextcloud/l10n'

	import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

	import { AppSettingsAPI } from '../../Api/index.js'
	import { Logger } from '../../helpers/index.ts'
	import { useSharesStore } from '../../stores/shares.ts'

	const sharesStore = useSharesStore()
	const users = ref([])
	const isLoading = ref(false)
	const placeholder = t('polls', 'Type to add an individual share')

	const loadUsersAsync = debounce(async function(query: string) {
		if (!query) {
			users.value = []
			return
		}

		isLoading.value = true

		try {
			const response = await AppSettingsAPI.getUsers(query)
			users.value = response.data.siteusers
			isLoading.value = false
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error(error.response)
			isLoading.value = false
		}
	}, 250)

	async function clickAdd(payload) {
		try {
			await sharesStore.add({
				user: {
					...payload,
				},
			},
			)
		} catch {
			showError(t('polls', 'Error while adding share'))
		}
	}

</script>

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

<style lang="scss">
	.multiselect {
		width: 100% !important;
		max-width: 100% !important;
		margin-top: 4px !important;
		margin-bottom: 4px !important;
	}
</style>
