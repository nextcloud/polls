<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import moment from '@nextcloud/moment'
import { computed, PropType } from 'vue'
import { Option } from '../../Types/index.ts'

const props = defineProps({
	calendarEvent: {
		type: Object,
		required: true,
	},

	option: {
		type: Object as PropType<Option>,
		required: true,
	},
})

const calendarStyle = computed(() => ({
	backgroundColor: props.calendarEvent.displayColor,
	color: fontColor.value,
}))

const fontColor = computed(() => {
	if (props.calendarEvent.displayColor === 'transparent') {
		return 'var(--color-main-text)'
	}

	const hex = props.calendarEvent.displayColor.replace(/#/, '')
	const r = parseInt(hex.slice(0, 2), 16)
	const g = parseInt(hex.slice(2, 4), 16)
	const b = parseInt(hex.slice(4, 6), 16)

	const l = [0.299 * r, 0.587 * g, 0.114 * b].reduce((a, b) => a + b) / 255

	return l > 0.5 ? '#222' : '#ddd'
})

const dayStart = computed(() => moment.unix(props.calendarEvent.start).format('ddd'))

const dayEnd = computed(() => moment.unix(props.calendarEvent.end - 1).format('ddd'))

const dayDisplay = computed(() => {
	if (dayEnd.value === dayStart.value) {
		return dayStart.value
	}

	return `${dayStart.value} - ${dayEnd.value}`
})

const timeStart = computed(() => moment.unix(props.calendarEvent.start).format('LT'))

const timeEnd = computed(() => moment.unix(props.calendarEvent.end).format('LT'))

const timeDisplay = computed(() => {
	if (timeEnd.value === timeStart.value) {
		return timeStart.value
	}
	return `${timeStart.value} - ${timeEnd.value}`
})

const showJustDays = computed(
	() => dayStart.value !== dayEnd.value || props.calendarEvent.allDay,
)

const statusClass = computed(() => props.calendarEvent.status.toLowerCase())

const conflictLevel = computed(() => {
	if (props.calendarEvent.calendarKey === 0) {
		return 'conflict-ignore'
	}

	// No conflict, if calendarEvent starts after end of option
	if (
		props.calendarEvent.start
		>= props.option.timestamp + props.option.duration
	) {
		return 'conflict-no'
	}

	// No conflict, if calendarEvent ends before option
	if (props.calendarEvent.end <= props.option.timestamp) {
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
	border-radius: var(--border-radius);
	margin: 4px 0;
	padding: 0 4px;

	.summay {
		margin-left: 4px;
	}

	&.conflict-ignore {
		border-left: 4px solid transparent;
		.summay {
			font-weight: bold;
		}
	}

	&.conflict-no {
		border-left: 4px solid var(--color-success);
	}

	&.cancelled {
		text-decoration: line-through;
		opacity: 0.5;
	}

	&.tentative {
		opacity: 0.5;
	}

	&.conflict-yes {
		border-left: 4px solid var(--color-error);
	}
}

.calendar-info__time {
	width: 65px;
	font-size: 0.8em;
	flex: 0 auto;
}
</style>
