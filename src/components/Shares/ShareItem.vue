<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcActionCaption from '@nextcloud/vue/components/NcActionCaption'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionRadio from '@nextcloud/vue/components/NcActionRadio'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

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

import { Logger } from '../../helpers/index.ts'
import UserItem from '../User/UserItem.vue'
import { AxiosError } from '@nextcloud/axios'

import {
	useSharesStore,
	Share,
	ShareType,
	PublicPollEmailConditions,
} from '../../stores/shares.ts'

const emit = defineEmits(['showQrCode'])

const { share } = defineProps<{ share: Share }>()

const sharesStore = useSharesStore()

const resolving = ref(false)
const label = ref({
	inputValue: '',
	inputProps: {
		success: false,
		error: false,
		showTrailingButton: true,
		labelOutside: false,
		label: t('polls', 'Share label'),
	},
})

const isActivePublicShare = computed(
	() => !share.deleted && share.type === ShareType.Public,
)
const activateResendInvitation = computed(
	() =>
		!share.deleted
		&& (share.user.emailAddress || share.type === ShareType.Group),
)
const activateResolveGroup = computed(
	() =>
		!share.deleted
		&& [ShareType.ContactGroup, ShareType.Circle].includes(share.type),
)
const activateSwitchAdmin = computed(
	() =>
		!share.deleted
		&& (share.type === ShareType.User || share.type === ShareType.Admin),
)
const activateCopyLink = computed(() => !share.deleted)
const activateShowQr = computed(() => !share.deleted && !!share.URL)
const userItemProps = computed(() => ({
	user: share.user,
	label: share.label,
	showEmail: true,
	resolveInfo: true,
	forcedDescription: share.deleted ? `(${t('polls', 'deleted')})` : null,
	showTypeIcon: true,
	icon: true,
}))

onMounted(() => {
	label.value.inputValue = share.label
})

/**
 *
 * @param share
 */
async function switchLocked(share: Share) {
	try {
		if (share.locked) {
			sharesStore.unlock({ share })
			showSuccess(
				t('polls', 'Share of {displayName} unlocked', {
					displayName: share.user.displayName,
				}),
			)
		} else {
			sharesStore.lock({ share })
			showSuccess(
				t('polls', 'Share of {displayName} locked', {
					displayName: share.user.displayName,
				}),
			)
		}
	} catch (error) {
		showError(
			t('polls', 'Error while changing lock status of share {displayName}', {
				displayName: share.user.displayName,
			}),
		)
		Logger.error('Error locking or unlocking share', {
			share,
			error,
		})
	}
}

/**
 *
 */
async function submitLabel() {
	sharesStore.writeLabel({
		token: share.token,
		label: label.value.inputValue,
	})
}

/**
 *
 * @param share
 */
async function resolveGroup(share: Share) {
	if (resolving.value) {
		return
	}

	resolving.value = true

	try {
		await sharesStore.resolveGroup({ share })
	} catch (error) {
		if (
			(error as AxiosError).response?.status === 409
			&& (error as AxiosError).response?.data
				=== 'Circles is not enabled for this user'
		) {
			showError(
				t(
					'polls',
					'Resolving of {name} is not possible. The circles app is not enabled.',
					{ name: share.user.displayName },
				),
			)
		} else if (
			(error as AxiosError).response?.status === 409
			&& (error as AxiosError).response?.data === 'Contacts is not enabled'
		) {
			showError(
				t(
					'polls',
					'Resolving of {name} is not possible. The contacts app is not enabled.',
					{ name: share.user.displayName },
				),
			)
		} else {
			showError(
				t('polls', 'Error resolving {name}.', {
					name: share.user.displayName,
				}),
			)
		}
	} finally {
		resolving.value = false
	}
}

/**
 *
 */
async function sendInvitation() {
	try {
		const response = await sharesStore.sendInvitation({ share })
		if (response.data?.sentResult?.sentMails) {
			response.data.sentResult.sentMails.forEach((item) => {
				showSuccess(
					t('polls', 'Invitation sent to {displayName} ({emailAddress})', {
						emailAddress: item.emailAddress,
						displayName: item.displayName,
					}),
				)
			})
		}
		if (response.data?.sentResult?.abortedMails) {
			response.data.sentResult.abortedMails.forEach((item) => {
				Logger.error('Mail could not be sent!', { recipient: item })
				showError(
					t(
						'polls',
						'Error sending invitation to {displayName} ({emailAddress})',
						{
							emailAddress: item.emailAddress,
							displayName: item.displayName,
						},
					),
				)
			})
		}
	} catch (error) {
		showError(t('polls', 'Error sending invitation'))
		Logger.error('Error sending invitation', { error })
	}
}

/**
 *
 */
function copyLink() {
	try {
		navigator.clipboard.writeText(share.URL)
		showSuccess(t('polls', 'Link copied to clipboard'))
	} catch {
		showError(t('polls', 'Error while copying link to clipboard'))
	}
}
</script>

