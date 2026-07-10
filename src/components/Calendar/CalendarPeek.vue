<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AxiosError } from '@nextcloud/axios'
import type { Option } from '../../stores/options.types'
import type { CalendarEvent } from './calendar.types'

import { t } from '@nextcloud/l10n'
import orderBy from 'lodash/orderBy'
import { computed, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import CalendarIcon from 'vue-material-design-icons/CalendarOutline.vue'
import CalendarInfo from './CalendarInfo.vue'
import { CalendarAPI } from '../../Api'
import { Logger } from '../../helpers/modules/logger'
import { usePollStore } from '../../stores/poll'
import { getDatesFromOption } from '@/composables/optionDateTime'

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
		closeOnClickOutside>
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
				:calendarEvent="eventItem"
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
