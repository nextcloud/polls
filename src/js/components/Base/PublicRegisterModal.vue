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
			<div class="enter__name">
				<h2>{{ t('polls', 'Who are you?') }}</h2>
				<p>{{ t('polls', 'To participate, tell us how we can call you!') }}</p>

				<input ref="userName" v-model="userName" :class="{ error: (!isValidName && userName.length > 0), success: isValidName }"
					type="text"
					:placeholder="t('polls', 'Enter your name')" @keyup.enter="writeUserName">

				<div>
					<span v-show="checkingUserName" class="icon-loading-small">Checking username …</span>
					<span v-show="!checkingUserName && userName.length < 3" class="error">{{ t('polls', 'Please use at least 3 characters for your name.') }}</span>
					<span v-show="!checkingUserName && userName.length > 2 && !isValidName" class="error">{{ t('polls', 'This name is not valid, i.e. because it is already in use.') }}</span>
					<span v-show="!checkingUserName && userName.length > 2 && isValidName" class="error">{{ t('polls', 'OK, we will call you {username}.', {username : userName }) }}</span>
				</div>
			</div>
			<div class="enter__email">
				<p>{{ t('polls', 'Enter your email address to be able to subscribe to updates and get your personal link via email.') }}</p>

				<input v-model="emailAddress" :class="{ error: (!isValidName && userName.length > 0), success: isValidName }"
					type="text"
					:placeholder="t('polls', 'Enter your email address')" @keyup.enter="writeUserName">
			</div>

			<div class="modal__buttons">
				<a :href="loginLink" class="modal__buttons__link"> {{ t('polls', 'You have an account? Log in here.') }} </a>
				<div class="modal__buttons__spacer" />
				<ButtonDiv :title="t('polls', 'Cancel')"
					@click="closeModal" />
				<ButtonDiv :primary="true" :disabled="!isValidName || checkingUserName" :title="t('polls', 'OK')"
					@click="writeUserName" />
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
import { Modal } from '@nextcloud/vue'
import { mapState } from 'vuex'

export default {
	name: 'PublicRegisterModal',

	components: {
		Modal,
		ButtonDiv,
	},

	data() {
		return {
			userName: '',
			emailAddress: '',
			checkingUserName: false,
			redirecting: false,
			isValidName: false,
			modal: true,
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			share: state => state.poll.share,
		}),

		loginLink() {
			const redirectUrl = this.$router.resolve({
				name: 'publicVote',
				params: { token: this.$route.params.token },
			}).href
			return generateUrl('login?redirect_url=' + redirectUrl)
		},
	},

	watch: {
		userName: function() {
			this.isValidName = false
			if (this.userName.length > 2) {
				this.checkingUserName = true
				if (this.userName !== this.share.userid) {
					this.isValidName = this.validatePublicUsername()
				}
			} else {
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your username!')
				this.checkingUserName = false
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

		validatePublicUsername:	debounce(function() {
			if (this.userName.length > 2) {
				this.checkingUserName = true
				return axios.post(generateUrl('apps/polls/check/username'), { pollId: this.poll.id, userName: this.userName, token: this.$route.params.token })
					.then(() => {
						this.checkingUserName = false
						this.isValidName = true
						this.invalidUserNameMessage = 'Username is OK.'
						return true
					})
					.catch(() => {
						this.checkingUserName = false
						this.isValidName = false
						this.invalidUserNameMessage = t('polls', 'This username can not be chosen.')
						return false
					})
			} else {
				this.checkingUserName = false
				this.isValidName = false
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your username!')
				return false
			}
		}, 500),

		writeUserName() {
			if (this.isValidName) {
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

<style lang="scss">

	.modal__content {
		.enter__name, .enter__email {
			margin-bottom: 12px;
		}
	}

</style>
