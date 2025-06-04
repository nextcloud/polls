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

import { AxiosError } from '@nextcloud/axios'

import {
	useSharesStore,
	Share,
	ShareType,
	PublicPollEmailConditions,
} from '../../stores/shares.ts'
import { SentResults } from '../../Api/modules/shares.ts'

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

type ButtonProps = {
	activate: boolean
	name: string
	action?: () => void
}

const resendInvitation = computed<ButtonProps>(() => ({
	activate:
		!share.deleted
		&& (!!share.user.emailAddress || share.type === ShareType.Group),
	name: share.invitationSent
		? t('polls', 'Resend invitation mail')
		: t('polls', 'Send invitation mail'),
	action: async () => {
		try {
			const result = await sharesStore.sendInvitation({ share })
			if (result?.sentResult) {
				handleInvitationResults(result.sentResult)
			}
		} catch (error) {
			showError(t('polls', 'Error sending invitation'))
		}
	},
}))

function handleInvitationResults(sentResult: SentResults) {
	if (sentResult?.sentMails) {
		sentResult.sentMails.forEach((item) => {
			showSuccess(
				t('polls', 'Invitation sent to {displayName} ({emailAddress})', {
					emailAddress: item.emailAddress,
					displayName: item.displayName,
				}),
			)
		})
	}
	if (sentResult?.abortedMails) {
		sentResult.abortedMails.forEach((item) => {
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
}

const resolveGroups = computed<ButtonProps>(() => ({
	activate:
		!resolving.value
		&& !share.deleted
		&& [ShareType.ContactGroup, ShareType.Circle].includes(share.type),
	name: t('polls', 'Resolve group into individual invitations'),
	action: async () => {
		if (resolving.value) return

		try {
			resolving.value = true
			await sharesStore.resolveGroup({ share })
		} catch (error) {
			if ((error as AxiosError).response?.status === 409) {
				const message = (error as AxiosError).response?.data as string
				resolveGroupResolveError(message)
				return
			}
		} finally {
			resolving.value = false
		}
	},
}))

function resolveGroupResolveError(message: string) {
	switch (message) {
		case 'Contacts is not enabled':
			return t(
				'polls',
				'Resolving of {name} is not possible. The contacts app is not enabled.',
				{ name: share.user.displayName },
			)
		case 'Circles is not enabled for this user':
			return t(
				'polls',
				'Resolving of {name} is not possible. The circles app is not enabled.',
				{ name: share.user.displayName },
			)
		default:
			return t('polls', 'Error resolving {name}.', {
				name: share.user.displayName,
			})
	}
}

const switchAdmin = computed<ButtonProps>(() => ({
	activate:
		!share.deleted
		&& (share.type === ShareType.User || share.type === ShareType.Admin),
	name:
		share.type === ShareType.User
			? t('polls', 'Grant poll admin access')
			: t('polls', 'Withdraw poll admin access'),
	action: () => {
		sharesStore.switchAdmin({ share })
	},
}))

const copyLinkButton = computed<ButtonProps>(() => ({
	activate: !share.deleted && !!share.URL,
	name: t('polls', 'Copy link to clipboard'),
	action: () => {
		try {
			navigator.clipboard.writeText(share.URL)
			showSuccess(t('polls', 'Link copied to clipboard'))
		} catch {
			showError(t('polls', 'Error while copying link to clipboard'))
		}
	},
}))

const showQrCodeButton = computed<ButtonProps>(() => ({
	activate: !share.deleted && !!share.URL,
	name: t('polls', 'Show QR code'),
	action: () => {
		emit('showQrCode')
	},
}))

const lockShareButton = computed<ButtonProps>(() => ({
	activate: !share.deleted,
	name: share.locked ? t('polls', 'Unlock share') : t('polls', 'Lock share'),
	action: () => {
		try {
			if (share.locked) {
				sharesStore.unlock({ share })
			} else {
				sharesStore.lock({ share })
			}
		} catch (error) {
			showError(
				t(
					'polls',
					'Error while changing lock status of share {displayName}',
					{
						displayName: share.user.displayName,
					},
				),
			)
		}
	},
}))

const deleteShareButton = computed<ButtonProps>(() => ({
	activate: true,
	name: share.deleted ? t('polls', 'Restore share') : t('polls', 'Delete share'),
	action: () => {
		try {
			if (share.deleted) {
				sharesStore.restore({ share })
			} else {
				sharesStore.delete({ share })
			}
		} catch (error) {
			showError(
				t(
					'polls',
					'Error while changing deleted status of share {displayName}',
					{
						displayName: share.user.displayName,
					},
				),
			)
		}
	},
}))

onMounted(() => {
	label.value.inputValue = share.label
})

/**
 *
 */
async function submitLabel() {
	sharesStore.writeLabel({
		token: share.token,
		label: label.value.inputValue,
	})
}
</script>

<template>
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
			v-if="resendInvitation.activate"
			close-after-click
			:name="resendInvitation.name"
			:aria-label="resendInvitation.name"
			@click="resendInvitation.action">
			<template #icon>
				<SendEmailIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="resolveGroups.activate"
			close-after-click
			:disabled="resolving"
			:name="resolveGroups.name"
			:aria-label="resolveGroups.name"
			@click="resolveGroups.action">
			<template #icon>
				<ResolveGroupIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="switchAdmin.activate"
			close-after-click
			:name="switchAdmin.name"
			:aria-label="switchAdmin.name"
			@click="switchAdmin.action">
			<template #icon>
				<GrantAdminIcon v-if="share.type === ShareType.User" />
				<WithdrawAdminIcon v-else />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="copyLinkButton.activate"
			close-after-click
			:name="copyLinkButton.name"
			:aria-label="copyLinkButton.name"
			@click="copyLinkButton.action">
			<template #icon>
				<ClippyIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="showQrCodeButton.activate"
			close-after-click
			:name="showQrCodeButton.name"
			:aria-label="showQrCodeButton.name"
			@click="showQrCodeButton.action">
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
			v-if="lockShareButton.activate"
			close-after-click
			:name="lockShareButton.name"
			:aria-label="lockShareButton.name"
			@click="lockShareButton.action">
			<template #icon>
				<UnlockIcon v-if="share.locked" />
				<LockIcon v-else />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="deleteShareButton.activate"
			close-after-click
			:name="deleteShareButton.name"
			:aria-label="deleteShareButton.name"
			@click="deleteShareButton.action">
			<template #icon>
				<RestoreIcon v-if="share.deleted" />
				<DeleteIcon v-else />
			</template>
		</NcActionButton>
	</NcActions>
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
