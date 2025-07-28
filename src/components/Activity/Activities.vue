<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import ActivityItem from './ActivityItem.vue'
import { useActivityStore } from '../../stores/activity'

const activityStore = useActivityStore()
</script>

<template>
	<TransitionGroup name="list" class="activities" tag="ul">
		<ActivityItem
			v-for="item in activityStore.getActivitiesForPoll"
			:key="item.activity_id"
			:activity="item"
			tag="li" />
	</TransitionGroup>
</template>
