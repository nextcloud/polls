/* global: Vue */
Vue.component('time-picker', {
	props: ['value', 'placeholder'],
	template: 
		'<input size="10" maxlength="10" :placeholder="placeholder"  ' +
		':value="value" ' +
		'@input="$emit(\'input\', $event.target.value)" />',
	mounted: function() {
		$(this.$el).timepicker({
			onClose: this.onClose
		});
	},
	methods: {
		onClose(time) {
			this.$emit('input', time);
		}
	},
	beforeDestroy: function() {
		$(this.$el).timepicker('hide').timepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).timepicker('setTime', newVal); }
	}
});