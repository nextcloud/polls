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
					<h2>{{ t('polls', 'Guest users') }}</h2>
					<InputDiv v-model="userName"
						class="section__username"
						:signaling-class="checkStatus.userName"
						:placeholder="t('polls', 'Enter your name or nickname')"
						:helper-text="userNameHint"
						focus
						@submit="submitRegistration" />

					<InputDiv v-if="share.publicPollEmail !== 'disabled'"
						v-model="emailAddress"
						class="section__email"
						:signaling-class="checkStatus.email"
						:placeholder="t('polls', share.publicPollEmail === 'mandatory' ? 'Mandatory email address' : 'Optional email address')"
						:helper-text="emailAddressHint"
						type="email"
						inputmode="email"
						@submit="submitRegistration" />

					<NcCheckboxRadioSwitch v-if="share.type === 'public'" :checked.sync="saveCookie">
						{{ t('polls', 'Remember me for 30 days') }}
					</NcCheckboxRadioSwitch>

					<div v-if="privacyUrl" class="section__optin">
						<NcRichText :text="privacyRich.subject" :arguments="privacyRich.parameters" />
					</div>

					<div class="modal__buttons">
						<div class="left">
							<div class="legal_links">
								<SimpleLink v-if="imprintUrl"
									:href="imprintUrl"
									target="_blank"
									:name="t('polls', 'Legal Notice')" />
							</div>
						</div>
						<div class="right">
							<NcButton @click="closeModal">
								<template #default>
									{{ t('polls', 'Cancel') }}
								</template>
							</NcButton>

							<NcButton type="primary" :disabled="disableSubmit" @click="submitRegistration()">
								<template #default>
									{{ t('polls', 'OK') }}
								</template>
							</NcButton>
						</div>
					</div>
				</div>

				<div v-if="share.showLogin" class="registration__login">
					<h2> {{ t('polls', 'Registered users') }} </h2>
					<NcButton wide @click="login()">
						<template #default>
							{{ t('polls', 'Login') }}
						</template>
					</NcButton>
					<div>
						{{ t('polls', 'As a regular user of this site, you can participate with your internal identity after logging in.') }}
					</div>
					<div>
						{{ t('polls', 'Otherwise participate as a guest user.') }}
					</div>
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { debounce } from 'lodash'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcModal, NcCheckboxRadioSwitch, NcRichText } from '@nextcloud/vue'
import { mapState } from 'vuex'
import InputDiv from '../Base/InputDiv.vue'
import SimpleLink from '../../helpers/SimpleLink.js'
import { ValidatorAPI } from '../../Api/validators.js'
import { PublicAPI } from '../../Api/public.js'
import { setCookie } from '../../helpers/cookieHelper.js'

const COOKIE_LIFETIME = 30

export default {
	name: 'PublicRegisterModal',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
		NcModal,
		NcRichText,
		SimpleLink,
		NcButton,
	},

	data() {
		return {
			checkStatus: {
				email: 'empty',
				userName: 'empty',
			},
			userName: '',
			emailAddress: '',
			redirecting: false,
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

		registrationIsValid() {
			return this.checkStatus.userName === 'valid' && (this.checkStatus.email === 'valid' || (this.emailAddress.length === 0 && this.share.publicPollEmail !== 'mandatory'))
		},

		disableSubmit() {
			return !this.registrationIsValid || this.checkStatus.userName === 'checking'
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

			return `${generateUrl('/login')}?redirect_url=${redirectUrl}`

			// TODO: broken?
			// return generateUrl('/login', { redirect_url: redirectUrl })
		},

		userNameHint() {
			if (this.checkStatus.userName === 'checking') return t('polls', 'Checking name …')
			if (this.checkStatus.userName === 'empty') return t('polls', 'A name is required.')
			if (this.checkStatus.userName === 'invalid') return t('polls', 'The name {username} is invalid or reserved.', { username: this.userName })
			return ''
		},

		emailGeneratedStatus() {
			return this.checkStatus.email === 'empty' ? this.share.publicPollEmail : this.checkStatus.email
		},

		emailAddressHint() {
			if (this.emailGeneratedStatus === 'checking') return t('polls', 'Checking email address …')
			if (this.emailGeneratedStatus === 'mandatory') return t('polls', 'An email address is required.')
			if (this.emailGeneratedStatus === 'invalid') return t('polls', 'Invalid email address.')
			if (this.share.type === 'public') {
				if (this.emailGeneratedStatus === 'valid') return t('polls', 'You will receive your personal link after clicking "OK".')
				return t('polls', 'Enter your email address to get your personal access link.')
			}
			return ''
		},
	},

	watch: {
		userName() {
			this.validatePublicUsername()
		},

		emailAddress() {
			this.validateEmailAddress()
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
			if (this.userName.length < 1) {
				this.checkStatus.userName = 'empty'
				return
			}

			this.checkStatus.userName = 'checking'

			try {
				await ValidatorAPI.validateName(this.$route.params.token, this.userName)
				this.checkStatus.userName = 'valid'
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				if (e?.code === 'ERR_BAD_REQUEST') {
					this.checkStatus.userName = 'invalid'
					return
				}
				throw e
			}
		}, 500),

		validateEmailAddress: debounce(async function() {
			if (this.emailAddress.length < 1) {
				this.checkStatus.email = 'empty'
				return
			}

			this.checkStatus.email = 'checking'
			try {
				await ValidatorAPI.validateEmailAddress(this.emailAddress)
				this.checkStatus.email = 'valid'
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				if (e?.code === 'ERR_BAD_REQUEST') {
					this.checkStatus.email = 'invalid'
					return
				}
				throw e
			}
		}, 500),

		updateCookie(value) {
			const cookieExpiration = (COOKIE_LIFETIME * 24 * 60 * 1000)
			setCookie(this.$route.params.token, value, cookieExpiration)
		},

		routeToPersonalShare(token) {
			if (this.$route.params.token === token) {
				// if share was not a public share, but a personal share
				// (i.e. email shares allow to change personal data by fist entering of the poll),
				// just load the poll
				this.$store.dispatch({ type: 'poll/get' })
				this.closeModal()
			} else {
				// in case of a public share, redirect to the generated share
				this.redirecting = true
				this.$router.replace({ name: 'publicVote', params: { token } })
				this.closeModal()
			}

		},

		async submitRegistration() {
			if (!this.registrationIsValid) {
				return
			}

			try {
				const response = await PublicAPI.register(
					this.$route.params.token,
					this.userName,
					this.emailAddress,
				)

				if (this.saveCookie && this.$route.params.type === 'public') {
					this.updateCookie(response.data.share.token)
				}

				this.routeToPersonalShare(response.data.share.token)

				// TODO: Is that correct, is this possible in any way?
				if (this.share.emailAddress && !this.share.invitationSent) {
					showError(t('polls', 'Email could not be sent to {emailAddress}', { emailAddress: this.share.emailAddress }))
				}
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				showError(t('polls', 'Error registering to poll', { error: e.response }))
				throw e
			}
		},
	},
}
</script>

<style lang="scss">
	.section__optin {
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
		a {
			color: var(--color-text-maxcontrast);
			font-weight: bold;
			white-space: nowrap;
			padding: 10px;
			margin: -10px;

			&:hover, &:active {
				color: var(--color-main-text);
				&::after {
					color: var(--color-text-maxcontrast);
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
