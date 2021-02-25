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
	<div class="public-email">
		<!-- <div class="input-wrapper">
			<input v-model="emailAddress" v-tooltip="check.result" :class="['input', check.status]"
				:placeholder="t('polls', 'Optional email address')" @keyup.enter="submitEmailAddress">
			<ButtonDiv submit @click="submitEmailAddress" />
		</div> -->

		<InputDiv v-tooltip="check.result"
			:value.sync="emailAddress"
			:class="check.status"
			:input-class="check.status"
			:placeholder="t('polls', 'Optional email address')"
			@submit="submitEmailAddress" />
		<h3>{{ t("polls", "With your email address you can subscribe to notifications and you will receive your personal link to this poll.") }}</h3>
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
// import ButtonDiv from '../Base/ButtonDiv'
import InputDiv from '../Base/InputDiv'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { mapState } from 'vuex'

export default {
	name: 'PublicEmail',

	components: {
		// ButtonDiv,
		InputDiv,
	},

	data() {
		return {
			checkingEmailAddress: false,
			isValidEmailAddress: false,
		}
	},

	computed: {
		...mapState({
			share: state => state.share,
		}),

		emailAddress: {
			get() {
				return this.share.emailAddress
			},
			set(value) {
				this.$store.commit('share/setEmailAddress', value)
			},
		},

		check() {
			if (this.checkingEmailAddress) {
				return {
					result: t('polls', 'Checking email address …'),
					status: 'checking',
				}
			} else {
				if (this.emailAddress.length < 1) {
					return {
						result: '',
						status: '',
					}
				} else if (!this.isValidEmailAddress) {
					return {
						result: t('polls', 'Invalid email address.'),
						status: 'error',
					}
				} else {
					return {
						result: t('polls', 'valid email address.'),
						status: 'success',
					}
				}
			}
		},
	},

	watch: {
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

	methods: {
		validateEmailAddress: debounce(function() {
			if (this.emailAddress.length > 0) {
				return axios.get(generateUrl('apps/polls/check/emailaddress') + '/' + this.emailAddress)
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

		submitEmailAddress() {
			if (this.isValidEmailAddress || this.emailAddress.length === 0) {
				this.$store.dispatch('share/updateEmailAddress', { emailAddress: this.emailAddress })
					.then((response) => {
						showSuccess(t('polls', 'Email address {emailAddress} saved.', { emailAddress: this.emailAddress }))
					})
					.catch(() => {
						showError(t('polls', 'Error saving email address {emailAddress}', { emailAddress: this.emailAddress }))
					})
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.input-wrapper {
	display: flex;
}

input {
	width: 240px;
}

</style>
