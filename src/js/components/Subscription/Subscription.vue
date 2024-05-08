<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="subscription">
		<NcCheckboxRadioSwitch :checked.sync="subscribe" type="switch">
			{{ label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
export default {
	name: 'Subscription',

	components: {
		NcCheckboxRadioSwitch,
	},

	computed: {
		...mapState({
			subscribed: (state) => state.subscription.subscribed,
			emailAddress: (state) => state.share.user.emailAddress,
		}),

		label() {
			if (this.emailAddress) {
				return t('polls', 'Receive notification email on activity to {emailAddress}', { emailAddress: this.emailAddress })
			}
			return t('polls', 'Receive notification email on activity')

		},

		subscribe: {
			get() {
				return !!this.subscribed
			},
			set(value) {
				this.$store.dispatch('subscription/update', value)
			},
		},
	},
}
</script>

<style lang="css">
	.subscription {
		padding: 8px;
	}
</style>
