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
	<Actions primary>
		<template #icon>
			<SettingsIcon :size="20" decorative />
		</template>
		<ActionButton v-if="$route.name === 'publicVote'" icon="icon-md-link" @click="copyLink()">
			{{ t('polls', 'Copy your personal link to clipboard') }}
		</ActionButton>
		<ActionSeparator v-if="$route.name === 'publicVote'" />
		<ActionInput v-if="$route.name === 'publicVote'"
			:class="check.status"
			:value="emailAddressTemp"
			@click="deleteEmailAddress"
			@update:value="validateEmailAddress"
			@submit="submitEmailAddress">
			<template #icon>
				<EditEmailIcon />
			</template>
			{{ t('polls', 'Edit Email Address') }}
		</ActionInput>
		<ActionButton v-if="$route.name === 'publicVote'"
			:disabled="!emailAddress"
			:value="emailAddress"
			@click="resendInvitation()">
			<template #icon>
				<SendLinkPerEmailIcon />
			</template>
			{{ t('polls', 'Get your personal link per mail') }}
		</ActionButton>
		<ActionCheckbox :checked="subscribed"
			:disabled="!acl.allowSubscribe"
			title="check"
			@change="toggleSubscription">
			{{ t('polls', 'Subscribe to notifications') }}
		</ActionCheckbox>
		<ActionButton v-if="$route.name === 'publicVote' && emailAddress"
			:disabled="!emailAddress"
			@click="deleteEmailAddress">
			<template #icon>
				<DeleteIcon />
			</template>
			{{ t('polls', 'Remove Email Address') }}
		</ActionButton>
		<ActionButton v-if="acl.allowEdit" @click="getAddresses()">
			<template #icon>
				<ClippyIcon />
			</template>
			{{ t('polls', 'Copy list of email addresses to clipboard') }}
		</ActionButton>
		<ActionButton @click="resetVotes()">
			<template #icon>
				<ResetVotesIcon />
			</template>
			{{ t('polls', 'Reset your votes') }}
		</ActionButton>
		<ActionButton v-if="$route.name === 'publicVote' && hasCookie" @click="logout()">
			<template #icon>
				<LogoutIcon />
			</template>
			{{ t('polls', 'Logout as {name} (delete cookie)', { name: acl.displayName }) }}
		</ActionButton>
	</Actions>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { Actions, ActionButton, ActionCheckbox, ActionInput, ActionSeparator } from '@nextcloud/vue'
import { mapState } from 'vuex'
import SettingsIcon from 'vue-material-design-icons/Cog.vue'
import EditEmailIcon from 'vue-material-design-icons/EmailEditOutline.vue'
import SendLinkPerEmailIcon from 'vue-material-design-icons/LinkVariant.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import ResetVotesIcon from 'vue-material-design-icons/Undo.vue'
import LogoutIcon from 'vue-material-design-icons/Logout.vue'
import { deleteCookieByValue, findCookieByValue } from '../../helpers/cookieHelper.js'

export default {
	name: 'UserMenu',

	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionSeparator,
		SettingsIcon,
		EditEmailIcon,
		LogoutIcon,
		SendLinkPerEmailIcon,
		DeleteIcon,
		ClippyIcon,
		ResetVotesIcon,
	},

	data() {
		return {
			emailAddressTemp: '',
			checkResult: '',
			checkStatus: '',
			checking: false,
		}
	},

	computed: {
		...mapState({
			acl: (state) => state.poll.acl,
			share: (state) => state.share,
			subscribed: (state) => state.subscription.subscribed,
			emailAddress: (state) => state.share.emailAddress,
		}),

		hasCookie() {
			return !!findCookieByValue(this.$route.params.token)
		},

		emailAddressUnchanged() {
			return this.emailAddress === this.emailAddressTemp
		},

		check() {
			if (this.checking) {
				return {
					result: t('polls', 'Checking email address …'),
					status: 'checking',
				}
			}

			if (this.emailAddressUnchanged) {
				return {
					result: '',
					status: '',
				}
			}

			return {
				result: this.checkResult,
				status: this.checkStatus,
			}
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
			this.emailAddressTemp = this.emailAddress
		},
	},

	created() {
		this.emailAddressTemp = this.emailAddress
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
				showError(t('polls', 'Error deleting email address {emailAddress}', { emailAddress: this.emailAddressTemp }))
			}
		},

		validateEmailAddress: debounce(async function(value) {
			const endPoint = `apps/polls/check/emailaddress/${this.emailAddressTemp}`

			this.emailAddressTemp = value
			try {
				this.checking = true
				await axios.get(generateUrl(endPoint), {
					headers: { Accept: 'application/json' },
				})
				this.checkResult = t('polls', 'valid email address.')
				this.checkStatus = 'success'
			} catch {
				this.checkResult = t('polls', 'Invalid email address.')
				this.checkStatus = 'error'
			} finally {
				this.checking = false
			}
		}, 500),

		async submitEmailAddress() {
			try {
				await this.$store.dispatch('share/updateEmailAddress', { emailAddress: this.emailAddressTemp })
				showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: this.emailAddressTemp }))
			} catch {
				showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: this.emailAddressTemp }))
			}
		},

		async resendInvitation() {
			try {
				const response = await this.$store.dispatch('share/resendInvitation')
				showSuccess(t('polls', 'Invitation resent to {emailAddress}', { emailAddress: response.data.share.emailAddress }))
			} catch {
				showError(t('polls', 'Mail could not be resent to {emailAddress}', { emailAddress: this.share.emailAddress }))
			}
		},

		async copyLink() {
			try {
				await this.$copyText(this.personalLink)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
			}
		},

		async getAddresses() {
			try {
				const response = await this.$store.dispatch('poll/getParticipantsEmailAddresses')
				await this.$copyText(response.data.map((item) => item.combined))
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
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

<style lang="scss">
	.action {
		input.action-input__input {
			background-repeat: no-repeat;
			background-position: right 12px center;
			&:empty:before {
				color: grey;
			}
		}

		&.error {
			input.action-input__input, label.action-input__label {
				border-color: var(--color-error);
				background-color: var(--color-background-error);
				color: var(--color-foreground-error);
			}
			input.action-input__input {
				background-image: var(--icon-polls-no);
			}
			label.action-input__label {
				cursor: not-allowed;
			}
		}

		&.checking input.action-input__input {
			border-color: var(--color-warning);
			background-image: var(--icon-polls-loading);
		}

		&.success {
			input.action-input__input, label.action-input__label {
				border-color: var(--color-success);
				background-color: var(--color-background-success) !important;
				color: var(--color-foreground-success);
			}
			input.action-input__input {
				background-image: var(--icon-polls-yes);
			}
		}

		&.success input.action-input__input , &.icon-confirm.success input.action-input__input  {
			border-color: var(--color-success);
			background-image: var(--icon-polls-yes);
			background-color: var(--color-background-success) !important;
			color: var(--color-foreground-success);
		}

	}

</style>
