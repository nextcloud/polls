<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action send-confirmations">
		<NcButton variant="primary"
			:aria-label="sendButtonCaption"
			:disabled="disableButton"
			@click="clickAction()">
			<template #icon>
				<EmailCheckIcon />
			</template>
			{{ sendButtonCaption }}
		</NcButton>

		<NcModal :show.sync="showModal"
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

<script>
import { NcButton, NcModal } from '@nextcloud/vue'
import EmailCheckIcon from 'vue-material-design-icons/EmailCheck.vue' // view-comfy-outline
import { PollsAPI } from '../../../Api/index.js'
import { Logger } from '../../../helpers/index.js'

export default {
	name: 'ActionSendConfirmed',

	components: {
		EmailCheckIcon,
		NcButton,
		NcModal,
	},

	data() {
		return {
			showModal: false,
			sendButtonCaption: t('polls', 'Send information about confirmed options by email'),
			confirmations: null,
			disableButton: false,
			sentStatus: '',
		}
	},

	methods: {
		async clickAction() {
			if (this.sendSatus === 'success') {
				this.showModal = true
				return
			}

			try {
				this.disableButton = true
				const result = await PollsAPI.sendConfirmation(this.$route.params.id)
				this.confirmations = result.data.confirmations
				this.showModal = true
				this.sendButtonCaption = t('polls', 'See result')
				this.sentStatus = 'success'
				this.$emit('success')
			} catch (error) {
				Logger.error('Error on sending confirmation mails', { error })
				this.sentStatus = 'error'
				this.$emit('error')
			} finally {
				this.disableButton = false
			}
		},
	},
}
</script>

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
