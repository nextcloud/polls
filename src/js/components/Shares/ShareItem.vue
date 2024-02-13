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
	<div :class="{ deleted: share.deleted }">
		<UserItem v-bind="userItemProps">
			<template #status>
				<div v-if="share.voted">
					<VotedIcon class="vote-status voted" :name="t('polls', 'Has voted')" />
				</div>
				<div v-else-if="['public', 'group'].includes(share.user.type)">
					<div class="vote-status empty" />
				</div>
				<div v-else>
					<UnvotedIcon class="vote-status unvoted" :name="t('polls', 'Has not voted')" />
				</div>
			</template>

			<NcActions>
				<NcActionInput v-if="isActivePublicShare"
					v-bind="label.inputProps"
					v-model:value="label.inputValue"
					@submit="submitLabel()">
					<template #icon>
						<EditIcon />
					</template>
				</NcActionInput>

				<NcActionButton v-if="activateResendInvitation"
					:name="share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail')"
					@click="sendInvitation()">
					<template #icon>
						<SendEmailIcon />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateResolveGroup"
					:name="t('polls', 'Resolve into individual invitations')"
					@click="resolveGroup(share)">
					<template #icon>
						<ResolveGroupIcon />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateSwitchAdmin"
					:name="share.user.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access')"
					@click="switchAdmin({ share: share })">
					<template #icon>
						<GrantAdminIcon v-if="share.user.type === 'user'" />
						<WithdrawAdminIcon v-else />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateCopyLink"
					:name="t('polls', 'Copy link to clipboard')"
					@click="copyLink()">
					<template #icon>
						<ClippyIcon />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateShowQr"
					:name="t('polls', 'Show QR code')"
					@click="$emit('show-qr-code')">
					<template #icon>
						<QrIcon />
					</template>
				</NcActionButton>

				<NcActionCaption v-if="isActivePublicShare" :name="t('polls', 'Options for the registration dialog')" />

				<NcActionRadio v-if="isActivePublicShare"
					name="publicPollEmail"
					value="optional"
					:checked="share.publicPollEmail === 'optional'"
					@change="setPublicPollEmail({ share, value: 'optional' })">
					{{ t('polls', 'Email address is optional') }}
				</NcActionRadio>

				<NcActionRadio v-if="isActivePublicShare"
					name="publicPollEmail"
					value="mandatory"
					:checked="share.publicPollEmail === 'mandatory'"
					@change="setPublicPollEmail({ share, value: 'mandatory' })">
					{{ t('polls', 'Email address is mandatory') }}
				</NcActionRadio>

				<NcActionRadio v-if="isActivePublicShare"
					name="publicPollEmail"
					value="disabled"
					:checked="share.publicPollEmail === 'disabled'"
					@change="setPublicPollEmail({ share, value: 'disabled' })">
					{{ t('polls', 'Do not ask for an email address') }}
				</NcActionRadio>
				<NcActionButton v-if="!share.deleted"
					:name="share.locked ? t('polls', 'Unlock share') : t('polls', 'Lock share')"
					@click="switchLocked(share)">
					<template #icon>
						<UnlockIcon v-if="share.locked" />
						<LockIcon v-else />
					</template>
				</NcActionButton>
				<NcActionButton v-if="!share.deleted"
					:name="t('polls', 'Delete share')"
					@click="deleteShare({ share })">
					<template #icon>
						<DeleteIcon />
					</template>
				</NcActionButton>
				<NcActionButton v-if="share.deleted"
					:name="t('polls', 'Restore share')"
					@click="restoreShare({ share })">
					<template #icon>
						<RestoreIcon />
					</template>
					{{ t('polls', 'Restore share') }}
				</NcActionButton>
			</NcActions>
		</UserItem>
	</div>
</template>

<script>
import { mapActions } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcActionCaption, NcActionInput, NcActionRadio } from '@nextcloud/vue'
import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'
import ResolveGroupIcon from 'vue-material-design-icons/CallSplit.vue'
import SendEmailIcon from 'vue-material-design-icons/EmailArrowRight.vue'
import GrantAdminIcon from 'vue-material-design-icons/ShieldCrown.vue'
import EditIcon from 'vue-material-design-icons/Pencil.vue'
import WithdrawAdminIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import QrIcon from 'vue-material-design-icons/Qrcode.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import UnlockIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'

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
		ResolveGroupIcon,
		DeleteIcon,
		RestoreIcon,
		LockIcon,
		UnlockIcon,
	},

	props: {
		share: {
			type: Object,
			default: undefined,
		},
	},

	emits: ['show-qr-code'],

	data() {
		return {
			label: {
				inputValue: '',
				inputProps: {
					success: false,
					error: false,
					showTrailingButton: true,
					labelOutside: false,
					label: t('polls', 'Share label'),
				},
			},
		}
	},

	computed: {
		isActivePublicShare() {
			return !this.share.deleted && this.share.user.type === 'public'
		},
		activateResendInvitation() {
			return !this.share.deleted && (this.share.user.emailAddress || this.share.user.type === 'group')
		},
		activateResolveGroup() {
			return !this.share.deleted && ['contactGroup', 'circle'].includes(this.share.user.type)
		},
		activateSwitchAdmin() {
			return !this.share.deleted && (this.share.user.type === 'user' || this.share.user.type === 'admin')
		},
		activateCopyLink() {
			return !this.share.deleted
		},
		activateShowQr() {
			return !this.share.deleted && !!this.share.URL
		},
		userItemProps() {
			return {
				user: this.share.user,
				label: this.share.label,
				showEmail: true,
				resolveInfo: true,
				forcedDescription: this.share.deleted ? `(${t('polls', 'deleted')})` : null,
				showTypeIcon: true,
				icon: true,

			}
		},
	},

	created() {
		this.label.inputValue = this.share.label
	},

	methods: {
		...mapActions({
			deleteShare: 'shares/delete',
			restoreShare: 'shares/restore',
			lockShare: 'shares/lock',
			unlockShare: 'shares/unlock',
			switchAdmin: 'shares/switchAdmin',
			setPublicPollEmail: 'shares/setPublicPollEmail',
			writeLabel: 'shares/writeLabel',
			deleteUser: 'votes/deleteUser',
		}),

		async switchLocked(share) {
			try {
				if (share.locked) {
					this.unlockShare({ share })
					showSuccess(t('polls', 'Share of {displayName} unlocked', { displayName: share.user.displayName }))
				} else {
					this.lockShare({ share })
					showSuccess(t('polls', 'Share of {displayName} locked', { displayName: share.user.displayName }))
				}
			} catch (e) {
				showError(t('polls', 'Error while changing lock status of user {displayName}', { displayName: share.user.displayName }))
				console.error('Error locking or unlocking share', { share }, e.response)
			}
		},

		async submitLabel() {
			this.writeLabel({ token: this.share.token, label: this.label.inputValue })
		},

		async resolveGroup(share) {
			try {
				await this.$store.dispatch('shares/resolveGroup', { share })
			} catch (e) {
				if (e.response.status === 409 && e.response.data === 'Circles is not enabled for this user') {
					showError(t('polls', 'Resolving of {name} is not possible. The circles app is not enabled.', { name: share.user.displayName }))
				} else if (e.response.status === 409 && e.response.data === 'Contacts is not enabled') {
					showError(t('polls', 'Resolving of {name} is not possible. The contacts app is not enabled.', { name: share.user.displayName }))
				} else {
					showError(t('polls', 'Error resolving {name}.', { name: share.user.displayName }))
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
.deleted .user-item .description {
	color: var(--color-error-text);
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
