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
	<Modal v-show="modal" :can-close="false">
		<div class="modal__content">
			<h2>{{ t('polls', 'Public poll') }}</h2>

			<div v-show="card === 'front'" class="front-card">
				<h3> {{ t('polls', 'Are you a user of this site and have a login?') }} </h3>
				<ButtonDiv :title="t('polls', 'Yes, I have a login')" @click="login()" />
				<ButtonDiv :title="t('polls', 'No, I have no login')" @click="card = 'back'" />
			</div>

			<div v-show="card === 'back'" class="back-card">
				<div class="section__username">
					<h3>{{ t('polls', 'To participate, tell us how we can call you!') }}</h3>
					<input ref="userName" v-model="userName" :class="userNameCheckStatus"
						type="text" :placeholder="t('polls', 'Enter your name')" @keyup.enter="submitRegistration">
					<div>
						{{ userNameCheckResult }}
					</div>
				</div>

				<div class="section__email">
					<h3>{{ t("polls", "With your email address you can subscribe to notifications and you will receive your personal link to this poll.") }}</h3>
					<input v-model="emailAddress" :class="emailAddressCheckStatus"
						type="text" :placeholder="t('polls', 'Optional email address')" @keyup.enter="submitRegistration">
					<div>
						{{ emailAddressCheckResult }}
					</div>
				</div>

				<div class="modal__buttons">
					<Actions>
						<ActionButton icon="icon-close" @click="card = 'front'">
							{{ t('polls', 'Back') }}
						</ActionButton>
					</Actions>
					<div class="modal__buttons__spacer" />
					<ButtonDiv :title="t('polls', 'Cancel')" @click="closeModal" />
					<ButtonDiv :primary="true" :disabled="disableSubmit" :title="t('polls', 'OK')"
						@click="submitRegistration" />
				</div>
			</div>
		</div>
	</Modal>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
import ButtonDiv from '../Base/ButtonDiv'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { Modal, Actions, ActionButton } from '@nextcloud/vue'
import { mapState } from 'vuex'

export default {
	name: 'PublicRegisterModal',

	components: {
		Actions,
		ActionButton,
		Modal,
		ButtonDiv,
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
			card: 'front',
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			share: state => state.poll.share,
		}),

		disableSubmit() {
			return !this.isValidName || this.checkingUserName
		},

		loginLink() {
			const redirectUrl = this.$router.resolve({
				name: 'publicVote',
				params: { token: this.$route.params.token },
			}).href
			return generateUrl('login?redirect_url=' + redirectUrl)
		},

		userNameCheckStatus() {
			if (this.checkingUserName) {
				return 'checking'
			} else {
				if (this.userName.length === 0) {
					return 'empty'
				} else if (this.userName.length < 3 || !this.isValidName) {
					return 'error'
				} else {
					return 'success'
				}
			}
		},

		userNameCheckResult() {
			if (this.checkingUserName) {
				return t('polls', 'Checking username …')
			} else {
				if (this.userName.length < 3) {
					return t('polls', 'Please use at least 3 characters.')
				} else if (!this.isValidName) {
					return t('polls', 'This name is not valid.')
				} else {
					return t('polls', 'OK, we will call you {username}.', { username: this.userName })
				}
			}
		},

		emailAddressCheckResult() {
			if (this.checkingEmailAddress) {
				return t('polls', 'Checking email address …')
			} else {
				if (this.emailAddress.length < 1) {
					return t('polls', ' ')
				} else if (!this.isValidEmailAddress) {
					return t('polls', 'This email address is not valid.')
				} else {
					return t('polls', 'This email address is valid.')
				}
			}
		},

		emailAddressCheckStatus() {
			if (this.checkingEmailAddress) {
				return 'checking'
			} else {
				if (this.emailAddress.length === 0) {
					return ''
				} else if (!this.isValidEmailAddress) {
					return 'error'
				} else {
					return 'success'
				}
			}
		},

	},

	watch: {
		userName: function() {
			if (this.userName.length > 2) {
				this.checkingUserName = true
				if (this.userName !== this.share.userid) {
					this.validatePublicUsername()
				}
			} else {
				this.checkingUserName = false
				this.isValidName = false
			}
		},

		emailAddress: function() {
			if (this.emailAddress.length > 0) {
				this.checkingEmailAddress = true
				this.validateEmailAddress()
			} else {
				this.checkingEmailAddress = false
				this.isValidEmailAddress = false
			}
		},
	},

	mounted() {
		this.userName = this.share.userId
		this.emailAddress = this.share.userEmail
		this.setFocus()
	},

	methods: {
		setFocus() {
			this.$nextTick(() => {
				this.$refs.userName.focus()
			})
		},

		closeModal() {
			this.modal = false
		},

		login() {
			window.location.assign(window.location.protocol + '//' + window.location.host + this.loginLink)
		},

		validatePublicUsername: debounce(function() {
			if (this.userName.length > 2) {
				return axios.post(generateUrl('apps/polls/check/username'), { pollId: this.poll.id, userName: this.userName, token: this.$route.params.token })
					.then(() => {
						this.checkingUserName = false
						this.isValidName = true
					})
					.catch(() => {
						this.checkingUserName = false
						this.isValidName = false
					})
			} else {
				this.checkingUserName = false
				this.isValidName = false
			}
		}, 500),

		validateEmailAddress: debounce(function() {
			if (this.emailAddress.length > 0) {
				return axios.get(generateUrl('apps/polls/check/emailaddress').concat('/', this.emailAddress))
					.then(() => {
						this.isValidEmailAddress = true
						this.checkingEmailAddress = false
					})
					.catch(() => {
						this.isValidEmailAddress = false
						this.checkingEmailAddress = false
					})
			} else {
				this.isValidEmailAddress = false
				this.checkingEmailAddress = false
			}
		}, 500),

		submitRegistration() {
			if (this.isValidName && (this.isValidEmailAddress || this.emailAddress.length === 0)) {
				this.$store.dispatch('poll/shares/addPersonal', { token: this.$route.params.token, userName: this.userName, emailAddress: this.emailAddress })
					.then((response) => {
						if (this.$route.params.token === response.token) {
							this.$store.dispatch({ type: 'poll/get', pollId: this.$route.params.id, token: this.$route.params.token })
							this.closeModal()
						} else {
							this.redirecting = true
							this.$router.replace({ name: 'publicVote', params: { token: response.token } })
							this.closeModal()
						}
					})
					.catch(() => {
						showError(t('polls', 'Error saving username', 1, this.poll.title))
					})
			}
		},
	},
}
</script>

<style lang="scss" scoped>

	.front-card, .back-card {
		display: flex;
		flex-direction: column;
		> button {
			margin: 8px 0;
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
		border-top: 1px solid var(--color-border)
	}
</style>
