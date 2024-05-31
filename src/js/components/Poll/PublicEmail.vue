<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="public-email">
		<InputDiv v-model="emailAddress"
			:title="check.result"
			:signaling-class="check.status"
			:placeholder="t('polls', 'Optional email address')"
			submit
			@submit="submitEmailAddress" />
	</div>
</template>

<script>
import { debounce } from 'lodash'
import { InputDiv } from '../Base/index.js'
import { ValidatorAPI } from '../../Api/index.js'
import { t } from '@nextcloud/l10n'

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
	},

	watch: {
		emailAddress() {
			this.validateEmailAddress()
		},
	},

	methods: {
		t,
		validateEmailAddress: debounce(async function() {
			if (this.emailAddress.length < 1 || this.emailAddressUnchanged) {
				this.checkResult = ''
				this.checkStatus = ''
			} else {
				try {
					this.checking = true
					await ValidatorAPI.validateEmailAddress(this.emailAddress)
					this.checkResult = t('polls', 'valid email address.')
					this.checkStatus = 'success'
				} catch (error) {
					if (error?.code === 'ERR_CANCELED') return
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