<template>
	<div :class="{ deleted: share.deleted }">
		<UserItem
			v-bind="userItemProps"
			:deleted-state="share.deleted"
			:locked-state="share.locked">
			<template #status>
				<div v-if="share.voted">
					<VotedIcon
						class="vote-status voted"
						:name="t('polls', 'Has voted')" />
				</div>
				<div
					v-else-if="
						[ShareType.Public, ShareType.Group].includes(share.type)
					">
					<div class="vote-status empty" />
				</div>
				<div v-else>
					<UnvotedIcon
						class="vote-status unvoted"
						:name="t('polls', 'Has not voted')" />
				</div>
			</template>

			<NcActions>
				<NcActionInput
					v-if="isActivePublicShare"
					v-bind="label.inputProps"
					v-model="label.inputValue"
					@submit="submitLabel()">
					<template #icon>
						<EditIcon />
					</template>
				</NcActionInput>

				<NcActionButton
					v-if="activateResendInvitation"
					:name="
						share.invitationSent
							? t('polls', 'Resend invitation mail')
							: t('polls', 'Send invitation mail')
					"
					:aria-label="
						share.invitationSent
							? t('polls', 'Resend invitation mail')
							: t('polls', 'Send invitation mail')
					"
					@click="sendInvitation()">
					<template #icon>
						<SendEmailIcon />
					</template>
				</NcActionButton>

				<NcActionButton
					v-if="activateResolveGroup"
					:disabled="resolving"
					:name="t('polls', 'Resolve into individual invitations')"
					:aria-label="t('polls', 'Resolve into individual invitations')"
					@click="resolveGroup(share)">
					<template #icon>
						<ResolveGroupIcon />
					</template>
				</NcActionButton>

				<NcActionButton
					v-if="activateSwitchAdmin"
					:name="
						share.type === ShareType.User
							? t('polls', 'Grant poll admin access')
							: t('polls', 'Withdraw poll admin access')
					"
					:aria-label="
						share.type === ShareType.User
							? t('polls', 'Grant poll admin access')
							: t('polls', 'Withdraw poll admin access')
					"
					@click="sharesStore.switchAdmin({ share: share })">
					<template #icon>
						<GrantAdminIcon v-if="share.type === ShareType.User" />
						<WithdrawAdminIcon v-else />
					</template>
				</NcActionButton>

				<NcActionButton
					v-if="activateCopyLink"
					:name="t('polls', 'Copy link to clipboard')"
					:aria-label="t('polls', 'Copy link to clipboard')"
					@click="copyLink()">
					<template #icon>
						<ClippyIcon />
					</template>
				</NcActionButton>

				<NcActionButton
					v-if="activateShowQr"
					:name="t('polls', 'Show QR code')"
					:aria-label="t('polls', 'Show QR code')"
					@click="emit('showQrCode')">
					<template #icon>
						<QrIcon />
					</template>
				</NcActionButton>

				<NcActionCaption
					v-if="isActivePublicShare"
					:name="t('polls', 'Options for the registration dialog')" />

				<NcActionRadio
					v-if="isActivePublicShare"
					name="publicPollEmail"
					:value="PublicPollEmailConditions.Optional"
					:model-value="share.publicPollEmail"
					@update:model-value="
						sharesStore.setPublicPollEmail({
							share,
							value: PublicPollEmailConditions.Optional,
						})
					">
					{{ t('polls', 'Email address is optional') }}
				</NcActionRadio>

				<NcActionRadio
					v-if="isActivePublicShare"
					name="publicPollEmail"
					:value="PublicPollEmailConditions.Mandatory"
					:model-value="share.publicPollEmail"
					@update:model-value="
						sharesStore.setPublicPollEmail({
							share,
							value: PublicPollEmailConditions.Mandatory,
						})
					">
					{{ t('polls', 'Email address is mandatory') }}
				</NcActionRadio>

				<NcActionRadio
					v-if="isActivePublicShare"
					name="publicPollEmail"
					:value="PublicPollEmailConditions.Disabled"
					:model-value="share.publicPollEmail"
					@update:model-value="
						sharesStore.setPublicPollEmail({
							share,
							value: PublicPollEmailConditions.Disabled,
						})
					">
					{{ t('polls', 'Do not ask for an email address') }}
				</NcActionRadio>
				<NcActionButton
					v-if="!share.deleted"
					:name="
						share.locked
							? t('polls', 'Unlock share')
							: t('polls', 'Lock share')
					"
					:aria-label="
						share.locked
							? t('polls', 'Unlock share')
							: t('polls', 'Lock share')
					"
					@click="switchLocked(share)">
					<template #icon>
						<UnlockIcon v-if="share.locked" />
						<LockIcon v-else />
					</template>
				</NcActionButton>
				<NcActionButton
					v-if="!share.deleted"
					:name="t('polls', 'Delete share')"
					:aria-label="t('polls', 'Delete share')"
					@click="sharesStore.delete({ share })">
					<template #icon>
						<DeleteIcon />
					</template>
				</NcActionButton>
				<NcActionButton
					v-if="share.deleted"
					:name="t('polls', 'Restore share')"
					:aria-label="t('polls', 'Restore share')"
					@click="sharesStore.restore({ share })">
					<template #icon>
						<RestoreIcon />
					</template>
					{{ t('polls', 'Restore share') }}
				</NcActionButton>
			</NcActions>
		</UserItem>
	</div>
</template>

<style lang="scss">
.deleted .user-item .description {
	color: var(--color-error-text);
}

.vote-status {
	margin-inline-start: 8px;
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes);
	}

	&.unvoted {
		color: var(--color-polls-foreground-no);
	}
}
</style>
