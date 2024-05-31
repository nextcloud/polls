<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcPopover v-if="events.length" :focus-trap="false" class="calendar-peek">
		<template #trigger>
			<div>
				<div class="calendar-peek__conflict icon icon-calendar" />
				<p class="calendar-peek__caption">
					{{ t('polls', 'Conflict') }}
				</p>
			</div>
		</template>
		<div class="calendar-peek__grid">
			<CalendarInfo v-for="eventItem in sortedEvents"
				:key="eventItem.UID"
				:calendar-event="eventItem"
				:option="option" />
		</div>
	</NcPopover>
</template>

<script>

import { mapState } from 'vuex'
import { orderBy } from 'lodash'
import { NcPopover } from '@nextcloud/vue'
import moment from '@nextcloud/moment'
import CalendarInfo from './CalendarInfo.vue'
import { CalendarAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'CalendarPeek',

	components: {
		CalendarInfo,
		NcPopover,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
	},

	data() {
		return {
			events: [],
		}
	},

	computed: {
		...mapState({
			pollConfiguration: (state) => state.poll.configuration,
		}),

		detectAllDay() {
			const from = moment.unix(this.option.timestamp)
			const to = moment.unix(this.option.timestamp + Math.max(0, this.option.duration))
			const dayLongEvent = from.unix() === moment(from).startOf('day').unix() && to.unix() === moment(to).startOf('day').unix() && from.unix() !== to.unix()
			return {
				allDay: dayLongEvent,
				type: dayLongEvent ? 'date' : 'dateTime',
			}
		},

		sortedEvents() {
			const sortedEvents = [...this.events]
			sortedEvents.push(this.currentOption)
			return orderBy(sortedEvents, ['start', 'end'], ['asc', 'asc'])
		},

		currentOption() {
			return {
				id: this.option.id,
				UID: this.option.id,
				calendarUri: '',
				calendarKey: 0,
				calendarName: 'Polls',
				displayColor: 'transparent',
				allDay: this.detectAllDay.allDay,
				description: this.pollConfiguration.description,
				start: this.option.timestamp,
				location: '',
				end: this.option.timestamp + this.option.duration,
				status: 'self',
				summary: this.pollConfiguration.title,
				type: this.detectAllDay.type,
			}
		},
	},

	mounted() {
		this.getEvents()
	},

	methods: {
		t,
		async getEvents() {
			try {
				const response = await CalendarAPI.getEvents(this.option.id)
				this.events = response.data.events
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error fetching events', { error })
			}
		},

	},
}

</script>

<style lang="scss">

.calendar-peek {
	flex-direction: column;
}

.calendar-peek__conflict.icon {
	width: 32px;
	height: 32px;
	background-color: var(--color-warning);
	border-radius: 50%;
	margin: 4px auto;
}

.calendar-peek__caption {
	font-size: 0.7em;
}

.calendar-peek__grid {
	padding: 8px;
	background-color: var(--color-main-background);
	border-radius: var(--border-radius);
}

</style>
