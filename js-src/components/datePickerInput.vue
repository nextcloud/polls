<template>
	<input size="10" maxlength="10" 
	:placeholder="placeholder" 
	:value="value" 
	:dateFormat="dateFormat" 
	@input="$emit('input', $event.target.value)" />
</template>

<script>
	export default {
		props: ['value', 'placeholder', 'dateFormat'],
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
	}
</script>
