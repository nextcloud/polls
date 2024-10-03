<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
	import { t } from '@nextcloud/l10n'
	import { useSubscriptionStore } from '../../stores/subscription.ts'
	import { useSessionStore } from '../../stores/session.ts'

	const sessionStore = useSessionStore()
	const subscriptionStore = useSubscriptionStore()

	const label = computed(() => sessionStore.share.user.emailAddress
		? t('polls', 'Receive notification email on activity to {emailAddress}', { emailAddress: sessionStore.share.user.emailAddress })
		: t('polls', 'Receive notification email on activity')
	)

</script>

<template>
	<div class="subscription">
		<NcCheckboxRadioSwitch v-model="subscriptionStore.subscribed" 
			type="switch"
			@change="subscriptionStore.write()">
			{{ label }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<style lang="css">
	.subscription {
		padding: 8px;
	}
</style>
