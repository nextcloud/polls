<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import type { Poll } from '../../stores/poll.types'
import type { User } from '../../Types/index.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import UserSearch from '../User/UserSearch.vue'
import { usePollStore } from '../../stores/poll.ts'
import { usePollsStore } from '../../stores/polls.ts'

const model = defineModel<boolean>({ required: true })
const { poll } = defineProps<{ poll: Poll }>()
const emit = defineEmits(['accessDenied'])
const pollsStore = usePollsStore()
const pollStore = usePollStore()
const newUser = ref<User | undefined>(undefined)

async function dialogOK() {
	try {
		await pollsStore.changeOwner({
			pollId: poll.id,
			userId: newUser.value ? newUser.value.id : '',
		})
		showSuccess(
			t('polls', 'Transfered poll to {user}.', {
				user: newUser.value ? newUser.value.displayName : '',
			}),
		)
	} catch {
		showError(t('polls', 'Error transfering poll.'))
	} finally {
		try {
			// reload the poll to refresh the configuration
			await pollStore.load()
		} catch {
			// if error occurs, we need to emit the accessDenied event
			// since we assume the user has no access to the poll anymore
			emit('accessDenied')
		}
	}
}

const dialogText = computed(() => {
	if (poll.currentUserStatus.isOwner) {
		if (!newUser.value) {
			return t(
				'polls',
				'Transfering a poll to another user may result in loss of access to this poll.',
			)
		}

		return t(
			'polls',
			'Transfering a poll to {user} may result in loss of access to this poll.',
			{
				user: newUser.value.displayName,
			},
		)
	}
	if (!newUser.value) {
		return t(
			'polls',
			'You are not the owner of this poll. {owner} will get informed about this action.',
			{
				owner: poll.owner.displayName,
			},
		)
	}
	return t(
		'polls',
		'You are not the owner of this poll. {owner} will get informed about the transfer to {newUser}.',
		{
			owner: poll.owner.displayName,
			newUser: newUser.value.displayName,
		},
	)
})

const dialogProps = computed(() => ({
	name: t('polls', 'Transfer poll'),
	noClose: true,
	closeOnClickOutside: true,
	buttons: [
		{ label: t('polls', 'Cancel') },
		{
			label: t('polls', 'OK'),
			variant: 'primary' as ButtonVariant,
			disabled: !newUser.value,
			callback: () => {
				dialogOK()
			},
		},
	],
}))
</script>

<template>
	<NcDialog v-model:open="model" v-bind="dialogProps">
		<UserSearch
			v-model="newUser"
			:searchTypes="[0]"
			:inputLabel="t('polls', 'Select the user to transfer the ownership to')"
			userSelect
			closeOnSelect
			@userSelected="(user: User) => (newUser = user)" />
		<span>
			{{ dialogText }}
		</span>
	</NcDialog>
</template>
