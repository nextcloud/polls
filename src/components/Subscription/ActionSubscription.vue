<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
	import NcActionCheckbox from '@nextcloud/vue/dist/Components/NcActionCheckbox.js'
	import { t } from '@nextcloud/l10n'
	import { useSubscriptionStore } from '../../stores/subscription.ts'
	import { useSessionStore } from '../../stores/session.ts'

	const subscriptionStore = useSubscriptionStore()
	const sessionStore = useSessionStore()

	const label = computed(() => sessionStore.share.user.emailAddress
		? t('polls', 'Receive notification email on activity to {emailAddress}', { emailAddress: sessionStore.share.user.emailAddress })
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
