<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import orderBy from 'lodash/orderBy'

import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcButton from '@nextcloud/vue/components/NcButton'

import CalendarIcon from 'vue-material-design-icons/CalendarOutline.vue'

import CalendarInfo from './CalendarInfo.vue'
import { CalendarAPI } from '../../Api'
import { Logger } from '../../helpers/modules/logger'

import { usePollStore } from '../../stores/poll'

import type { AxiosError } from '@nextcloud/axios'
import type { Option } from '../../stores/options.types'
import { getDatesFromOption } from '@/composables/optionDateTime'

export type CalendarEvent = {
	id: number
	UID: number
	calendarUri: string
	calendarKey: number
	calendarName: string
	displayColor: string
	allDay: boolean
	description: string
	start: number
	location: string
	end: number
	status: string
	summary: string
	type: 'date' | 'dateTime'
	busy: boolean
}

const { option } = defineProps<{ option: Option }>()

const events = ref<CalendarEvent[]>([])

const pollStore = usePollStore()
const optionDates = getDatesFromOption(option)

const sortedEvents = computed(() => {
	const sortedEvents = [...events.value]
	sortedEvents.push(currentEvent.value)
	return orderBy(sortedEvents, ['start', 'end'], ['asc', 'asc'])
})

const currentEvent = computed(
	(): CalendarEvent => ({
		id: option.id,
		UID: option.id,
		calendarUri: '',
		calendarKey: 0,
		calendarName: 'Polls',
		displayColor: 'transparent',
		allDay: optionDates.isFullDays,
		description: pollStore.configuration.description,
		start: optionDates.optionStart.toSeconds(),
		location: '',
		end: optionDates.optionEnd.toSeconds(),
		status: 'self',
		summary: pollStore.configuration.title,
		type: optionDates.isFullDays ? 'date' : 'dateTime',
		busy: false,
	}),
)

onMounted(async () => {
	try {
		const response = await CalendarAPI.getEvents(option.id)
		events.value = response.data.events
	} catch (error) {
		if ((error as AxiosError)?.code === 'ERR_CANCELED') {
			return
		}
		Logger.error('Error fetching events', { error })
	}
})
</script>

<template>
	<NcPopover
		v-if="events.length"
		v-bind="$attrs"
		class="calendar-peek"
		close-on-click-outside>
		<template #trigger>
			<NcButton variant="tertiary-no-background">
				<template #icon>
					<CalendarIcon
						:size="24"
						:title="t('polls', 'Possibly affected calendar events')" />
				</template>
			</NcButton>
		</template>
		<div class="calendar-peek__grid">
			<CalendarInfo
				v-for="eventItem in sortedEvents"
				:key="eventItem.UID"
				:calendar-event="eventItem"
				:option="option" />
		</div>
	</NcPopover>
</template>

<style lang="scss">
.calendar-peek {
	margin: auto;
}

.calendar-peek__grid {
	padding: 8px;
	background-color: var(--color-main-background);
	border-radius: var(--border-radius-small);
}
</style>
