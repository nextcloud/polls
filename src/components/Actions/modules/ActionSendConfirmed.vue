<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { useRoute } from 'vue-router'
	import { ref } from 'vue'
	import { NcButton, NcModal } from '@nextcloud/vue'
	import EmailCheckIcon from 'vue-material-design-icons/EmailCheck.vue' // view-comfy-outline
	import { PollsAPI } from '../../../Api/index.js'
	import { Logger } from '../../../helpers/index.ts'
	import { t, n } from '@nextcloud/l10n'
	import { StatusResults } from '../../../Types/index.ts'
	import { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'

	const route = useRoute()
	const showModal = ref(false)
	const sendButtonCaption = ref(t('polls', 'Send information about confirmed options by email'))
	const confirmations = ref(null)
	const disableButton = ref(false)
	const sentStatus = ref('')
	const emit = defineEmits(['success', 'error'])

	/**
	 *
	 */
	async function clickAction() {
		if (sentStatus.value === 'success') {
			showModal.value = true
			return
		}

		try {
			disableButton.value = true
			const result = await PollsAPI.sendConfirmation(route.params.id)
			confirmations.value = result.data.confirmations
			showModal.value = true
			sendButtonCaption.value = t('polls', 'See result')
			sentStatus.value = StatusResults.Success
			emit('success')
		} catch (error) {
			Logger.error('Error on sending confirmation mails', { error })
			sentStatus.value = StatusResults.Error
			emit('error')
		} finally {
			disableButton.value = false
		}
	}
</script>

<template>
	<div class="action send-confirmations">
		<NcButton :type="ButtonType.Primary"
			:aria-label="sendButtonCaption"
			:disabled="disableButton"
			@click="clickAction()">
			<template #icon>
				<EmailCheckIcon />
			</template>
			{{ sendButtonCaption }}
		</NcButton>

		<NcModal v-model:show="showModal"
			:name="t('polls', 'Result of sent confirmation mails')"
			size="small">
			<div class="modal-confirmation-result">
				<div v-if="confirmations?.countSentMails > 0" class="sent-confirmations">
					<h2>{{ n('polls', '%n confirmation has been sent', '%n confirmations have been sent', confirmations.countSentMails) }}</h2>
					<ul>
						<li v-for="(item) in confirmations.sentMails" :key="item.displayName">
							{{ item.displayName }} &lt;{{ item.emailAddress }}&gt;
						</li>
					</ul>
				</div>
				<div v-if="confirmations?.countAbortedMails > 0" class="error-confirmations">
					<h2>{{ n('polls', '%n confirmation could not be sent', '%n confirmations could not be sent:', confirmations.countAbortedMails) }}</h2>
					<ul>
						<li v-for="(item) in confirmations.abortedMails" :key="item.displayName">
							{{ item.displayName }} ({{ item.reason === 'InvalidMail' ? t('polls', 'No valid email address') : t('polls', 'Unknown error') }})
						</li>
					</ul>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<style lang="scss">
.modal-confirmation-result {
	padding: 24px;
	ul {
		list-style: initial;
	}

	.sent-confirmations, .error-confirmations {
		padding: 12px;
	}
}
</style>
