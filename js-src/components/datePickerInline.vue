^<template>
		<div class="datepicker-inline"></div>
</template>

<script>
	export default {
		props: {		
			minDate: {
				default: null
			},
			// get moment.js locale information, because datepicker initializes in default localization
			localeData: {
			},
			time: {
			}
		},

		mounted: function() {
			$(this.$el).datepicker({
				dateFormat: 'yy-mm-dd',
				minDate: this.minDate,
				monthNames: this.localeData.months(),
				dayNamesMin: this.localeData.weekdaysMin(),
				dayNamesShort: this.localeData.weekdaysShort(),
				firstDay: this.localeData.firstDayOfWeek(),
				showOtherMonths: true,
				selectOtherMonths: true,
				onSelect: this.onSelect
			});
		},
		methods: {
			onSelect(date) {
				var time;
				if (this.time === '') {
					time = '12:00';
				} else {
					time = this.time
				};
				this.$emit('selected', moment(date + ' ' + time)).date;
			}
		},
		beforeDestroy: function() {
			$(this.$el).datepicker('hide').datepicker('destroy');
		}
		
	}
</script>
