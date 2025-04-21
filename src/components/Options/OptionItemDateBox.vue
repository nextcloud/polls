<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { DateTime, Duration } from 'luxon'
import { useSessionStore } from '../../stores/session.ts'
import DateBox from '../Base/modules/DateBox.vue'

const sessionStore = useSessionStore()

const props = defineProps({
	timeStamp: {
		type: Number,
		default: 0,
	},
	durationSeconds: {
		type: Number,
		default: 0,
	},
})
// computed from as DateTime from Luxon
const from = DateTime.fromSeconds(props.timeStamp).setLocale(
	sessionStore.currentUser.languageCode,
)

const duration = Duration.fromMillis(props.durationSeconds * 1000)
</script>

<template>
	<div class="option-item__option--datebox">
		<DateBox :luxon-date="from" :duration="duration" />
	</div>
</template>

<style lang="scss">
.option-item__option--datebox {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	justify-content: flex-start;
}
</style>
