<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import { useSessionStore } from '../../stores/session'
import { computed } from 'vue'

import NcDialog from '@nextcloud/vue/components/NcDialog'
import { showError } from '@nextcloud/dialogs'
import { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import { usePollsStore } from '../../stores/polls'

import type { Poll } from '../../stores/poll.types'

const model = defineModel<boolean>({ required: true })
const { poll } = defineProps<{ poll: Poll }>()
const emit = defineEmits(['deleted'])

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()

const adminAccess = computed(
	() => !poll.permissions.view && sessionStore.currentUser.isAdmin,
)

function dialogOK() {
	try {
		pollsStore.delete({ pollId: poll.id })
		emit('deleted')
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}

const dialogText = adminAccess.value
	? t(
			'polls',
			'This will irreversibly delete the poll and {username} will get notified.',
			{ username: poll.owner.displayName },
		)
	: t('polls', 'This will irreversibly delete the poll.')

const dialogProps = {
	name: t('polls', 'Delete poll'),
	noClose: true,
	closeOnClickOutside: true,
	buttons: [
		{ label: t('polls', 'Cancel') },
		{
			label: t('polls', 'OK'),
			variant: 'primary' as ButtonVariant,
			callback: () => {
				dialogOK()
			},
		},
	],
}
</script>

<template>
	<NcDialog v-model:open="model" v-bind="dialogProps">
		<span>
			{{ dialogText }}
		</span>
	</NcDialog>
</template>
