<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="subscription">
		<NcCheckboxRadioSwitch :checked.sync="subscriptionStore.subscribed" 
			type="switch"
			@change="subscription.write()">
			{{ label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { useSubscriptionStore } from '../../stores/subscription.ts'
import { useShareStore } from '../../stores/share.ts'

export default {
	name: 'Subscription',

	components: {
		NcCheckboxRadioSwitch,
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
