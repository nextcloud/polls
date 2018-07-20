<template>
	<div class="NewDate">
		<date-picker-input :placeholder="t('polls', 'Add new date option')" v-model="dateArray.FromDate"></date-picker-input>
		<time-picker       :placeholder="t('polls', 'Add time') "          v-model="dateArray.FromTime" v-show="dateArray.FromDate"/>
		<date-picker-input :placeholder="t('polls', 'Add end date or leave empty')" v-model="dateArray.ToDate" v-show="dateArray.FromDate"></date-picker-input>
		<time-picker       :placeholder="t('polls', 'Add time') "          v-model="dateArray.ToTime" v-show="dateArray.ToDate"/>

	</div>
</template>

<script>
	export default {
		mounted: function() {
			$('.hasDatePicker').datepicker({
				dateFormat: 'yy-mm-dd',
				onSelect: this.onClose
			});
				$('.hasTimePicker').timepicker({
				onClose: this.onClose
			});

		},

		data: function () {
			return {
			dateArray: { 
				FromDate: '',
				FromTime: '',
				ToDate: '',
				ToTime: ''
				}
			}
		},
		
		methods: {
			onClose(value) {
				console.log(value);
				this.$emit('input', value);
			}
		},
		beforeDestroy: function() {
			$('.hasDatePicker').datepicker('hide').datepicker('destroy');
			$('.hasTimePicker').timepicker('hide').timepicker('destroy');

		},
		watch: {
			value(newVal) { $(this.el).datepicker('setDate', newVal); }
		}
	}
</script>
