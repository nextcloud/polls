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
	<ConfigBox :title="t('polls', 'Effective shares')" icon-class="icon-share">
		<TransitionGroup :css="false" tag="div" class="shared-list">
			<UserItem v-for="(share) in invitationShares"
				:key="share.id"
				v-bind="share"
				:icon="true">
				<Actions>
					<ActionButton
						v-if="share.emailAddress || share.type === 'group'"
						icon="icon-confirm"
						@click="sendInvitation(share)">
						{{ share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail') }}
					</ActionButton>
					<ActionButton icon="icon-clippy" @click="copyLink( { url: share.URL })">
						{{ t('polls', 'Copy link to clipboard') }}
					</ActionButton>
				</Actions>
				<Actions>
					<ActionButton icon="icon-delete" @click="removeShare(share)">
						{{ t('polls', 'Remove share') }}
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
	name: 'SharesEffective',

	components: {
		Actions,
		ActionButton,
		ConfigBox,
	},

	data() {
		return {
			users: [],
			isLoading: false,
			placeholder: t('polls', 'Enter a name to start the search'),
		}
	},

	computed: {
		...mapGetters({
			invitationShares: 'shares/invitation',
		}),
	},

	methods: {
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

		async copyLink(payload) {
			try {
				await this.$copyText(payload.url)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
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
