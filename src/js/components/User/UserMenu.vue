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
		<NcActionInput v-if="share.type === 'external'"
			v-bind="userEmail.inputProps"
			:value.sync="userEmail.inputValue"
			:label-outside="false"
			:label="t('polls', 'Edit Email Address')"
			@update:value="validateEmailAddress"
			@submit="submitEmailAddress">
			<template #icon>
				<EditEmailIcon />
			</template>
			{{ t('polls', 'Edit Email Address') }}
		</NcActionInput>
		<NcActionInput v-if="share.type === 'external' && permissions.vote"
			v-bind="userName.inputProps"
			:value.sync="userName.inputValue"
			:label-outside="false"
			:label="t('polls', 'Change name')"
			@update:value="validateDisplayName"
			@submit="submitDisplayName">
			<template #icon>
				<EditAccountIcon />
			</template>
			{{ t('polls', 'Change name') }}
		</NcActionInput>
		<NcActionButton v-if="$route.name === 'publicVote'"
			:name="t('polls', 'Get your personal link per mail')"
			:aria-label="t('polls', 'Get your personal link per mail')"
			:disabled="!emailAddress"
			:value="emailAddress"
			@click="resendInvitation()">
			<template #icon>
				<SendLinkPerEmailIcon />
			</template>
		</NcActionButton>
		<NcActionCheckbox :checked="subscribed"
			:disabled="!permissions.subscribe"
			title="check"
			@change="toggleSubscription">
			{{ t('polls', 'Subscribe to notifications') }}
		</NcActionCheckbox>
		<NcActionButton v-if="share.type === 'external' && emailAddress"
			:name="t('polls', 'Remove Email Address')"
			:aria-label="t('polls', 'Remove Email Address')"
			:disabled="!emailAddress"
			@click="deleteEmailAddress">
			<template #icon>
				<DeleteIcon />
			</template>
		</NcActionButton>
		<NcActionButton v-if="permissions.edit"
			:name="t('polls', 'Copy list of email addresses to clipboard')"
			:aria-label="t('polls', 'Copy list of email addresses to clipboard')"
			@click="getAddresses()">
			<template #icon>
				<ClippyIcon />
			</template>
		</NcActionButton>
		<NcActionButton v-if="permissions.vote"
			:name="t('polls', 'Reset your votes')"
			:aria-label="t('polls', 'Reset your votes')"
			@click="resetVotes()">
			<template #icon>
				<ResetVotesIcon />
			</template>
		</NcActionButton>
		<NcActionButton v-if="$route.name === 'publicVote' && hasCookie"
			:name="t('polls', 'Logout as {name} (delete cookie)', { name: displayName })"
			:aria-label="t('polls', 'Logout as {name} (delete cookie)', { name: displayName })"
			@click="logout()">
			<template #icon>
				<LogoutIcon />
			</template>
		</NcActionButton>
	</NcActions>
</template>

<script>
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcActionCheckbox, NcActionInput, NcActionSeparator } from '@nextcloud/vue'
import { mapState } from 'vuex'
import SettingsIcon from 'vue-material-design-icons/Cog.vue'
import EditAccountIcon from 'vue-material-design-icons/AccountEdit.vue'
import EditEmailIcon from 'vue-material-design-icons/EmailEditOutline.vue'
import SendLinkPerEmailIcon from 'vue-material-design-icons/LinkVariant.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import ResetVotesIcon from 'vue-material-design-icons/Undo.vue'
import LogoutIcon from 'vue-material-design-icons/Logout.vue'
import { deleteCookieByValue, findCookieByValue } from '../../helpers/index.js'
import { ValidatorAPI, PollsAPI } from '../../Api/index.js'

const setError = (inputProps) => {
	inputProps.success = false
	inputProps.error = true
	inputProps.showTrailingButton = false
}

const setSuccess = (inputProps) => {
	inputProps.success = true
	inputProps.error = false
	inputProps.showTrailingButton = true
}
const setNeutral = (inputProps) => {
	inputProps.success = false
	inputProps.error = false
	inputProps.showTrailingButton = false
}

