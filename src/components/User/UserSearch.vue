<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { debounce } from 'lodash'
import { t } from '@nextcloud/l10n'

import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'

import { AppSettingsAPI } from '../../Api/index.ts'
import { Logger } from '../../helpers/index.ts'
import { ISearchType, User } from '../../Types/index.ts'
import { AxiosError } from '@nextcloud/axios'

interface Props {
	placeholder?: string
	ariaLabel?: string
	searchTypes?: ISearchType[]
	closeOnSelect?: boolean
}

const emit = defineEmits(['userSelected'])

const model = defineModel<User | undefined>()

const {
	placeholder = t('polls', 'Type to start searching ...'),
	ariaLabel = t('polls', 'Select users'),
	searchTypes = [ISearchType.All],
	closeOnSelect = false,
} = defineProps<Props>()

const users = ref<User[]>([])
const isLoading = ref(false)

const loadUsersAsync = debounce(async function (query: string) {
	if (!query) {
		users.value = []
		return
	}

	isLoading.value = true

	try {
		const response = await AppSettingsAPI.getUsers(query, searchTypes)
		users.value = response.data.siteusers
		isLoading.value = false
	} catch (error) {
		if ((error as AxiosError)?.code === 'ERR_CANCELED') {
			return
		}
		Logger.error('Error loading users', { error })
		isLoading.value = false
	}
}, 250)

async function optionSelected(user: User) {
	emit('userSelected', user)
}

const selectProps = {
	ariaLabelCombobox: ariaLabel,
	multiple: false,
	userSelect: true,
	tagWidth: 80,
	loading: isLoading.value,
	filterable: false,
	searchable: true,
	placeholder,
	closeOnSelect,
	dropdownShouldOpen: () => users.value.length > 0,
	label: 'displayName',
}
</script>

<template>
	<NcSelectUsers
		id="ajax"
		v-model="model"
		v-bind="selectProps"
		:options="users"
		@option:selected="optionSelected"
		@search="loadUsersAsync">
		<template #selection="{ values, isOpen }">
			<span
				v-if="values.length &amp;&amp; !isOpen"
				class="multiselect__single">
				{{ values.length }} users selected
			</span>
		</template>
	</NcSelectUsers>
</template>

<style lang="scss">
.multiselect {
	width: 100% !important;
	max-width: 100% !important;
	margin-top: 4px !important;
	margin-bottom: 4px !important;
}
// TODO: temp hack, remove this when the bug is fixed
.vs--single.vs--searching:not(.vs--open):not(.vs--loading) .vs__search {
	opacity: 1 !important;
}
</style>
