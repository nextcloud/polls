<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { ref } from 'vue'
import { t, n } from '@nextcloud/l10n'

import NcModal from '@nextcloud/vue/components/NcModal'
import NcButton from '@nextcloud/vue/components/NcButton'

import EmailCheckIcon from 'vue-material-design-icons/EmailCheckOutline.vue' // view-comfy-outline

import { PollsAPI } from '../../../Api'
import { Logger } from '../../../helpers'
import { Confirmations } from '../../../Api/modules/polls'

const route = useRoute()
const showModal = ref(false)
const sendButtonCaption = ref(t('polls', 'Send confirmation mails'))

const confirmations = ref<Confirmations>({
	sentMails: [],
	abortedMails: [],
	countSentMails: 0,
	countAbortedMails: 0,
})
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
		const result = await PollsAPI.sendConfirmation(
			parseInt(route.params.id as string),
		)
		confirmations.value = result.data.confirmations
		showModal.value = true
		sendButtonCaption.value = t('polls', 'See result')
		sentStatus.value = 'success'
		emit('success')
	} catch (error) {
		Logger.error('Error on sending confirmation mails', { error })
		sentStatus.value = 'error'
		emit('error')
	} finally {
		disableButton.value = false
	}
}
</script>

<template>
	<div class="action send-confirmations">
		<NcButton
			:variant="'primary'"
			:aria-label="sendButtonCaption"
			:disabled="disableButton"
			@click="clickAction()">
			<template #icon>
				<EmailCheckIcon />
			</template>
			{{ sendButtonCaption }}
		</NcButton>

		<NcModal
			v-model:show="showModal"
			no-close
			:name="t('polls', 'Result of sent confirmation mails')"
			size="small">
			<div class="modal-confirmation-result">
				<div
					v-if="confirmations?.countSentMails > 0"
					class="sent-confirmations">
					<h2>
						{{
							n(
								'polls',
								'%n confirmation has been sent',
								'%n confirmations have been sent',
								confirmations.countSentMails,
							)
						}}
					</h2>
					<ul>
						<li
							v-for="item in confirmations.sentMails"
							:key="item.displayName">
							{{ item.displayName }} &lt;{{ item.emailAddress }}&gt;
						</li>
					</ul>
				</div>
				<div
					v-if="confirmations?.countAbortedMails > 0"
					class="error-confirmations">
					<h2>
						{{
							n(
								'polls',
								'%n confirmation could not be sent',
								'%n confirmations could not be sent:',
								confirmations.countAbortedMails,
							)
						}}
					</h2>
					<ul>
						<li
							v-for="item in confirmations.abortedMails"
							:key="item.displayName">
							{{ item.displayName }} ({{
								item.reason === 'InvalidMail'
									? t('polls', 'No valid email address')
									: t('polls', 'Unknown error')
							}})
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

	.sent-confirmations,
	.error-confirmations {
		padding: 12px;
	}
}
</style>
