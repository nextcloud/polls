<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { onMounted } from 'vue'
import { onBeforeRouteUpdate } from 'vue-router'
import SharesList from '../Shares/SharesListPollGroup.vue'
import { useSharesStore } from '../../stores/shares.ts'

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
