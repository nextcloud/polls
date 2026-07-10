<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { onBeforeRouteLeave, onBeforeRouteUpdate } from 'vue-router'
import SharesList from '../Shares/SharesList.vue'
import SharesListLocked from '../Shares/SharesListLocked.vue'
import SharesListUnsent from '../Shares/SharesListUnsent.vue'
import { useSessionStore } from '../../stores/session.ts'
import { useSharesStore } from '../../stores/shares.ts'

const sharesStore = useSharesStore()
const sessionStore = useSessionStore()

onMounted(() => {
	sharesStore.load()
})

onBeforeRouteUpdate(async () => {
	sharesStore.load()
})

onBeforeRouteLeave(() => {
	sharesStore.$reset()
})
</script>

<template>
	<SharesListUnsent v-if="sessionStore.appPermissions.addShares" />
	<SharesList />
	<SharesListLocked v-if="sessionStore.appPermissions.addShares" />
</template>
