<template>
	<div class="polls-sidebar" >
		<slot></slot>
	</div>
</template>

<style lang="scss">
	.polls-sidebar {
		display: flex;
		flex-direction: column;
		flex-grow: 0;
		flex-shrink: 1;
		width: 520px;
		min-width: 300px;
		border-left: 1px solid var(--color-border);
		z-index: 500;
	/* z-index: 1000 !important; */
		> div, > ul {
			padding: 8px;
		}
	}
</style>
