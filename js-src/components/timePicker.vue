<template>
	<input size="10" maxlength="10" :placeholder="placeholder"  
	:value="value" 
	@input="$emit('input', $event.target.value)" />',
</template>

<script>
	export default {
		props: ['value', 'placeholder'],
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
	}
</script>
