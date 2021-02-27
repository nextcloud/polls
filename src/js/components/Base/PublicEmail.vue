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
		<InputDiv v-model="emailAddress"
			v-tooltip="check.result"
			:signaling-class="check.status"
			:placeholder="t('polls', 'Optional email address')"
			@submit="submitEmailAddress" />
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
import InputDiv from '../Base/InputDiv'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'PublicEmail',

	components: {
		InputDiv,
	},

	props: {
		value: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			emailAddress: this.value,
			checkResult: '',
			checkStatus: '',
			checking: false,
		}
	},

	computed: {
		emailAddressUnchanged() {
			return this.emailAddress === this.value
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
	},

	watch: {
		emailAddress: function() {
			this.validateEmailAddress()
		},
	},

	methods: {
		validateEmailAddress: debounce(async function() {
			if (this.emailAddress.length < 1 || this.emailAddressUnchanged) {
				this.checkResult = ''
				this.checkStatus = ''
			} else {
				try {
					this.checking = true
					await axios.get(generateUrl('apps/polls/check/emailaddress') + '/' + this.emailAddress)
					this.checkResult = t('polls', 'valid email address.')
					this.checkStatus = 'success'
				} catch {
					this.checkResult = t('polls', 'Invalid email address.')
					this.checkStatus = 'error'
				} finally {
					this.checking = false
				}

			}
		}, 500),

		async submitEmailAddress() {
			if (this.checkResult === 'success' || this.emailAddress.length > 0) {
				this.$emit('update', this.emailAddress)
			}
		},
	},
}
</script>
