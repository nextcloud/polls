<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime, Duration } from 'luxon'

import { n } from '@nextcloud/l10n'

import DateBox from '../Base/modules/DateBox.vue'

import type { Option, Sequence, SimpleOption } from '../../stores/options.types'
import { getDates } from '@/composables/optionDateTime'

interface Props {
	option: SimpleOption | Option
	timezone?: string | undefined
	otherTimeZone?: string | undefined
	sequence?: Sequence | undefined
	title?: string | undefined
}

const {
	option,
	timezone = Intl.DateTimeFormat().resolvedOptions().timeZone,
	otherTimeZone,
	sequence,
	title,
} = defineProps<Props>()

const dateTime = computed(() =>
	DateTime.fromISO(option.isoTimestamp).setZone(timezone),
)

const duration = computed(() => Duration.fromISO(option.isoDuration || 'PT0S'))

// computed last from dateTime repetition
const lastRepetitionDateTime = computed(() =>
	dateTime.value.plus(sequenceDuration.value),
)

const sequenceDuration = computed(() =>
	sequence?.repetitions
		? Duration.fromObject({
				[sequence.unit.id]: sequence.stepWidth * sequence.repetitions,
			})
		: Duration.fromObject({ millisecond: 0 }),
)

const repetitionCaption = computed(() =>
	sequence?.repetitions
		? n('polls', '%n repetition', 'Last of %n repetitions', sequence.repetitions)
		: '',
)

const simpleDate = computed(() => {
	const dates = getDates(dateTime.value, duration.value, otherTimeZone)
	return dates.interval.toLocaleString(DateTime.DATETIME_MED)
})
</script>

<template>
	<div class="preview-box">
		<div
			v-if="title"
			:class="['preview-title', { 'span-2': sequence?.repetitions }]">
			{{ title }}
		</div>
		<div class="preview-date">
			<DateBox
				:start-date="dateTime"
				:duration="duration"
				:timezone="timezone" />
		</div>

		<div v-if="sequence?.repetitions" class="preview-repetitions">
			<span>{{ repetitionCaption }}</span>
			<DateBox
				:start-date="lastRepetitionDateTime"
				:duration="duration"
				:timezone="timezone" />
		</div>
		<div
			v-if="otherTimeZone"
			:class="['timezone-information', { 'span-2': sequence?.repetitions }]">
			<div>{{ otherTimeZone }}:</div>
			<div>{{ simpleDate }}</div>
		</div>
	</div>
</template>

<style lang="scss" scoped>
.preview-box {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
	place-items: stretch;
	background-color: rgb(from var(--color-background-darker) r g b/0.6);
	border-radius: var(--border-radius-container-large);
	padding: 1rem;

	.span-2 {
		grid-column: 1 / span 2;
	}

	.preview-title {
		grid-row: 1;
		font-size: 0.8em;
		color: var(--color-text-maxcontrast);
		font-weight: 600;
		text-align: center;
		margin-bottom: 0.5rem;
	}

	.preview-date {
		display: flex;
		place-items: center;
		margin-block-end: 0.5rem;
	}

	.preview-repetitions {
		display: flex;
		flex-direction: column;
		place-items: center;
		font-weight: 700;
		font-size: 0.8em;
		opacity: 0.6;
		background-color: var(--color-background-darker);
		padding: 0.5rem;
		border-radius: var(--border-radius-container-large);
	}

	.timezone-information {
		grid-column: 1;
		grid-row: 2;
		padding: 0.5rem 0 2rem;
		font-size: 0.8em;
		font-weight: 600;
		color: var(--color-text-maxcontrast);
		text-align: center;
	}
}
</style>
