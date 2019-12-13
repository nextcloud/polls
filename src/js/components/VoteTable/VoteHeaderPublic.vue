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
	<div class="voteHeader">
		<div v-if="!isValidUser" class="getUsername">
			<!-- <label>
				{{ t('polls', 'Enter a valid username, to participate in this poll.') }}
			</label> -->

			<form v-if="!redirecting">
				<input v-model="userName" :class="{ error: (!isValidName && userName.length > 0), success: isValidName }" type="text"
					:placeholder="t('polls', 'Enter a valid username with at least 3 Characters')">
				<input v-show="isValidName && !checkingUserName" class="icon-confirm" :class="{ error: !isValidName, success: isValidName }"
					@click="writeUserName">
				<span v-show="checkingUserName" class="icon-loading-small" />
				<!-- <span v-if="!isValidName" class="error"> {{ invalidUserNameMessage }} </span> -->
			</form>
			<div v-else>
				<span>{{ t('polls', 'You will be redirected to your personal share.') }}</span>
				<span>
					{{ t('polls', 'If you are not redirected to your poll click this link:') }}
					<router-link :to="{ name: 'publicVote', params: { token: token }}">
						Link
					</router-link>
				</span>
			</div>
		</div>
		<div v-if="displayLink" class="personal-link">
			{{ t('polls', 'Your personal link to this poll: %n', 1, personalLink) }}
			<a class="icon icon-clippy" @click="copyLink( { url: OC.generateUrl($route.path) } )" />
		</div>
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
import { mapState } from 'vuex'

export default {
	name: 'VoteHeaderPublic',

	data() {
		return {
			userName: '',
			token: '',
			checkingUserName: false,
			redirecting: false,
			isValidName: false,
			newName: ''
		}
	},

	computed: {
		...mapState({
			event: state => state.event,
			acl: state => state.acl
		}),

		personalLink() {
			return location.protocol.concat('//', window.location.hostname, OC.generateUrl(this.$route.path))
		},

		displayLink() {
			return (this.acl.userId !== '' && this.acl.userId !== null && this.acl.foundByToken)
		},

		isValidUser() {
			return (this.acl.userId !== '' && this.acl.userId !== null)
		}

	},

	watch: {
		userName: function() {
			if (this.userName.length > 2) {
				this.isValidName = this.validatePublicUsername()
			} else {
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your user name!')
				this.isValidName = false
			}
		}
	},

	methods: {
		copyLink(payload) {
			this.$copyText(window.location.origin + payload.url).then(
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Link copied to clipboard'), { type: 'success' })
				},
				function(e) {
					OC.Notification.showTemporary(t('polls', 'Error while copying link to clipboard'), { type: 'error' })
				}
			)
		},
		validatePublicUsername:	debounce(function() {
			if (this.userName.length > 2) {
				this.checkingUserName = true
				return axios.post(OC.generateUrl('apps/polls/check/username'), { pollId: this.event.id, userName: this.userName, token: this.$route.params.token })
					.then((response) => {
						this.checkingUserName = false
						this.isValidName = true
						this.invalidUserNameMessage = 'User name is OK.'
						return true
					})
					.catch(() => {
						this.checkingUserName = false
						this.isValidName = false
						this.invalidUserNameMessage = t('polls', 'This user name can not be chosen.')
						return false
					})
			} else {
				this.checkingUserName = false
				this.isValidName = false
				this.invalidUserNameMessage = t('polls', 'Please use at least 3 characters for your user name!')
				return false
			}
		}, 500),

		writeUserName() {
			if (this.validatePublicUsername()) {
				this.$store.dispatch('addShareFromUser', { token: this.$route.params.token, userName: this.userName })
					.then((response) => {
						this.token = response.token
						this.redirecting = true
						this.$router.replace({ name: 'publicVote', params: { 'token': response.token } })
					})
					.catch(() => {
						OC.Notification.showTemporary(t('polls', 'Error saving user name"', 1, event.title), { type: 'error' })
					})
			}
		}
	}
}
</script>

<style lang="scss" scoped>
	.voteHeader {
		margin: 8px 24px;
	}

	.personal-link {
		display: flex;
		padding: 4px 12px;
		margin: 0 12px 0 24px;
		border: 2px solid var(--color-success);
		background-color: #d6fdda !important;
		border-radius: var(--border-radius);
		font-size: 1.2em;
		opacity: 0.8;
		.icon {
			margin: 0 12px;
		}
	}

	.getUsername {
		& > label {
			margin-right: 12px;
		}

		margin: 0 12px 12px 24px;
		border:2px solid var(--color-border-dark);
		font-size: 1.2em;
		padding: 0 12px 0 12px;
		display: flex;
		align-items: center;
		border-radius: var(--border-radius);
		background-color: var(--color-background-dark);
		flex-wrap: wrap;

		form, div {
			flex: 1;
			display: flex;

		}
		input {
			flex: 1;
		}

		.icon-loading-small {
			position: relative;
			right: 24px;
			top: 0px;
		}

		input[type="text"] + .icon-confirm, input[type="text"] + .icon-loading-small {
			flex: 0;
			margin-left: -8px !important;
			border-left-color: transparent !important;
			border-radius: 0 var(--border-radius) var(--border-radius) 0 !important;
			background-clip: padding-box;
			opacity: 1;
			height: 34px;
			width: 34px;
			padding: 7px 20px;
			cursor: pointer;
			margin-right: 0;
		}
	}

</style>
