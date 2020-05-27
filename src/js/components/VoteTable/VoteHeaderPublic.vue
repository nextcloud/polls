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
	<div v-if="poll.id" class="vote__header">
		<div v-show="displayLink" class="vote__header__personal-link">
			{{ t('polls', 'Your personal link to this poll: %n', 1, personalLink) }}
			<a class="icon icon-clippy" @click="copyLink()" />
		</div>

		<Modal v-show="!isValidUser &!expired & modal" :can-close="false">
			<div class="modal__content">
				<h2>{{ t('polls', 'Enter your name!') }}</h2>
				<p>{{ t('polls', 'To participate, enter a username with at least 3 characters.') }}</p>

				<input ref="userName" v-model="userName" :class="{ error: (!isValidName && userName.length > 0), success: isValidName }"
					type="text"
					:placeholder="t('polls', 'Enter your name')" @keyup.enter="writeUserName">

				<div>
					<span v-show="checkingUserName" class="icon-loading-small">Checking username …</span>
					<span v-show="!checkingUserName && userName.length < 3" class="error">{{ t('polls', 'Username is not valid. Please enter at least 3 characters.') }}</span>
					<span v-show="!checkingUserName && userName.length > 2 && !isValidName" class="error">{{ t('polls', 'This username is not valid, i.e. because it is already in use.') }}</span>
					<span v-show="!checkingUserName && userName.length > 2 && isValidName" class="error">{{ t('polls', 'Valid username.') }}</span>
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
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { Modal } from '@nextcloud/vue'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteHeaderPublic',

	components: {
		Modal,
	},

	data() {
		return {
			userName: '',
			token: '',
			checkingUserName: false,
			redirecting: false,
			isValidName: false,
			newName: '',
			modal: true,
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			expired: 'poll/expired',
		}),

		loginLink() {
			const redirectUrl = this.$router.resolve({
				name: 'publicVote',
				params: { token: this.$route.params.token },
			}).href
			return generateUrl('login?redirect_url=' + redirectUrl)
		},

		personalLink() {
			return window.location.origin.concat(
				this.$router.resolve({
					name: 'publicVote',
					params: { token: this.$route.params.token },
				}).href
			)
		},

		displayLink() {
			return (this.acl.userId !== '' && this.acl.userId !== null && this.acl.foundByToken)
		},

		isValidUser() {
			return (this.acl.userId !== '' && this.acl.userId !== null)
		},

	},

	watch: {
		userName: function() {
			this.isValidName = false
			if (this.userName.length > 2) {
				this.checkingUserName = true
				this.isValidName = this.validatePublicUsername()
			} else {
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your username!')
				this.checkingUserName = false
			}
		},

		'poll.id': function(newValue) {
			this.setFocus()
		},
	},

	methods: {
		setFocus() {
			this.$nextTick(() => {
				this.$refs.userName.focus()
			})
		},

		showModal() {
			this.modal = true
		},
		closeModal() {
			this.modal = false
		},
		copyLink() {
			this.$copyText(this.personalLink).then(
				function() {
					OC.Notification.showTemporary(t('polls', 'Link copied to clipboard'), { type: 'success' })
				},
				function() {
					OC.Notification.showTemporary(t('polls', 'Error while copying link to clipboard'), { type: 'error' })
				}
			)
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
				this.$store.dispatch('poll/share/addPersonal', { token: this.$route.params.token, userName: this.userName })
					.then((response) => {
						if (this.$route.params.token === response.token) {
							this.$store.dispatch({ type: 'poll/load', pollId: this.$route.params.id, token: this.$route.params.token })
						} else {
							this.token = response.token
							this.redirecting = true
							this.$router.replace({ name: 'publicVote', params: { token: this.token } })
						}
					})
					.catch(() => {
						OC.Notification.showTemporary(t('polls', 'Error saving username', 1, this.poll.title), { type: 'error' })
					})
			}
		},
	},
}
</script>

<style lang="scss">
	.vote__header {
		margin: 8px 24px;
	}

	.vote__header__personal-link {
		display: flex;
		padding: 4px 12px;
		margin: 0 12px 0 24px;
		border: 2px solid var(--color-success);
		background-color: var(--color-background-success) !important;
		border-radius: var(--border-radius);
		font-size: 1.2em;
		opacity: 0.8;
		.icon {
			margin: 0 12px;
		}
	}
</style>
