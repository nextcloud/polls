<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import { useSessionStore } from '../../stores/session.ts'
import { Poll } from '../../stores/poll.ts'
import { computed, PropType } from 'vue'
import { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import { NcDialog } from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { usePollsStore } from '../../stores/polls.ts'

const pollsStore = usePollsStore()
const sessionStore = useSessionStore()

const emit = defineEmits(['deleted'])

const props = defineProps({
	poll: {
		type: Object as PropType<Poll>,
		required: true,
	},
})

const adminAccess = computed(
	() => !props.poll.permissions.view && sessionStore.currentUser.isAdmin,
)

const model = defineModel({
	required: true,
	type: Boolean,
})

function dialogOK() {
	try {
		pollsStore.delete({ pollId: props.poll.id })
		emit('deleted')
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}

const dialogText = adminAccess.value
	? t(
			'polls',
			'This will finally delete the poll and {username} will get notified.',
			{ username: props.poll.owner.displayName },
		)
	: t('polls', 'This will finally delete the poll.')

const dialogProps = {
	name: t('polls', 'Delete poll'),
	noClose: true,
	closeOnClickOutside: true,
	buttons: [
		{ label: t('polls', 'Cancel') },
		{
			label: t('polls', 'OK'),
			variant: ButtonVariant.Primary,
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
