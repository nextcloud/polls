<template>
	<input size="16" maxlength="16" 
	:placeholder="placeholder" 
	:value="value" 
	@input="$emit('input', $event.target.value)" />
</template>

<script>
	export default {
		props: ['value', 'placeholder'],
		mounted: function() {
			moment.locale(OC.getLocale())
			$(this.$el).datepicker({
				regional: OC.getLocale(),
				dateFormat: moment.localeData().longDateFormat('l').toLowerCase().replace("yyyy", "yy"),
				onSelect: this.onClose
			});
		},
		
		methods: {
			onClose(value) {
				this.$emit('input', value);
				console.log(value);
			}
		},
		
		beforeDestroy: function() {
			$(this.$el).datepicker('hide').datepicker('destroy');
		}
	}
</script>
