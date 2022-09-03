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
	<ConfigBox v-if="unsentInvitations.length" :title="t('polls', 'Unsent invitations')">
		<template #icon>
			<EmailAlertIcon />
		</template>
		<TransitionGroup :css="false" tag="div" class="shares-list">
			<UserItem v-for="(share) in unsentInvitations"
				:key="share.id"
				v-bind="share"
				show-email
				resolve-info
				:icon="true">
				<NcActions>
					<NcActionButton v-if="share.emailAddress || share.type === 'group'"
						icon="icon-confirm"
						@click="sendInvitation(share)">
						{{ t('polls', 'Send invitation mail') }}
					</NcActionButton>
					<NcActionButton v-if="['contactGroup', 'circle'].includes(share.type)"
						icon="icon-toggle-filelist"
						@click="resolveGroup(share)">
						{{ t('polls', 'Resolve into individual invitations') }}
					</NcActionButton>
				</NcActions>
				<ActionDelete :title="t('polls', 'Remove invitation')"
					@delete="removeShare({ share })" />
			</UserItem>
		</TransitionGroup>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import ActionDelete from '../Actions/ActionDelete.vue'
import ConfigBox from '../Base/ConfigBox.vue'
import EmailAlertIcon from 'vue-material-design-icons/EmailAlert.vue'

export default {
	name: 'SharesListUnsent',

	components: {
		EmailAlertIcon,
		NcActions,
		NcActionButton,
		ActionDelete,
		ConfigBox,
	},

	computed: {
		...mapGetters({
			unsentInvitations: 'shares/unsentInvitations',
		}),
	},

	methods: {
		...mapActions({
			removeShare: 'shares/delete',
		}),

		async resolveGroup(share) {
			try {
				await this.$store.dispatch('shares/resolveGroup', { share })
			} catch (e) {
				if (e.response.status === 409 && e.response.data === 'Circles is not enabled for this user') {
					showError(t('polls', 'Resolving of {name} is not possible. The circles app is not enabled.', { name: share.displayName }))
				} else if (e.response.status === 409 && e.response.data === 'Contacts is not enabled') {
					showError(t('polls', 'Resolving of {name} is not possible. The contacts app is not enabled.', { name: share.displayName }))
				} else {
					showError(t('polls', 'Error resolving {name}.', { name: share.displayName }))
				}
			}
		},

		async sendInvitation(share) {
			const response = await this.$store.dispatch('shares/sendInvitation', { share })
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
