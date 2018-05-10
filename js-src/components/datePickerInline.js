/* global: Vue */
Vue.component('date-picker-inline', {
	
	props: {		
		minDate: {
			default: null
		},
		// get moment.js locale information, because datepicker initializes in default localization
		localeData: {
		},
		useTime: {
			default: '00:00'
		}
	},
	
	template: 
		'<div class="datepicker-inline"></div>',
	
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: 'yy-mm-dd',
			minDate: this.minDate,
			monthNames: this.localeData.months(),
			dayNamesMin: this.localeData.weekdaysMin(),
			dayNamesShort: this.localeData.weekdaysShort(),
			firstDay: +this.localeData.firstDayOfWeek(),
			showOtherMonths: true,
			selectOtherMonths: true,
			onSelect: this.onSelect
		});
	},
	methods: {
		onSelect(date) {
			var useTime;
			if (this.useTime === '') {
				useTime = '00:00';
			} else {
				useTime = this.useTime
			};
			this.$emit('selected', date + ' ' + useTime);
		}
	},
	beforeDestroy: function() {
		$(this.$el).datepicker('hide').datepicker('destroy');
	}
		
});
