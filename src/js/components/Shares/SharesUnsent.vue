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
	<ConfigBox v-if="unsentInvitations.length" :title="t('polls', 'Unsent invitations')" icon-class="icon-polls-mail">
		<TransitionGroup :css="false" tag="div" class="shared-list">
			<UserItem v-for="(share) in unsentInvitations"
				:key="share.id"
				v-bind="share"
				:icon="true">
				<Actions>
					<ActionButton
						v-if="share.emailAddress || share.type === 'group'"
						icon="icon-confirm"
						@click="sendInvitation(share)">
						{{ t('polls', 'Send invitation mail') }}
					</ActionButton>
					<ActionButton
						v-if="share.type === 'contactGroup' || share.type === 'circle'"
						icon="icon-toggle-filelist"
						@click="resolveGroup(share)">
						{{ t('polls', 'Resolve into individual invitations') }}
					</ActionButton>
				</Actions>
				<Actions>
					<ActionButton icon="icon-delete" @click="removeShare(share)">
						{{ t('polls', 'Remove invitation') }}
					</ActionButton>
				</Actions>
			</UserItem>
		</TransitionGroup>
	</ConfigBox>
</template>

<script>
import { mapGetters } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { Actions, ActionButton } from '@nextcloud/vue'
import ConfigBox from '../Base/ConfigBox'

export default {
	name: 'SharesUnsent',

	components: {
		Actions,
		ActionButton,
		ConfigBox,
	},

	computed: {
		...mapGetters({
			unsentInvitations: 'shares/unsentInvitations',
		}),
	},

	methods: {
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
					showSuccess(t('polls', 'Invitation sent to {emailAddress}', { emailAddress: item }))
				})
			}
			if (response.data?.sentResult?.abortedMails) {
				response.data.sentResult.abortedMails.forEach((item) => {
					console.error('Mail could not be sent!', { recipient: item })
					showError(t('polls', 'Error sending invitation to {emailAddress}', { emailAddress: item }))
				})
			}
		},

		removeShare(share) {
			this.$store.dispatch('shares/delete', { share })
		},
	},
}
</script>

<style lang="scss">
	.shared-list {
		display: flex;
		flex-wrap: wrap;
		flex-direction: column;
		justify-content: flex-start;
		padding-top: 8px;

		> li {
			display: flex;
			align-items: stretch;
			margin: 4px 0;
		}
	}

	.share-item {
		display: flex;
		flex: 1;
		align-items: center;
		max-width: 100%;
	}

</style>
