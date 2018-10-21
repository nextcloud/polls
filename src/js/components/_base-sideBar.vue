<template>
	<div class="polls-sidebar" >
		<slot></slot>
	</div>
</template>

<style lang="scss">
	.polls-sidebar {
		min-width: 300px;
		border-left: 1px solid var(--color-border);
		z-index: 500;
		> div, > ul {
			padding: 8px;
		}
	}
</style>
