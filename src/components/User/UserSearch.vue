<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import debounce from 'lodash/debounce'
import { t } from '@nextcloud/l10n'
import {
	mdiAccount,
	mdiAccountGroup,
	mdiAccountMultiple,
	mdiAccountMultipleOutline,
	mdiCardAccountDetails,
	mdiEmail,
	mdiShareVariant,
} from '@mdi/js'

import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'

import { AppSettingsAPI } from '../../Api'
import { Logger } from '../../helpers'
import { ISearchType, User, UserType } from '../../Types'
import type { AxiosError } from '@nextcloud/axios'

interface Props {
	placeholder?: string
	ariaLabel?: string
	searchTypes?: ISearchType[]
	closeOnSelect?: boolean
}

const emit = defineEmits(['userSelected'])

const model = defineModel<User | undefined>()

const {
	placeholder = t('polls', 'Type to start searching …'),
	ariaLabel = t('polls', 'Select users'),
	searchTypes = [99],
	closeOnSelect = false,
} = defineProps<Props>()

const users = ref<User[]>([])
const isLoading = ref(false)

// Same icons as core (`icon-user`, `icon-teams`), calendar and contacts:
// account = Account, group = AccountMultiple, team = AccountGroup,
// contact group = AccountMultipleOutline
const typeDecoration: Partial<Record<UserType, { path: string; name: string }>> = {
	user: { path: mdiAccount, name: t('polls', 'Account') },
	admin: { path: mdiAccount, name: t('polls', 'Account') },
	group: { path: mdiAccountMultiple, name: t('polls', 'Group') },
	contactGroup: {
		path: mdiAccountMultipleOutline,
		name: t('polls', 'Contact group'),
	},
	circle: { path: mdiAccountGroup, name: t('polls', 'Team') },
	contact: { path: mdiCardAccountDetails, name: t('polls', 'Contact') },
	email: { path: mdiEmail, name: t('polls', 'Email') },
	external: { path: mdiShareVariant, name: t('polls', 'External') },
}

type UserOption = {
	id: string
	displayName: string
	user?: string
	isNoUser: boolean
	subname: string
	iconSvg?: string
	iconName?: string
	source: User
}

/**
 * Shape a search result for NcSelectUsers/NcListItemIcon like files_sharing does:
 * real avatar for accounts, a subname and a trailing type icon for every type,
 * so accounts without an avatar picture are not mistaken for initials-only entries.
 *
 * @param user search result
 */
function toOption(user: User): UserOption {
	const decoration = typeDecoration[user.type]
	const isAccount = user.type === 'user' || user.type === 'admin'
	return {
		// groups and accounts may share the same id
		id: `${user.type}:${user.id}`,
		displayName: user.displayName,
		user: isAccount ? user.id : undefined,
		isNoUser: !isAccount,
		subname:
			user.emailAddress && user.emailAddress !== user.displayName
				? user.emailAddress
				: (decoration?.name ?? ''),
		iconSvg: decoration
			? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="${decoration.path}"/></svg>`
			: undefined,
		iconName: decoration?.name,
		source: user,
	}
}

const options = computed(() => users.value.map(toOption))

// consumers get the untouched search result, the option is for display only
const selected = computed({
	get: () => (model.value ? toOption(model.value) : undefined),
	set: (option: UserOption | undefined) => {
		model.value = option?.source
	},
})

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

function optionSelected(option: UserOption) {
	emit('userSelected', option.source)
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
		v-model="selected"
		v-bind="selectProps"
		:options="options"
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
