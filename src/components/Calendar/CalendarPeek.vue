<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, ref, PropType } from 'vue'
import { t } from '@nextcloud/l10n'
import orderBy from 'lodash/orderBy'
import moment from '@nextcloud/moment'

import NcPopover from '@nextcloud/vue/components/NcPopover'

import { usePollStore } from '../../stores/poll.ts'

import CalendarInfo from './CalendarInfo.vue'
import { CalendarAPI } from '../../Api/index.ts'
import { Logger } from '../../helpers/index.ts'
import { Option } from '../../Types/index.ts'
import { AxiosError } from '@nextcloud/axios'
import AlertIcon from 'vue-material-design-icons/Alert.vue'

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
	type: string
}

const pollStore = usePollStore()

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		required: true,
	},
})

const events = ref<CalendarEvent[]>([])

const detectAllDay = computed(() => {
	const from = moment.unix(props.option.timestamp)
	const to = moment.unix(
		props.option.timestamp + Math.max(0, props.option.duration),
	)
	const dayLongEvent =
		from.unix() === moment(from).startOf('day').unix()
		&& to.unix() === moment(to).startOf('day').unix()
		&& from.unix() !== to.unix()
	return {
		allDay: dayLongEvent,
		type: dayLongEvent ? 'date' : 'dateTime',
	}
})

const sortedEvents = computed(() => {
	const sortedEvents = [...events.value]
	sortedEvents.push(currentEvent.value)
	return orderBy(sortedEvents, ['start', 'end'], ['asc', 'asc'])
})

const currentEvent = computed(
	(): CalendarEvent => ({
		id: props.option.id,
		UID: props.option.id,
		calendarUri: '',
		calendarKey: 0,
		calendarName: 'Polls',
		displayColor: 'transparent',
		allDay: detectAllDay.value.allDay,
		description: pollStore.configuration.description,
		start: props.option.timestamp,
		location: '',
		end: props.option.timestamp + props.option.duration,
		status: 'self',
		summary: pollStore.configuration.title,
		type: detectAllDay.value.type,
	}),
)

onMounted(async () => {
	try {
		const response = await CalendarAPI.getEvents(props.option.id)
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
	<NcPopover v-if="events.length" v-bind="$attrs" class="calendar-peek">
		<template #trigger>
			<AlertIcon
				fill-color="var(--color-warning)"
				:size="24"
				:title="t('polls', 'Possible calendar conflicts')" />
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
	border-radius: var(--border-radius);
}
</style>
