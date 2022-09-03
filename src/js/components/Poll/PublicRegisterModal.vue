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

<template lang="html">
	<NcModal v-show="modal" :size="modalSize" :can-close="false">
		<div class="modal__content">
			<div class="modal__registration">
				<div class="registration__registration">
					<h2>{{ t('polls', 'Public participation') }}</h2>
					<InputDiv v-model="userName"
						v-tooltip="userNameCheck.result"
						class="section__username"
						:label="t('polls', 'To participate, tell us how we can call you!')"
						:signaling-class="userNameCheck.status"
						:placeholder="t('polls', 'Enter your name')"
						:helper-text="userNameCheck.result"
						focus
						@submit="submitRegistration" />

					<NcCheckboxRadioSwitch v-if="share.type === 'public'" :checked.sync="saveCookie">
						{{ t('polls', 'Remember me for 30 days') }}
					</NcCheckboxRadioSwitch>

					<InputDiv v-if="share.publicPollEmail !== 'disabled'"
						v-model="emailAddress"
						v-tooltip="emailCheck.result"
						class="section__email"
						:label="emailLabel"
						:signaling-class="emailCheck.status"
						:placeholder="t('polls', share.publicPollEmail === 'mandatory' ? 'Mandatory email address' : 'Optional email address')"
						:helper-text="emailCheck.result"
						type="email"
						inputmode="email"
						@submit="submitRegistration" />

					<div v-if="privacyUrl" class="section__optin">
						<RichText :text="privacyRich.subject" :arguments="privacyRich.parameters" />
					</div>

					<div class="modal__buttons">
						<NcButton @click="closeModal">
							{{ t('polls', 'Cancel') }}
						</NcButton>

						<NcButton type="primary" :disabled="disableSubmit" @click="submitRegistration()">
							{{ t('polls', 'OK') }}
						</NcButton>
					</div>
				</div>

				<div v-if="share.showLogin" class="registration__login">
					<h2> {{ t('polls', 'You are a registered user of this site?') }} </h2>
					<NcButton wide @click="login()">
						{{ t('polls', 'Login') }}
					</NcButton>
					<div>
						{{ t('polls', 'As a regular user of this site, you can participate with your internal identity after logging in.') }}
					</div>
					<div>
						{{ t('polls', 'Otherwise participate publicly.') }}
					</div>
				</div>
			</div>
			<div class="legal_links">
				<SimpleLink v-if="imprintUrl"
					:href="imprintUrl"
					target="_blank"
					:name="t('polls', 'Legal Notice')" />
			</div>
		</div>
	</NcModal>
</template>

<script>
import { debounce } from 'lodash'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcModal, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { mapState } from 'vuex'
import { RichText } from '@nextcloud/vue-richtext'
import InputDiv from '../Base/InputDiv.vue'
import SimpleLink from '../../helpers/SimpleLink.js'

