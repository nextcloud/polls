<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="{ deleted: share.deleted }">
		<UserItem v-bind="userItemProps"
			:deleted-state="share.deleted"
			:locked-state="share.locked">
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
					:value.sync="label.inputValue"
					@submit="submitLabel()">
					<template #icon>
						<EditIcon />
					</template>
				</NcActionInput>

				<NcActionButton v-if="activateResendInvitation"
					:name="share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail')"
					:aria-label="share.invitationSent ? t('polls', 'Resend invitation mail') : t('polls', 'Send invitation mail')"
					@click="sendInvitation()">
					<template #icon>
						<SendEmailIcon />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateResolveGroup"
					:disabled="resolving"
					:name="t('polls', 'Resolve into individual invitations')"
					:aria-label="t('polls', 'Resolve into individual invitations')"
					@click="resolveGroup(share)">
					<template #icon>
						<ResolveGroupIcon />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateSwitchAdmin"
					:name="share.user.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access')"
					:aria-label="share.user.type === 'user' ? t('polls', 'Grant poll admin access') : t('polls', 'Withdraw poll admin access')"
					@click="switchAdmin({ share: share })">
					<template #icon>
						<GrantAdminIcon v-if="share.user.type === 'user'" />
						<WithdrawAdminIcon v-else />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateCopyLink"
					:name="t('polls', 'Copy link to clipboard')"
					:aria-label="t('polls', 'Copy link to clipboard')"
					@click="copyLink()">
					<template #icon>
						<ClippyIcon />
					</template>
				</NcActionButton>

				<NcActionButton v-if="activateShowQr"
					:name="t('polls', 'Show QR code')"
					:aria-label="t('polls', 'Show QR code')"
					@click="$emit('show-qr-code')">
					<template #icon>
						<QrIcon />
					</template>
				</NcActionButton>

				<NcActionCaption v-if="isActivePublicShare" :name="t('polls', 'Options for the registration dialog')" />

				<NcActionRadio v-if="isActivePublicShare"
					:key="publicPollEmail"
					v-model="publicPollEmail"
					name="publicPollEmail"
					value="optional">
					{{ t('polls', 'Email address is optional') }}
				</NcActionRadio>

				<NcActionRadio v-if="isActivePublicShare"
					:key="publicPollEmail"
					v-model="publicPollEmail"
					name="publicPollEmail"
					value="mandatory">
					{{ t('polls', 'Email address is mandatory') }}
				</NcActionRadio>

				<NcActionRadio v-if="isActivePublicShare"
					:key="publicPollEmail"
					v-model="publicPollEmail"
					name="publicPollEmail"
					value="disabled">
					{{ t('polls', 'Do not ask for an email address') }}
				</NcActionRadio>

				<NcActionButton v-if="!share.deleted"
					:name="share.locked ? t('polls', 'Unlock share') : t('polls', 'Lock share')"
					:aria-label="share.locked ? t('polls', 'Unlock share') : t('polls', 'Lock share')"
					@click="switchLocked(share)">
					<template #icon>
						<UnlockIcon v-if="share.locked" />
						<LockIcon v-else />
					</template>
				</NcActionButton>
				<NcActionButton v-if="!share.deleted"
					:name="t('polls', 'Delete share')"
					:aria-label="t('polls', 'Delete share')"
					@click="deleteShare({ share })">
					<template #icon>
						<DeleteIcon />
					</template>
				</NcActionButton>
				<NcActionButton v-if="share.deleted"
					:name="t('polls', 'Restore share')"
					:aria-label="t('polls', 'Restore share')"
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
import { Logger } from '../../helpers/index.js'

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

	data() {
		return {
			resolving: false,
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
		publicPollEmail: {
			get() {
				return this.share.publicPollEmail
			},
			set(value) {
				this.$store.dispatch('shares/setPublicPollEmail', { share: this.share, value })
			},
		},

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
			writeLabel: 'shares/writeLabel',
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
			} catch (error) {
				showError(t('polls', 'Error while changing lock status of share {displayName}', { displayName: share.user.displayName }))
				Logger.error('Error locking or unlocking share', { share, error })
			}
		},

		async submitLabel() {
			this.writeLabel({ token: this.share.token, label: this.label.inputValue })
		},

		async resolveGroup(share) {
			if (this.resolving) {
				return
			}

			this.resolving = true

			try {
				await this.$store.dispatch('shares/resolveGroup', { share })
			} catch (error) {
				if (error.response.status === 409 && error.response.data === 'Circles is not enabled for this user') {
					showError(t('polls', 'Resolving of {name} is not possible. The circles app is not enabled.', { name: share.user.displayName }))
				} else if (error.response.status === 409 && error.response.data === 'Contacts is not enabled') {
					showError(t('polls', 'Resolving of {name} is not possible. The contacts app is not enabled.', { name: share.user.displayName }))
				} else {
					showError(t('polls', 'Error resolving {name}.', { name: share.user.displayName }))
				}
			} finally {
				this.resolving = false
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
					Logger.error('Mail could not be sent!', { recipient: item })
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
	margin-inline-start: 8px;
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes)
	}

	&.unvoted {
		color: var(--color-polls-foreground-no)
	}
}

</style>
