<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions>
		<NcActionCheckbox v-model="subscribe" :label="label" />
	</NcActions>
</template>

<script>
import { mapState } from 'vuex'
import { NcActions, NcActionCheckbox } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'ActionSubscription',

	components: {
		NcActions, NcActionCheckbox,
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
				return this.subscribed
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
