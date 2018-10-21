<template>
	<li>
		<div>{{ option.text }}</div>
		<div>
			<a @click="$emit('remove')" class="icon icon-delete svg delete-poll"></a>
		</div>
	</li>
</template>

<script>
	export default {
		props: ['option'],
	}
</script>
