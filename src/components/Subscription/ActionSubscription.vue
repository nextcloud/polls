<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { NcActions, NcActionCheckbox } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { useSubscriptionStore } from '../../stores/subscription.ts'
import { useShareStore } from '../../stores/share.ts'

const subscriptionStore = useSubscriptionStore()
const shareStore = useShareStore()

const label = computed(() => shareStore.user.emailAddress
	? t('polls', 'Receive notification email on activity to {emailAddress}', { emailAddress: shareStore.user.emailAddress })
	: t('polls', 'Receive notification email on activity'))

</script>

<template>
	<NcActions>
		<NcActionCheckbox v-model="subscriptionStore.subscribed" :label="label" />
	</NcActions>
</template>

<style lang="css">
	.subscription {
		padding: 8px;
	}
</style>
