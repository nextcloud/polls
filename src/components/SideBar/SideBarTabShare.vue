<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { onBeforeRouteLeave, onBeforeRouteUpdate } from 'vue-router'

import { useSharesStore } from '../../stores/shares'
import { useSessionStore } from '../../stores/session'

import SharesList from '../Shares/SharesList.vue'
import SharesListUnsent from '../Shares/SharesListUnsent.vue'
import SharesListLocked from '../Shares/SharesListLocked.vue'

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
	<div class="sidebar-share">
		<SharesListUnsent
			v-if="sessionStore.appPermissions.addShares"
			class="shares unsent" />
		<SharesList class="shares effective" />
		<SharesListLocked
			v-if="sessionStore.appPermissions.addShares"
			class="shares" />
	</div>
</template>

<style lang="scss">
.sidebar-share {
	display: flex;
	flex-direction: column;
}

.shares-list {
	display: flex;
	flex-flow: column;
	justify-content: flex-start;
	padding-top: 8px;

	> li {
		display: flex;
		align-items: stretch;
		margin: 4px 0;
	}
}

.share-item {
	display: flex;
	flex: 1;
	align-items: center;
	max-width: 100%;
}

.share-item__description {
	flex: 1;
	min-width: 50px;
	color: var(--color-text-maxcontrast);
	padding-inline-start: 8px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
</style>
