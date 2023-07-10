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
	<ConfigBox :title="t('polls', 'Shares')">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch class="add-share" />
		<ShareItemAllUsers v-if="allowAllAccess" />
		<SharePublicAdd v-if="allowPublicShares" />

		<div v-if="invitationShares.length" class="shares-list shared">
			<TransitionGroup :css="false" tag="div">
				<UserItem v-for="(share) in invitationShares"
					:key="share.id"
					v-bind="share"
					show-email
					:icon="true">
					<template #status>
						<div v-if="hasVoted(share.userId)">
							<VotedIcon class="vote-status voted" :title="t('polls', 'Has voted')" />
						</div>
						<div v-else-if="['public', 'group'].includes(share.type)">
							<div class="vote-status empty" />
						</div>
						<div v-else>
							<UnvotedIcon class="vote-status unvoted" :title="t('polls', 'Has not voted')" />
						</div>
					</template>

					<NcActions>
						<NcActionButton v-if="share.emailAddress || share.type === 'group'" @click="sendInvitation(share)">
							<template #icon>
								<SendEmailIcon />
							</template>
							{{ share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail') }}
						</NcActionButton>

						<NcActionButton v-if="share.type === 'user' || share.type === 'admin'" @click="switchAdmin({ share })">
							<template #icon>
								<GrantAdminIcon v-if="share.type === 'user'" />
								<WithdrawAdminIcon v-else />
							</template>
							{{ share.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access') }}
						</NcActionButton>

						<NcActionButton @click="copyLink({ url: share.URL })">
							<template #icon>
								<ClippyIcon />
							</template>
							{{ t('polls', 'Copy link to clipboard') }}
						</NcActionButton>

						<NcActionButton v-if="share.URL" @click="openQrModal({ url: share.URL })">
							<template #icon>
								<QrIcon />
							</template>
							{{ t('polls', 'Show QR code') }}
						</NcActionButton>

						<NcActionCaption v-if="share.type === 'public'" :title="t('polls', 'Options for the registration dialog')" />

						<NcActionRadio v-if="share.type === 'public'"
							name="publicPollEmail"
							value="optional"
							:checked="share.publicPollEmail === 'optional'"
							@change="setPublicPollEmail({ share, value: 'optional' })">
							{{ t('polls', 'Email address is optional') }}
						</NcActionRadio>

						<NcActionRadio v-if="share.type === 'public'"
							name="publicPollEmail"
							value="mandatory"
							:checked="share.publicPollEmail === 'mandatory'"
							@change="setPublicPollEmail({ share, value: 'mandatory' })">
							{{ t('polls', 'Email address is mandatory') }}
						</NcActionRadio>

						<NcActionRadio v-if="share.type === 'public'"
							name="publicPollEmail"
							value="disabled"
							:checked="share.publicPollEmail === 'disabled'"
							@change="setPublicPollEmail({ share, value: 'disabled' })">
							{{ t('polls', 'Do not ask for an email address') }}
						</NcActionRadio>
					</NcActions>

					<ActionDelete :title="t('polls', 'Remove share')"
						@delete="removeShare({ share })" />
				</UserItem>
			</TransitionGroup>
		</div>
		<NcModal v-if="qrModal" size="small" @close="qrModal=false">
			<QrModal :title="pollTitle"
				:description="pollDescription"
				:encode-text="qrText"
				class="modal__content">
				<template #description>
					<MarkUpDescription />
				</template>
			</QrModal>
		</NcModal>
	</ConfigBox>
</template>

<script>
import { mapGetters, mapActions, mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcActionCaption, NcActionRadio, NcModal } from '@nextcloud/vue'
import ActionDelete from '../Actions/ActionDelete.vue'
import ConfigBox from '../Base/ConfigBox.vue'
import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'
import UserSearch from '../User/UserSearch.vue'
import SharePublicAdd from './SharePublicAdd.vue'
import ShareItemAllUsers from './ShareItemAllUsers.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'
import SendEmailIcon from 'vue-material-design-icons/EmailArrowRight.vue'
import GrantAdminIcon from 'vue-material-design-icons/ShieldCrown.vue'
import WithdrawAdminIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import QrIcon from 'vue-material-design-icons/Qrcode.vue'
import QrModal from '../Base/QrModal.vue'
import MarkUpDescription from '../Poll/MarkUpDescription.vue'

export default {
	name: 'SharesList',

	components: {
		WithdrawAdminIcon,
		GrantAdminIcon,
		ClippyIcon,
		QrIcon,
		ShareIcon,
		SendEmailIcon,
		UnvotedIcon,
		UserSearch,
		VotedIcon,
		NcActions,
		NcActionButton,
		NcActionCaption,
		NcActionRadio,
		ActionDelete,
		ConfigBox,
		SharePublicAdd,
		ShareItemAllUsers,
		QrModal,
		NcModal,
		MarkUpDescription,
	},

	data() {
		return {
			qrModal: false,
			qrText: '',
		}
	},

	computed: {
		...mapState({
			allowAllAccess: (state) => state.poll.acl.allowAllAccess,
			allowPublicShares: (state) => state.poll.acl.allowPublicShares,
			pollAccess: (state) => state.poll.access,
			pollTitle: (state) => state.poll.title,
			pollDescription: (state) => state.poll.description,
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
			setPublicPollEmail: 'shares/setPublicPollEmail',
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

		copyLink(payload) {
			try {
				navigator.clipboard.writeText(payload.url)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},

		openQrModal(payload) {
			this.qrText = payload.url
			this.qrModal = true
		},
	},
}
</script>

<style lang="scss">
.shares-list.shared {
	border-top: 1px solid var(--color-border);
	padding-top: 24px;
	margin-top: 16px;
}

.vote-status {
	margin-left: 8px;
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes)
	}

	&.unvoted {
		color: var(--color-polls-foreground-no)
	}
}

</style>
