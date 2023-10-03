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
	<UserItem v-bind="share"
		show-email
		resolve-info
		:forced-description="share.revoked ? t('polls', 'User is only able to see the votes.') : null"
		:icon="true">
		<template #status>
			<div v-if="share.voted">
				<VotedIcon class="vote-status voted" :title="t('polls', 'Has voted')" />
			</div>
			<div v-else-if="['public', 'group'].includes(share.type)">
				<div class="vote-status empty" />
			</div>
			<div v-else>
				<UnvotedIcon class="vote-status unvoted" :title="t('polls', 'Has not voted')" />
			</div>
		</template>

		<NcActions v-if="!share.revoked">
			<NcActionInput v-if="share.type === 'public'"
				:show-trailing-button="false"
				:value.sync="label"
				@input="writeLabel()">
				<template #icon>
					<EditIcon />
				</template>
				{{ t('polls', 'Share label') }}
			</NcActionInput>

			<NcActionButton v-if="share.emailAddress || share.type === 'group'" @click="sendInvitation()">
				<template #icon>
					<SendEmailIcon />
				</template>
				{{ share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail') }}
			</NcActionButton>

			<NcActionButton v-if="['contactGroup', 'circle'].includes(share.type)"
				@click="resolveGroup(share)">
				<template #icon>
					<ResolveGroupIcon />
				</template>
				{{ t('polls', 'Resolve into individual invitations') }}
			</NcActionButton>

			<NcActionButton v-if="share.type === 'user' || share.type === 'admin'" @click="switchAdmin({ share: share })">
				<template #icon>
					<GrantAdminIcon v-if="share.type === 'user'" />
					<WithdrawAdminIcon v-else />
				</template>
				{{ share.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access') }}
			</NcActionButton>

			<NcActionButton @click="copyLink()">
				<template #icon>
					<ClippyIcon />
				</template>
				{{ t('polls', 'Copy link to clipboard') }}
			</NcActionButton>

			<NcActionButton v-if="share.URL" @click="$emit('show-qr-code')">
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

		<NcActions v-if="share.revoked">
			<NcActionButton @click="reRevokeShare({ share })">
				<template #icon>
					<ReRevokeIcon />
				</template>
				{{ t('polls', 'Re-Revoke share') }}
			</NcActionButton>
		</NcActions>

		<ActionDelete :timeout="share.revoked ? 4 : 0"
			:revoke="!!share.voted && !share.revoked"
			:title="deleteButtonCaption"
			@delete="clickDeleted(share)" />
	</UserItem>
</template>

<script>
import { mapActions } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcActionCaption, NcActionInput, NcActionRadio } from '@nextcloud/vue'
import { ActionDelete } from '../Actions/index.js'
import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'
import ResolveGroupIcon from 'vue-material-design-icons/CallSplit.vue'
import SendEmailIcon from 'vue-material-design-icons/EmailArrowRight.vue'
import GrantAdminIcon from 'vue-material-design-icons/ShieldCrown.vue'
import EditIcon from 'vue-material-design-icons/Pencil.vue'
import WithdrawAdminIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import QrIcon from 'vue-material-design-icons/Qrcode.vue'
import ReRevokeIcon from 'vue-material-design-icons/Recycle.vue'

export default {
	name: 'ShareItem',

	components: {
		WithdrawAdminIcon,
		GrantAdminIcon,
		ClippyIcon,
		EditIcon,
		QrIcon,
		SendEmailIcon,
		UnvotedIcon,
		VotedIcon,
		NcActions,
		NcActionButton,
		NcActionCaption,
		NcActionInput,
		NcActionRadio,
		ActionDelete,
		ResolveGroupIcon,
		ReRevokeIcon,
	},

	props: {
		share: {
			type: Object,
			default: undefined,
		},
	},

	computed: {
		label: {
			get() {
				return this.share.displayName
			},
			set(value) {
				this.$store.commit('shares/setShareProperty', { id: this.share.id, displayName: value })
			},
		},
		deleteButtonCaption() {
			if (this.share.voted && this.share.revoked) {
				return t('polls', 'Delete share and remove user from poll')
			}

			if (this.share.voted && !this.share.revoked) {
				return t('polls', 'Revoke share')
			}

			return t('polls', 'Delete share')
		},

	},

	methods: {
		...mapActions({
			deleteShare: 'shares/delete',
			revokeShare: 'shares/revoke',
			reRevokeShare: 'shares/reRevoke',
			switchAdmin: 'shares/switchAdmin',
			setPublicPollEmail: 'shares/setPublicPollEmail',
			setLabel: 'shares/writeLabel',
			deleteUser: 'votes/deleteUser',
		}),

		async clickDeleted(share) {
			try {
				if (share.voted && share.revoked) {
					this.deleteShare({ share })
					this.deleteUser({ userId: share.userId })
					showSuccess(t('polls', 'Deleted share and votes for {displayName}', { displayName: share.displayName }))
				} else if (share.voted && !share.revoked) {
					this.revokeShare({ share })
					showSuccess(t('polls', 'Share for user {displayName} revoked', { displayName: share.displayName }))
				} else {
					this.deleteShare({ share })
					showSuccess(t('polls', 'Deleted share for user {displayName}', { displayName: share.displayName }))
				}
			} catch (e) {
				showError(t('polls', 'Error deleting or revoking share for user {displayName}', { displayName: share.displayName }))
				console.error('Error deleting or revoking share', { share }, e.response)
			}
		},

		async writeLabel() {
			this.setLabel({ token: this.share.token, displayName: this.share.displayName })
		},

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

		async sendInvitation() {
			const response = await this.$store.dispatch('shares/sendInvitation', { share: this.share })
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

		copyLink() {
			try {
				navigator.clipboard.writeText(this.share.URL)
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
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes)
	}

	&.unvoted {
		color: var(--color-polls-foreground-no)
	}
}

</style>