export default {
	name: 'UserMenu',

	components: {
		NcActions,
		NcActionButton,
		NcActionCheckbox,
		NcActionInput,
		NcActionSeparator,
		SettingsIcon,
		EditAccountIcon,
		EditEmailIcon,
		LogoutIcon,
		SendLinkPerEmailIcon,
		DeleteIcon,
		ClippyIcon,
		ResetVotesIcon,
	},

	data() {
		return {
			userEmail: {
				inputValue: '',
				inputProps: {
					success: false,
					error: false,
					showTrailingButton: true,
					labelOutside: false,
					label: t('polls', 'Edit Email Address'),
				},
			},
			userName: {
				inputValue: '',
				inputProps: {
					success: false,
					error: false,
					showTrailingButton: true,
					labelOutside: false,
					label: t('polls', 'Change name'),
				},
			},
		}
	},

	computed: {
		...mapState({
			permissions: (state) => state.poll.permissions,
			share: (state) => state.share,
			subscribed: (state) => state.subscription.subscribed,
			emailAddress: (state) => state.share.user.emailAddress,
			displayName: (state) => state.acl.currentUser.displayName,
		}),

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

	watch: {
		emailAddress() {
			this.userEmail.inputValue = this.emailAddress
		},
		displayName() {
			this.userName.inputValue = this.displayName
		},
	},

	created() {
		this.userEmail.inputValue = this.emailAddress
		this.userName.inputValue = this.displayName
	},

	methods: {
		logout() {
			const reRouteTo = deleteCookieByValue(this.$route.params.token)
			if (reRouteTo) {
				this.$router.push({ name: 'publicVote', params: { token: reRouteTo } })
			}
		},

		async toggleSubscription() {
			await this.$store.dispatch('subscription/update', !this.subscribed)
		},

		async deleteEmailAddress() {
			try {
				await this.$store.dispatch('share/deleteEmailAddress')
				showSuccess(t('polls', 'Email address deleted.'))
			} catch {
				showError(t('polls', 'Error deleting email address {emailAddress}', { emailAddress: this.userEmail.inputValue }))
			}
		},

		validateEmailAddress: debounce(async function() {
			const inputProps = this.userEmail.inputProps

			if (this.userEmail.inputValue === this.emailAddress) {
				setNeutral(inputProps)
				return
			}

			try {
				await ValidatorAPI.validateEmailAddress(this.userEmail.inputValue)
				setSuccess(inputProps)
			} catch {
				setError(inputProps)
			}
		}, 500),

		validateDisplayName: debounce(async function() {
			const inputProps = this.userName.inputProps
			if (this.userName.inputValue.length < 1) {
				setError(inputProps)
				return
			}

			if (this.userName.inputValue === this.displayName) {
				setNeutral(inputProps)
				return
			}

			try {
				await ValidatorAPI.validateName(this.$route.params.token, this.userName.inputValue)
				setSuccess(inputProps)
			} catch {
				setError(inputProps)
			}
		}, 500),

		async submitEmailAddress() {
			try {
				await this.$store.dispatch('share/updateEmailAddress', { emailAddress: this.userEmail.inputValue })
				showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: this.userEmail.inputValue }))
				setNeutral(this.userEmail.inputProps)
			} catch {
				showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: this.userEmail.inputValue }))
				setError(this.userEmail.inputProps)
			}
		},

		async submitDisplayName() {
			try {
				await this.$store.dispatch('share/updateDisplayName', { displayName: this.userName.inputValue })
				setNeutral(this.userName.inputProps)
				showSuccess(t('polls', 'Name changed.'))
			} catch {
				showError(t('polls', 'Error changing name.'))
				setError(this.userName.inputProps)
			}
		},

		async resendInvitation() {
			try {
				const response = await this.$store.dispatch('share/resendInvitation')
				showSuccess(t('polls', 'Invitation resent to {emailAddress}', { emailAddress: response.data.share.user.emailAddress }))
			} catch {
				showError(t('polls', 'Mail could not be resent to {emailAddress}', { emailAddress: this.share.user.emailAddress }))
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
				await this.$store.dispatch('votes/resetVotes')
				showSuccess(t('polls', 'Your votes are reset'))
			} catch {
				showError(t('polls', 'Error while resetting votes'))
			}
		},
	},
}
</script>
