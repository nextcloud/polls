<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions primary>
		<template #icon>
			<SettingsIcon :size="20" decorative />
		</template>
		<NcActionButton v-if="$route.name === 'publicVote'"
			:name="t('polls', 'Copy your personal link to clipboard')"
			:aria-label="t('polls', 'Copy your personal link to clipboard')"
			@click="copyLink()">
			<template #icon>
				<ClippyIcon />
			</template>
		</NcActionButton>
		<NcActionSeparator v-if="$route.name === 'publicVote'" />
		<ActionInputEmailAddress v-if="$route.name === 'publicVote'" />
		<ActionInputDisplayName v-if="$route.name === 'publicVote' && pollStore.permissions.vote" />
		<NcActionButton v-if="$route.name === 'publicVote'"
			:name="t('polls', 'Get your personal link per mail')"
			:aria-label="t('polls', 'Get your personal link per mail')"
			:disabled="!shareStore.user.emailAddress"
			@click="resendInvitation()">
			<template #icon>
				<SendLinkPerEmailIcon />
			</template>
		</NcActionButton>
		<NcActionCheckbox :checked="subscriptionStore.subscribed"
			:disabled="!pollStore.permissions.subscribe"
			title="check"
			@change="toggleSubscription">
			{{ t('polls', 'Subscribe to notifications') }}
		</NcActionCheckbox>
		<NcActionButton v-if="$route.name === 'publicVote' && shareStore.user.emailAddress"
			:name="t('polls', 'Remove Email Address')"
			:aria-label="t('polls', 'Remove Email Address')"
			@click="deleteEmailAddress">
			<template #icon>
				<DeleteIcon />
			</template>
		</NcActionButton>
		<NcActionButton v-if="pollStore.permissions.edit"
			:name="t('polls', 'Copy list of email addresses to clipboard')"
			:aria-label="t('polls', 'Copy list of email addresses to clipboard')"
			@click="getAddresses()">
			<template #icon>
				<ClippyIcon />
			</template>
		</NcActionButton>
		<NcActionButton v-if="pollStore.permissions.vote"
			:name="t('polls', 'Reset your votes')"
			:aria-label="t('polls', 'Reset your votes')"
			@click="resetVotes()">
			<template #icon>
				<ResetVotesIcon />
			</template>
		</NcActionButton>
		<NcActionButton v-if="$route.name === 'publicVote' && hasCookie"
			:name="t('polls', 'Logout as {name} (delete cookie)', { name: aclStore.currentUser.displayName })"
			:aria-label="t('polls', 'Logout as {name} (delete cookie)', { name: aclStore.currentUser.displayName })"
			@click="logout()">
			<template #icon>
				<LogoutIcon />
			</template>
		</NcActionButton>
	</NcActions>
</template>

<script>
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcActionCheckbox, NcActionSeparator } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import SettingsIcon from 'vue-material-design-icons/Cog.vue'
import SendLinkPerEmailIcon from 'vue-material-design-icons/LinkVariant.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import ResetVotesIcon from 'vue-material-design-icons/Undo.vue'
import LogoutIcon from 'vue-material-design-icons/Logout.vue'
import { deleteCookieByValue, findCookieByValue } from '../../helpers/index.js'
import { PollsAPI } from '../../Api/index.js'
import { t } from '@nextcloud/l10n'
import { useAclStore } from '../../stores/acl.ts'
import { usePollStore } from '../../stores/poll.ts'
import { useShareStore } from '../../stores/share.ts'
import { useSubscriptionStore } from '../../stores/subscription.ts'
import ActionInputDisplayName from './ActionInputDisplayName.vue'
import ActionInputEmailAddress from './ActionInputEmailAddress.vue'

export default {
	name: 'UserMenu',

	components: {
		NcActions,
		NcActionButton,
		NcActionCheckbox,
		NcActionSeparator,
		SettingsIcon,
		LogoutIcon,
		SendLinkPerEmailIcon,
		DeleteIcon,
		ClippyIcon,
		ResetVotesIcon,
		ActionInputDisplayName,
		ActionInputEmailAddress,
	},

	computed: {
		...mapStores(useAclStore, usePollStore, useShareStore, useSubscriptionStore),
		
		hasCookie() {
			return !!findCookieByValue(this.$route.params.token)
		},

		personalLink() {
			return window.location.origin
				+ this.$router.resolve({
					name: 'publicVote',
					params: { token: this.$route.params.token },
				}).href
		},
	},

	methods: {
		t,
		logout() {
			const reRouteTo = deleteCookieByValue(this.$route.params.token)
			if (reRouteTo) {
				this.$router.push({ name: 'publicVote', params: { token: reRouteTo } })
			}
		},

		async toggleSubscription() {
			this.subscriptionStore.subscribed = !this.subscriptionStore.subscribed
			this.subscriptionStore.write()
		},

		async deleteEmailAddress() {
			try {
				await this.shareStore.deleteEmailAddress()
				showSuccess(t('polls', 'Email address deleted.'))
			} catch {
				showError(t('polls', 'Error deleting email address {emailAddress}', { emailAddress: this.shareStore.user.emailAddress }))
			}
		},

		async resendInvitation() {
			try {
				const response = await this.shareStore.resendInvitation()
				showSuccess(t('polls', 'Invitation resent to {emailAddress}', { emailAddress: response.data.shareStore.user.emailAddress }))
			} catch {
				showError(t('polls', 'Mail could not be resent to {emailAddress}', { emailAddress: this.shareStore.user.emailAddress }))
			}
		},

		async copyLink() {
			try {
				await navigator.clipboard.writeText(this.personalLink)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},

		async getAddresses() {
			try {
				const response = await PollsAPI.getParticipantsEmailAddresses(this.$route.params.id)
				await navigator.clipboard.writeText(response.data.map((item) => item.combined))
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},

		async resetVotes() {
			try {
				await this.votes.resetVotes()
				showSuccess(t('polls', 'Your votes are reset'))
			} catch {
				showError(t('polls', 'Error while resetting votes'))
			}
		},
	},
}
</script>
