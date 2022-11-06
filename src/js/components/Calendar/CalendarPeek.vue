<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcPopover class="calendar-peek">
		<template v-if="events.length" #trigger>
			<div class="calendar-peek__conflict icon icon-calendar" />
			<p class="calendar-peek__caption">
				{{ t('polls', 'Conflict') }}
			</p>
		</template>
		<div class="calendar-peek__grid">
			<CalendarInfo v-for="eventItem in sortedEvents"
				:key="eventItem.UID"
				:calendar-event="eventItem"
				:option="option" />
		</div>
	</NcPopover>
</template>
n
<script>

import { mapState } from 'vuex'
import { orderBy } from 'lodash'
import { NcPopover } from '@nextcloud/vue'
import moment from '@nextcloud/moment'
import CalendarInfo from './CalendarInfo.vue'
import { showError } from '@nextcloud/dialogs'
import { CalendarAPI } from '../../Api/calendar.js'

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
			poll: (state) => state.poll,
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
			sortedEvents.push(this.thisOption)
			return orderBy(sortedEvents, ['start', 'end'], ['asc', 'asc'])
		},

		thisOption() {
			return {
				id: this.option.id,
				UID: this.option.id,
				calendarUri: this.poll.uri,
				calendarKey: 0,
				calendarName: 'Polls',
				displayColor: 'transparent',
				allDay: this.detectAllDay.allDay,
				description: this.poll.description,
				start: this.option.timestamp,
				location: '',
				end: this.option.timestamp + this.option.duration,
				status: 'self',
				summary: this.poll.title,
				type: this.detectAllDay.type,
			}
		},
	},

	mounted() {
		this.getEvents()
	},

	methods: {
		async getEvents() {
			try {
				const response = CalendarAPI.getEvents(this.option.pollId)
				this.events = response.data.events
			} catch (e) {
				if (e.message === 'Network Error') {
					showError(t('polls', 'Got a network error while checking calendar events.'))
				}
			}
		},

	},
}

</script>

<style lang="scss">

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
