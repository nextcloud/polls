<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, onMounted, onUnmounted } from 'vue'
	import { t } from '@nextcloud/l10n'
	import { subscribe, unsubscribe } from '@nextcloud/event-bus'

	import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

	import ActivityIcon from 'vue-material-design-icons/LightningBolt.vue'

	import Activities from '../Activity/Activities.vue'
	import { useActivityStore } from '../../stores/activity.ts'

	const activityStore = useActivityStore()
	const emptyContentProps = {
		name: t('polls', 'No activity yet'),
	}

	const showEmptyContent = computed(() => activityStore.list.length === 0)

	onMounted(() => {
		subscribe('polls:activity:update', () => activityStore.load())
	})

	onUnmounted(() => {
		unsubscribe('polls:activity:update', () => activityStore.load())
	})
</script>

<template>
	<div class="comments">
		<Activities v-if="!showEmptyContent" />
		<NcEmptyContent v-else v-bind="emptyContentProps">
			<template #icon>
				<ActivityIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>
