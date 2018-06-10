<template>
	<li class="flex-row poll-box">
		<div class="poll-item">{{option.timestamp | localFullDate}}</div>
		<div class="flex-row options">
			<a @click="$emit('remove')" class="icon-delete"></a>
		</div>
	</li>
</template>

<script>
	export default {
		props: ['option'],
		filters: {
			localFullDate: function (timestamp) {
				if (!timestamp) return '';
				if (!moment(timestamp).isValid()) return 'Invalid Date';
				if (timestamp <  999999999999) timestamp = timestamp *1000;
				return moment(timestamp).format('llll');
			}
		}
	}
</script>
