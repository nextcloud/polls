Vue.component('date-picker-input', {
	props: ['value', 'placeholder', 'dateFormat'],
	template:
		'<input size="10" maxlength="10" ' +
		':placeholder="placeholder" ' +
		':value="value" ' +
		':dateFormat="dateFormat" ' +
		'@input="$emit(\'input\', $event.target.value)" />',
	mounted: function() {
		$(this.$el).datepicker({
			dateFormat: this.dateFormat,
			onSelect: this.onClose
		});
	},
	methods: {
		onClose(date) {
			this.$emit('input', date);
		}
	},
	beforeDestroy: function() {
		$(this.$el).datepicker('hide').datepicker('destroy');
	},
	watch: {
		value(newVal) { $(this.el).datepicker('setDate', newVal); }
	}
});