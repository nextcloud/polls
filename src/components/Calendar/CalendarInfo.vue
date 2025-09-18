<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime } from 'luxon'
import { CalendarEvent } from '../../components/Calendar/CalendarPeek.vue'

import type { Option } from '../../stores/options.types'

interface Props {
	calendarEvent: CalendarEvent
	option: Option
}

const { calendarEvent, option } = defineProps<Props>()

const calendarStyle = computed(() => ({
	backgroundColor: calendarEvent.displayColor,
	color: fontColor.value,
}))

const fontColor = computed(() => {
	if (calendarEvent.displayColor === 'transparent') {
		return 'var(--color-main-text)'
	}

	const hex = calendarEvent.displayColor.replace(/#/, '')
	const r = parseInt(hex.slice(0, 2), 16)
	const g = parseInt(hex.slice(2, 4), 16)
	const b = parseInt(hex.slice(4, 6), 16)

	const l = [0.299 * r, 0.587 * g, 0.114 * b].reduce((a, b) => a + b) / 255

	return l > 0.5 ? '#222' : '#ddd'
})

const eventStart = computed(() => DateTime.fromSeconds(calendarEvent.start))
const eventEnd = computed(() => DateTime.fromSeconds(calendarEvent.end))

const dayDisplay = computed(() => {
	if (eventEnd.value.hasSame(eventStart.value.minus({ second: 1 }), 'day')) {
		return eventStart.value.toLocaleString({ weekday: 'short' })
	}

	return `${eventStart.value.toLocaleString({ weekday: 'short' })} - ${eventEnd.value.toLocaleString({ weekday: 'short' })}`
})

const timeDisplay = computed(() => {
	if (eventStart.value.hasSame(eventEnd.value, 'minute')) {
		return eventStart.value.toLocaleString(DateTime.TIME_SIMPLE)
	}

	return `${eventStart.value.toLocaleString(DateTime.TIME_SIMPLE)} - ${eventEnd.value.toLocaleString(DateTime.TIME_SIMPLE)}`
})

const showJustDays = computed(
	() =>
		!eventEnd.value.hasSame(eventStart.value.minus({ second: 1 }), 'day')
		|| calendarEvent.allDay,
)

const statusClass = computed(() => calendarEvent.status.toLowerCase())

const conflictLevel = computed(() => {
	if (calendarEvent.calendarKey === 0) {
		return 'conflict-ignore'
	}

	// No conflict, if calendarEvent starts after end of option
	if (calendarEvent.start >= option.timestamp + option.duration) {
		return 'conflict-no'
	}

	// No conflict, if calendarEvent is available (not busy)
	if (!calendarEvent.busy) {
		return 'conflict-no'
	}

	// No conflict, if calendarEvent ends before option
	if (calendarEvent.end <= option.timestamp) {
		return 'conflict-no'
	}

	return 'conflict-yes'
})
</script>

<template>
	<div
		class="calendar-info"
		:class="[conflictLevel, statusClass]"
		:style="calendarStyle">
		<div class="calendar-info__time">
			{{ showJustDays ? dayDisplay : timeDisplay }}
		</div>
		<div class="summay" :class="statusClass">
			{{ calendarEvent.summary }}
		</div>
	</div>
</template>

<style lang="scss">
.calendar-info {
	display: flex;
	align-items: center;
	border-radius: var(--border-radius-small);
	margin: 4px 0;
	padding: 0 4px;

	.summay {
		margin-inline-start: 4px;
	}

	&.conflict-ignore {
		border-inline-start: 4px solid transparent;
		.summay {
			font-weight: bold;
		}
	}

	&.conflict-no {
		border-inline-start: 4px solid var(--color-border-success);
	}

	&.cancelled {
		text-decoration: line-through;
		opacity: 0.5;
	}

	&.tentative {
		opacity: 0.5;
	}

	&.conflict-yes {
		border-inline-start: 4px solid var(--color-error);
	}
}

.calendar-info__time {
	width: 65px;
	font-size: 0.8em;
	flex: 0 auto;
}
</style>
