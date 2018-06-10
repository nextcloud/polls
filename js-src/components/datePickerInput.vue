<template>
	<input size="10" maxlength="10" 
	:placeholder="placeholder" 
	:dateFormat="dateFormat" 
	:value="value" 
	@input="$emit('input', $event.target.value)" />
</template>

<script>
	export default {
		props: ['value', 'placeholder', 'dateFormat'],
		mounted: function() {
			$(this.$el).datepicker({
				dateFormat: 'yy-mm-dd',
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
