<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, PropType } from 'vue'
	import moment from '@nextcloud/moment'
	import { BoxType } from '../../../Types/index.ts'

	const props = defineProps({
		date: {
			type: Object as PropType<Date>,
			required: true,
		},
		durationSec: {
			type: Number,
			default: 0,
		},
		display: {
			type: String as PropType<BoxType>,
			default: BoxType.Date,
		},
		hideDate: {
			type: Boolean,
			default: false,
		},
		hideTime: {
			type: Boolean,
			default: false,
		},

	})

	const dateMoment = computed(() => {
		const date = moment(props.date)
		return {
			date: date.format('ll'),
			time: date.format('LT'),
			relative: date.fromNow(),
			dow: date.format('ddd'),
			day: date.format('D'),
			month: date.format(moment().year() === date.year() ? 'MMM' : 'MMM [ \']YY'),
			year: date.format('YYYY'),
			dateTime: date.format('llll'),
			utc: moment(date).utc().format('llll'),
			dueTime: props.durationSec ? moment(date).add(props.durationSec, 'seconds').format('LT') : 0,
		}
	})

</script>

<template>
	<div v-if="props.display === BoxType.Date" :title="dateMoment.utc" class="date__datebox">
		<div v-if="!hideDate" class="month">
			{{ dateMoment.month }}
		</div>
		<div v-if="!hideDate" class="day">
			{{ dateMoment.dow }} {{ dateMoment.day }}
		</div>
		<div v-if="!hideTime" class="time">
			{{ dateMoment.time }} {{ props.durationSec ? ' - ' + dateMoment.dueTime : '' }}
		</div>
	</div>
</template>

<style lang="scss" scoped>
	.date__datebox {
		display: flex;
		flex-direction: column;
		align-items: stretch;
		justify-content: flex-start;
		text-align: center;
		hyphens: auto;
		white-space: nowrap;

		.month, .time {
			white-space: pre;
			font-size: 1.1em;
			padding: 0 0.25em;
			color: var(--color-text-maxcontrast);
		}

		.day {
			font-size: 1.2em;
			font-weight: 600;
			margin: 0.33em 0 0.33em 0;
			padding: 0 0.25em;
		}

		.time {
			font-size: 0.8em;
			padding: 0 0.25em;
		}
	}
</style>
