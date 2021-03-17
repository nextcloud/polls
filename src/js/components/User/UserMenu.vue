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
	<Actions>
		<ActionButton v-if="$route.name === 'publicVote'" icon="icon-clippy" @click="copyLink()">
			{{ t('polls', 'Copy your personal link to clipboard') }}
		</ActionButton>
		<ActionSeparator />
		<ActionInput v-if="$route.name === 'publicVote'" icon="icon-edit" :class="check.status"
			:value="emailAddressTemp"
			@click="deleteEmailAddress"
			@update:value="validateEmailAddress"
			@submit="submitEmailAddress">
			{{ t('polls', 'edit Email Address') }}
		</ActionInput>
		<ActionButton v-if="$route.name === 'publicVote'" :disabled="!emailAddress"
			:value="emailAddress"
			icon="icon-share"
			@click="resendInvitation()">
			{{ t('polls', 'Get your personal link per mail') }}
		</ActionButton>
		<ActionCheckbox :checked="subscribed" :disabled="!acl.allowSubscribe" title="check"
			@change="switchSubscription">
			{{ t('polls', 'Subscribe to notifications') }}
		</ActionCheckbox>
		<ActionButton v-if="$route.name === 'publicVote' && emailAddress"
			:disabled="!emailAddress"
			icon="icon-delete"
			@click="deleteEmailAddress">
			{{ t('polls', 'remove Email Address') }}
		</ActionButton>
		<ActionButton v-if="acl.allowEdit" icon="icon-clippy" @click="getAddresses()">
			{{ t('polls', 'Copy list of email addresses to clipboard') }}
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

export default {
	name: 'UserMenu',

	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionSeparator,
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
			acl: state => state.poll.acl,
			share: state => state.share,
			subscribed: state => state.subscription.subscribed,
			emailAddress: state => state.share.emailAddress,
		}),

		emailAddressUnchanged() {
			return this.emailAddress === this.emailAddressTemp
		},

		check() {
			if (this.checking) {
				return {
					result: t('polls', 'Checking email address …'),
					status: 'checking',
				}
			} else if (this.emailAddressUnchanged) {
				return {
					result: '',
					status: '',
				}
			} else {
				return {
					result: this.checkResult,
					status: this.checkStatus,
				}
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
		emailAddress: function() {
			this.emailAddressTemp = this.emailAddress
		},
	},

	created() {
		this.emailAddressTemp = this.emailAddress
	},

	methods: {
		async switchSubscription() {
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
			this.emailAddressTemp = value
			try {
				this.checking = true
				await axios.get(generateUrl('apps/polls/check/emailaddress') + '/' + this.emailAddressTemp)
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
				await this.$copyText(response.data)
				showSuccess(t('polls', 'Link copied to clipboard'))
			} catch {
				showError(t('polls', 'Error while copying link to clipboard'))
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
