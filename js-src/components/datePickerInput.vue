<template>
	<input size="10" maxlength="10" 
	:placeholder="placeholder" 
	:dateFormat="dateFormat" 
	:value="value" 
	@input="$emit('input', $event.target.value)" />
</template>

<script>
	export default {
		props: {
			minDate: {
				default: null
			},
			value: {
			},
			placeholder: {
			},
		},
		mounted: function() {
			$(this.$el).datepicker({
				minDate: this.minDate,
				showOtherMonths: true,
				selectOtherMonths: true,
				onSelect: this.onClose
			});
		},
		methods: {
			onClose(value) {
				this.$emit('input', value);
			}
		},
		beforeDestroy: function() {
			$(this.$el).datepicker('hide').datepicker('destroy');
		},
		watch: {
			value(newVal) { $(this.el).datepicker('setDate', newVal); }
		}
	}
</script>
