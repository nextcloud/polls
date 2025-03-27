<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { CardDiv } from '../../Base/index.ts'
import ActionSendConfirmed from '../../Actions/modules/ActionSendConfirmed.vue'
import { t } from '@nextcloud/l10n'

const emit = defineEmits(['sendConfirmationSuccess', 'sendConfirmationError'])
const cardType = ref('info')
const confirmationSendMessage = ref(
	t(
		'polls',
		'You have confirmed options. Inform your participants about the result via email.',
	),
)

/**
 *
 */
function confirmationSendError() {
	cardType.value = 'error'
	confirmationSendMessage.value = t(
		'polls',
		'Some confirmation messages could not been sent.',
	)
	emit('sendConfirmationSuccess')
}

/**
 *
 */
function confirmationSendSuccess() {
	cardType.value = 'success'
	confirmationSendMessage.value = t('polls', 'Messages sent.')
	emit('sendConfirmationError')
}
</script>

<template>
	<CardDiv :type="cardType">
		{{ confirmationSendMessage }}
		<template #button>
			<ActionSendConfirmed
				@error="confirmationSendError()"
				@success="confirmationSendSuccess()" />
		</template>
	</CardDiv>
</template>
