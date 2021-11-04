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
	<ConfigBox :title="t('polls', 'Active shares')" icon-class="icon-share">
		<UserSearch />
		<SharesPublicAdd v-if="allowPublicShares" />
		<TransitionGroup :css="false" tag="div" class="shared-list">
			<UserItem v-for="(share) in invitationShares"
				:key="share.id"
				v-bind="share"
				show-email
				:icon="true">
				<template #status>
					<div v-if="hasVoted(share.userId)">
						<VotedIcon class="vote-status voted" :title="t('polls', 'Has voted')" />
					</div>
					<div v-else-if="share.type === 'public'">
						<VotedImpossible class="vote-status" :title="t('polls', 'Is crazy')" />
					</div>
					<div v-else>
						<UnvotedIcon class="vote-status unvoted" :title="t('polls', 'Has not voted')" />
					</div>
				</template>

				<Actions>
					<ActionButton
						v-if="share.emailAddress || share.type === 'group'"
						icon="icon-confirm"
						@click="sendInvitation(share)">
						{{ share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail') }}
					</ActionButton>
					<ActionButton
						v-if="share.type === 'user' || share.type === 'admin'"
						:icon="share.type === 'user' ? 'icon-user-admin' : 'icon-user'"
						@click="switchAdmin({ share })">
						{{ share.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access') }}
					</ActionButton>
					<ActionButton icon="icon-clippy" @click="copyLink( { url: share.URL })">
						{{ t('polls', 'Copy link to clipboard') }}
					</ActionButton>
				</Actions>

				<ActionDelete
					:title="t('polls', 'Remove share')"
					@delete="removeShare({ share })" />
			</UserItem>
		</TransitionGroup>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapActions, mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { Actions, ActionButton } from '@nextcloud/vue'
import ActionDelete from '../Actions/ActionDelete'
import ConfigBox from '../Base/ConfigBox'
import VotedImpossible from 'vue-material-design-icons/AllInclusive.vue'
import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'
import UserSearch from '../User/UserSearch'
import SharesPublicAdd from './SharesPublicAdd'

export default {
	name: 'SharesEffective',

	components: {
		Actions,
		ActionButton,
		ActionDelete,
		ConfigBox,
		SharesPublicAdd,
		UnvotedIcon,
		UserSearch,
		VotedIcon,
		VotedImpossible,
	},

	computed: {
		...mapState({
			allowPublicShares: (state) => state.poll.acl.allowPublicShares,
		}),
		...mapGetters({
			invitationShares: 'shares/invitation',
			hasVoted: 'votes/hasVoted',
		}),
	},

	methods: {
		...mapActions({
			removeShare: 'shares/delete',
			switchAdmin: 'shares/switchAdmin',
		}),

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

		async copyLink(payload) {
			try {
				await this.$copyText(payload.url)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},
	},
}
</script>

<style lang="scss">
.vote-status {
	margin-left: 8px;

	&.voted {
		color: var(--color-polls-foreground-yes)
	}

	&.unvoted {
		color: var(--color-polls-foreground-no)
	}
}

</style>
