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

interface Props {
	option: SimpleOption | Option
	timezone?: string | undefined
	sequence?: Sequence | undefined
	title?: string | undefined
}

const {
	option,
	timezone = Intl.DateTimeFormat().resolvedOptions().timeZone,
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
		? n('polls', '%n repetition', '%n repetitions', sequence.repetitions)
		: '',
)
</script>

<template>
	<div class="preview-box">
		<div v-if="title" class="preview-title">{{ title }}</div>
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
	</div>
</template>

<style lang="scss" scoped>
.preview-box {
	display: grid;
	grid-template-columns: 1fr 1fr;
	margin-bottom: 2rem;
	place-items: stretch;
}

.preview-title {
	grid-row: 1;
	grid-column: 1 / span 2;
	font-size: 0.8em;
	color: var(--color-text-maxcontrast);
	font-weight: 600;
	text-align: center;
	margin-bottom: 0.5rem;
}

.preview-date {
	grid-row: 2;
	grid-column: 1;
	display: flex;
	place-items: center;
	margin-bottom: 8px;
}
.preview-repetitions {
	grid-row: 2;
	grid-column: 2;
	display: flex;
	flex-direction: column;
	place-items: center;
	font-weight: 700;
	font-size: 0.8em;
	opacity: 0.6;
	background-color: var(--color-background-darker);
	padding: 1rem 1rem 0;
	border-radius: var(--border-radius-container-large);
}
</style>
