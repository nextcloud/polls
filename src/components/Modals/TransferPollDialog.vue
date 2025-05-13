<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import { Poll, usePollStore } from '../../stores/poll.ts'
import { computed, PropType, ref } from 'vue'
import { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import { NcDialog } from '@nextcloud/vue'
import UserSearch from '../User/UserSearch.vue'
import { ISearchType, User } from '../../Types/index.ts'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { usePollsStore } from '../../stores/polls.ts'

const pollsStore = usePollsStore()
const pollStore = usePollStore()

const emit = defineEmits(['accessDenied'])

const props = defineProps({
	poll: {
		type: Object as PropType<Poll>,
		required: true,
	},
})

const model = defineModel({
	required: true,
	type: Boolean,
})

const newUser = ref<User | null>(null)

async function dialogOK() {
	try {
		await pollsStore.changeOwner({
			pollId: props.poll.id,
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
		} catch (error) {
			// if error occurs, we need to emit the accessDenied event
			// since we assume the user has no access to the poll anymore
			emit('accessDenied')
		}
	}
}

const dialogText = computed(() => {
	if (props.poll.currentUserStatus.isOwner) {
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
				owner: props.poll.owner.displayName,
			},
		)
	}
	return t(
		'polls',
		'You are not the owner of this poll. {owner} will get informed about the transfer to {newUser}.',
		{
			owner: props.poll.owner.displayName,
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
			variant: ButtonVariant.Primary,
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
			:search-types="[ISearchType.User]"
			:input-label="t('polls', ' Select the user to transfer the ownership to')"
			user-select
			close-on-select
			@user-selected="(user: User) => (newUser = user)" />
		<span>
			{{ dialogText }}
		</span>
	</NcDialog>
</template>
