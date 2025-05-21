<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { DateTime, Duration } from 'luxon'
import { useSessionStore } from '../../stores/session.ts'
import DateBox from '../Base/modules/DateBox.vue'

interface Props {
	timeStamp: number
	durationSeconds?: number
}

const sessionStore = useSessionStore()

const { timeStamp, durationSeconds = 0 } = defineProps<Props>()

// computed from as DateTime from Luxon
const from = DateTime.fromSeconds(timeStamp).setLocale(
	sessionStore.currentUser.languageCodeIntl,
)

const duration = Duration.fromMillis(durationSeconds * 1000)
</script>

<template>
	<div class="option-item__option--datebox">
		<DateBox :date-time="from" :duration="duration" />
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
