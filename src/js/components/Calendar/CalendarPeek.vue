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
	<div class="calendar-peek">
		<Popover>
			<div v-if="events.length" slot="trigger">
				<div class="calendar-peek__conflict icon icon-calendar" />
				<p class="calendar-peek__caption">
					{{ t('polls', 'Conflict') }}
				</p>
			</div>
			<div class="calendar-peek__grid">
				<CalendarInfo v-for="eventItem in sortedEvents" :key="eventItem.UID"
					:event="eventItem"
					:option="option" />
			</div>
		</Popover>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import orderBy from 'lodash/orderBy'
import { Popover } from '@nextcloud/vue'
import CalendarInfo from '../Calendar/CalendarInfo'

export default {
	name: 'CalendarPeek',

	components: {
		CalendarInfo,
		Popover,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		open: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			events: [],
			event: {
				relatedFrom: 0,
				relatedTo: 0,
				name: '',
				key: '',
				displayColor: '',
				permissions: 0,
				eventId: 0,
				UID: 0,
				summary: '',
				description: '',
				location: '',
				eventFrom: '',
				eventTo: '',
			},
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
		}),

		sortedEvents() {
			var sortedEvents = [...this.events]
			sortedEvents.push(this.thisOption)
			return orderBy(sortedEvents, ['eventFrom', 'eventTo'], ['asc', 'asc'])
		},

		thisOption() {
			return {
				name: 'Polls',
				key: 0,
				displayColor: '#ffffff',
				permissions: 0,
				eventId: this.option.id,
				UID: this.option.id,
				summary: this.poll.title,
				description: this.poll.description,
				location: '',
				eventFrom: this.option.timestamp,
				eventTo: this.option.timestamp + 3600,
			}
		},
	},

	mounted() {
		this.getEvents()
	},

	methods: {
		getEvents() {
			this.$store
				.dispatch('poll/options/getEvents', { option: this.option })
				.then((response) => {
					this.events = response.events
				})
		},

	},
}

</script>

<style lang="scss">

.calendar-peek__conflict.icon {
	font-style: normal;
	font-weight: 400;
	width: 32px;
	height: 32px;
	// background-size: 28px;
	background-color: var(--color-warning);
	border-radius: 50%;
	margin: 4px auto;
}

.mobile .calendar-peek__caption {
	display: none;
}

.calendar-peek__grid {
	padding: 8px;
	background-color: #fff;
	border-radius: var(--border-radius);
}

.calendar-peek >div {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
}

</style>
