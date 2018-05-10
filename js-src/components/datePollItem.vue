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
	}
</script>
