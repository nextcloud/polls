<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { PropType, ref } from 'vue'
import { debounce } from 'lodash'
import { t } from '@nextcloud/l10n'

import NcSelect from '@nextcloud/vue/components/NcSelect'

import { AppSettingsAPI } from '../../Api/index.ts'
import { Logger } from '../../helpers/index.ts'
import { ISearchType, User } from '../../Types/index.ts'
import { AxiosError } from '@nextcloud/axios'


const users = ref<User[]>([])
const isLoading = ref(false)
const model = defineModel({
	required: true,
	type: Object as PropType<User | null>,
})
const emit = defineEmits(['userSelected'])

const props = defineProps({
	placeholder: {
		type: String,
		default: t('polls', 'Type to start searching ...'),
	},
	ariaLabel: {
		type: String,
		default: t('polls', 'Select users'),
	},
	searchTypes: {
		type: Array as PropType<ISearchType[]>,
		default: () => [ISearchType.All],
	},
	closeOnSelect: {
		type: Boolean,
		default: false,
	},
})

const loadUsersAsync = debounce(async function (query: string) {
	if (!query) {
		users.value = []
		return
	}

	isLoading.value = true

	try {
		const response = await AppSettingsAPI.getUsers(query, props.searchTypes)
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
	ariaLabelCombobox: props.ariaLabel,
	multiple: false,
	userSelect: true,
	tagWidth: 80,
	loading: isLoading.value,
	filterable: false,
	searchable: true,
	placeholder: props.placeholder,
	closeOnSelect: props.closeOnSelect,
	label: 'displayName',
}
</script>

<template>
	<NcSelect
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
