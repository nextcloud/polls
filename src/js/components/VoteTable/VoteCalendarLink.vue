<script>

export default {
	name: 'VoteCalendarLink',

	methods: {
		newEvent(option) {
			const dateTimeStart = option.timestamp
			const dateTimeEnd = moment.unix(dateTimeStart).add(1, 'hour').unix()

			const calendarRoot = 'apps/calendar'
			const calendarView = 'timeGridWeek' // timeGridDay, 'timeGridWeek' or 'dayGridMonth'
			const calendarTimeRange = moment.unix(dateTimeStart).format('Y-MM-DD') // 'now' or 'yyyy-mm-dd'
			const calendarAction = 'new'
			const calendarMode = 'sidebar' // 'popover' or 'sidebar'
			const calendarIsAllDay = '0'

			const calendarUrl = OC.generateUrl(calendarRoot + '/' + calendarView + '/' + calendarTimeRange + '/' + calendarAction + '/' + calendarMode + '/' + calendarIsAllDay + '/' + dateTimeStart + '/' + dateTimeEnd)

			return calendarUrl
		}
	}
}

</script>
