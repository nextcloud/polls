<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ConfigBox v-if="unsentInvitations.length" :name="t('polls', 'Unsent invitations')">
		<template #icon>
			<EmailAlertIcon />
		</template>
		<template #actions>
			<NcButton :title="t('polls', 'Resolve and send all invitations')"
				:aria-label="t('polls', 'Resolve and send all invitations')"
				variant="tertiary"
				@click="sendAllInvitations()">
				<template #icon>
					<BulkMailIcon />
				</template>
			</NcButton>
		</template>
		<TransitionGroup is="div"
			name="list"
			:css="false"
			class="shares-list">
			<ShareItem v-for="(share) in unsentInvitations"
				:key="share.id"
				:share="share" />
		</TransitionGroup>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapActions, mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import { ConfigBox } from '../Base/index.js'
import EmailAlertIcon from 'vue-material-design-icons/EmailAlert.vue'
import ShareItem from './ShareItem.vue'
import BulkMailIcon from 'vue-material-design-icons/EmailMultipleOutline.vue'
import { Logger } from '../../helpers/index.js'

export default {
	name: 'SharesListUnsent',

	components: {
		EmailAlertIcon,
		ConfigBox,
		NcButton,
		ShareItem,
		BulkMailIcon,
	},

	computed: {
		...mapState({
			pollId: (state) => state.poll.id,
		}),

		...mapGetters({
			unsentInvitations: 'shares/unsentInvitations',
		}),
	},

	methods: {
		...mapActions({
			inviteAll: 'shares/inviteAll',
		}),

		async sendAllInvitations() {

			const response = await this.inviteAll({ pollId: this.pollId })
			if (response.data?.sentResult?.sentMails) {
				response.data.sentResult.sentMails.forEach((item) => {
					showSuccess(t('polls', 'Invitation sent to {displayName} ({emailAddress})', { emailAddress: item.emailAddress, displayName: item.displayName }))
				})
			}
			if (response.data?.sentResult?.abortedMails) {
				response.data.sentResult.abortedMails.forEach((item) => {
					Logger.error('Mail could not be sent!', { recipient: item })
					showError(t('polls', 'Error sending invitation to {displayName} ({emailAddress})', { emailAddress: item.emailAddress, displayName: item.displayName }))
				})
			}

		},
	},
}
</script>