export default {
	name: 'PublicRegisterModal',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
		NcModal,
		RichText,
		SimpleLink,
		NcButton,
	},

	data() {
		return {
			userName: '',
			emailAddress: '',
			checkingUserName: false,
			checkingEmailAddress: false,
			redirecting: false,
			isValidName: false,
			isValidEmailAddress: false,
			modal: true,
			modalSize: 'large',
			saveCookie: true,
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
			share: (state) => state.share,
			privacyUrl: (state) => state.appSettings.usePrivacyUrl,
			imprintUrl: (state) => state.appSettings.useImprintUrl,
		}),

		emailLabel() {
			if (this.share.publicPollEmail === 'mandatory') {
				return t('polls', 'Your email address is required. After the registration your personal link to the poll will be sent to this address.')
			}
			return t('polls', 'With your email address you can subscribe to notifications and you will receive your personal link to this poll.')
		},

		registrationIsValid() {
			return this.isValidName && (this.isValidEmailAddress || (this.emailAddress.length === 0 && this.share.publicPollEmail !== 'mandatory'))
		},

		disableSubmit() {
			return !this.registrationIsValid || this.checkingUserName
		},

		privacyRich() {
			const subject = t('polls', 'By clicking the "OK" button you accept our {privacyPolicy}.')
			const parameters = {
				privacyPolicy: {
					component: SimpleLink,
					props: {
						href: this.privacyUrl,
						name: t('polls', 'privacy policy'),
						target: '_blank',
					},
				},
			}
			return { subject, parameters }
		},

		loginLink() {
			const redirectUrl = this.$router.resolve({
				name: 'publicVote',
				params: { token: this.$route.params.token },
			}).href
			return generateUrl(`login?redirect_url=${redirectUrl}`)
		},

		userNameCheck() {
			if (this.checkingUserName) {
				return {
					result: t('polls', 'Checking name …'),
					status: 'checking',
				}
			}

			if (this.userName.length < 1) {
				return {
					result: t('polls', 'A name is required.'),
					status: 'empty',
				}
			}

			if (!this.isValidName) {
				return {
					result: t('polls', 'The name {username} is invalid or reserved.', { username: this.userName }),
					status: 'error',
				}
			}

			return {
				result: '',
				status: 'success',
			}
		},

		emailCheck() {
			if (this.checkingEmailAddress) {
				return {
					result: t('polls', 'Checking email address …'),
					status: 'checking',
				}
			}

			if (this.emailAddress.length < 1) {
				if (this.share.publicPollEmail === 'mandatory') {
					return {
						result: t('polls', 'An email address is required.'),
						status: 'empty',
					}
				}
				return {
					result: '',
					status: 'empty',
				}
			}

			if (!this.isValidEmailAddress) {
				return {
					result: t('polls', 'Invalid email address.'),
					status: 'error',
				}
			}

			return {
				result: '',
				status: 'success',
			}
		},

	},

	watch: {
		userName() {
			if (this.userName) {
				this.checkingUserName = true
				if (this.userName !== this.share.userid) {
					this.validatePublicUsername()
				}
			} else {
				this.checkingUserName = false
				this.isValidName = false
			}
		},

		emailAddress() {
			if (this.emailAddress) {
				this.checkingEmailAddress = true
				this.validateEmailAddress()
			} else {
				this.checkingEmailAddress = false
				this.isValidEmailAddress = false
			}
		},
	},

	mounted() {
		if (this.$route.name === 'publicVote' && this.$route.query.name) {
			this.userName = this.$route.query.name
		} else {
			this.userName = this.share.displayName
		}
		if (this.$route.name === 'publicVote' && this.$route.query.email) {
			this.emailAddress = this.$route.query.email
		} else {
			this.emailAddress = this.share.emailAddress
		}
	},

	methods: {
		closeModal() {
			this.modal = false
		},

		login() {
			window.location.assign(`${window.location.protocol}//${window.location.host}${this.loginLink}`)
		},

		validatePublicUsername: debounce(async function() {
			const endpoint = 'apps/polls/check/username'

			try {
				await axios.post(generateUrl(endpoint), {
					headers: { Accept: 'application/json' },
					userName: this.userName,
					token: this.$route.params.token,
				})
				this.isValidName = true
			} catch {
				this.isValidName = false
			}
			this.checkingUserName = false
		}, 500),

		validateEmailAddress: debounce(async function() {
			const endpoint = `apps/polls/check/emailaddress/${this.emailAddress}`

			try {
				await axios.get(generateUrl(endpoint), {
					headers: { Accept: 'application/json' },
				})
				this.isValidEmailAddress = true
			} catch {
				this.isValidEmailAddress = false
			}
			this.checkingEmailAddress = false
		}, 500),

		async submitRegistration() {
			if (this.registrationIsValid) {
				try {
					const response = await this.$store.dispatch('share/register', { userName: this.userName, emailAddress: this.emailAddress, saveCookie: this.saveCookie })

					if (this.$route.params.token === response.token) {
						this.$store.dispatch({ type: 'poll/get', pollId: this.$route.params.id, token: this.$route.params.token })
						this.closeModal()
					} else {
						this.redirecting = true
						this.$router.replace({ name: 'publicVote', params: { token: response.token } })
						this.closeModal()
					}
					if (this.share.emailAddress && !this.share.invitationSent) {
						showError(t('polls', 'Email could not be sent to {emailAddress}', { emailAddress: this.share.emailAddress }))
					}
				} catch {
					showError(t('polls', 'Error saving name'))
				}

			}
		},
	},
}
</script>

<style lang="scss">
	.section__optin {
		align-self: end;

		a {
			text-decoration: underline;
		}
	}

	.modal__registration {
		display: flex;
		flex-wrap: wrap;
		overflow: hidden;
		&>div {
			display: flex;
			flex-direction: column;
			flex: 1 auto;
			min-width: 140px;
			padding: 24px;
			border-top: 1px solid;
			border-right: 1px solid;
			margin-top: -2px;
			margin-right: -2px;
			> button {
				margin: 8px 0;
			}
		}

		.registration__login {
			flex: 1 180px;
		}
		.registration__registration {
			flex: 1 480px;

		}
	}

	[class*='section__'] {
		margin: 4px 0;
	}

	.modal__content {
		.enter__name, .enter__email {
			margin-bottom: 12px;
		}
	}

	.description {
		hyphens: auto;
		border-top: 1px solid var(--color-border);
	}

	.legal_links {
		padding: 4px 24px;

		a {
			color: var(--color-text-lighter);
			font-weight: bold;
			white-space: nowrap;
			padding: 10px;
			margin: -10px;

			&:hover, &:active {
				color: var(--color-text-light);
				&::after {
					color: var(--color-text-lighter);
				}
			}

			&:after {
			    content:"|";
				padding: 0 4px;
			}

			&:last-child:after {
			    content:"";
			}
		}
	}

	@media only screen and (max-width: 688px) {
		.modal__content {
			padding: 6px;
		}

		.modal__registration > div {
			padding: 12px;
		}
	}
</style>
