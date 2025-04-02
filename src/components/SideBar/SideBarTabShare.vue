<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import { useSharesStore } from '../../stores/shares.ts'
import { useSessionStore } from '../../stores/session.ts'

import SharesList from '../Shares/SharesList.vue'
import SharesListUnsent from '../Shares/SharesListUnsent.vue'
import SharesListLocked from '../Shares/SharesListLocked.vue'
import { Event } from '../../Types/index.ts'

const sharesStore = useSharesStore()
const sessionStore = useSessionStore()

onMounted(() => {
	subscribe(Event.ChangeShares, () => sharesStore.load())
})

onUnmounted(() => {
	unsubscribe(Event.ChangeShares, () => sharesStore.load())
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
	padding-left: 8px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
</style>
