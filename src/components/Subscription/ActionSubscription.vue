<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions>
		<NcActionCheckbox v-model="subscriptionStore.subscribed" :label="label" />
	</NcActions>
</template>

<script>
import { mapStores } from 'pinia'
import { NcActions, NcActionCheckbox } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { useSubscriptionStore } from '../../stores/subscription.ts'
import { useShareStore } from '../../stores/share.ts'

export default {
	name: 'ActionSubscription',

	components: {
		NcActions, NcActionCheckbox,
	},

	computed: {
		...mapStores(useSubscriptionStore, useShareStore),

		label() {
			if (this.shareStore.user.emailAddress) {
				return t('polls', 'Receive notification email on activity to {emailAddress}', { emailAddress: this.shareStore.user.emailAddress })
			}
			return t('polls', 'Receive notification email on activity')
		},

	},
}
</script>

<style lang="css">
	.subscription {
		padding: 8px;
	}
</style>
