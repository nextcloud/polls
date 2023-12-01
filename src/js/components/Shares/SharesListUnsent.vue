<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<ConfigBox v-if="unsentInvitations.length" :name="t('polls', 'Unsent invitations')">
		<template #icon>
			<EmailAlertIcon />
		</template>
		<template #actions>
			<NcButton :title="t('polls', 'Resolve and send all invitations')"
				:aria-label="t('polls', 'Resolve and send all invitations')"
				type="tertiary"
				@click="sendAllInvitations()">
				<template #icon>
					<BulkMailIcon />
				</template>
			</NcButton>
		</template>
		<TransitionGroup :css="false" tag="div" class="shares-list">
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
			removeShare: 'shares/delete',
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
					console.error('Mail could not be sent!', { recipient: item })
					showError(t('polls', 'Error sending invitation to {displayName} ({emailAddress})', { emailAddress: item.emailAddress, displayName: item.displayName }))
				})
			}

		},
	},
}
</script>
