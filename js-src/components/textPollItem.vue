<template>
	<li class="flex-row poll-box">
		<div class="poll-item">{{ option.text }}</div>
		<div class="flex-row options">
			<a @click="$emit('remove')" class="icon icon-delete svg delete-poll"></a>
		</div>
	</li>
</template>

<script>
	export default {
		props: ['option'],
	}
</script>
