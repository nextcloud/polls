<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { onBeforeRouteUpdate } from 'vue-router'

import { useSharesStore } from '../../stores/shares'

import SharesList from '../Shares/SharesListPollGroup.vue'
import { t } from '@nextcloud/l10n'

const sharesStore = useSharesStore()
const infoText = t(
	'polls',
	'Shares for a poll group grant voting access to the polls contained in the poll group.',
)

onMounted(() => {
	sharesStore.load('pollGroup')
})

onBeforeRouteUpdate(async () => {
	sharesStore.load('pollGroup')
})
</script>

<template>
	<div>
		{{ infoText }}
	</div>
	<SharesList :info="infoText" />
</template>
