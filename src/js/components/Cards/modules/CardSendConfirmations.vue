<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ confirmationSendMessage }}
		<template #button>
			<ActionSendConfirmed @error="confirmationSendError()"
				@success="confirmationSendSuccess()" />
		</template>
	</CardDiv>
</template>

<script>
import { CardDiv } from '../../Base/index.js'
import ActionSendConfirmed from '../../Actions/modules/ActionSendConfirmed.vue'

export default {
	name: 'CardSendConfirmations',
	components: {
		CardDiv,
		ActionSendConfirmed,
	},

	data() {
		return {
			cardType: 'info',
			confirmationSendMessage: t('polls', 'You have confirmed options. Inform your participants about the result via email.'),
		}
	},

	methods: {
		confirmationSendError() {
			this.cardType = 'error'
			this.confirmationSendMessage = t('polls', 'Some confirmation messages could not been sent.')
			this.$emit('send-confirmation-success')
		},

		confirmationSendSuccess() {
			this.cardType = 'success'
			this.confirmationSendMessage = t('polls', 'Messages sent.')
			this.$emit('send-confirmation-error')
		},

	},
}
</script>
