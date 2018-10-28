<template>
	<div class="controls">
		<breadcrump :index-page="indexPage" :intitle="intitle"></breadcrump>
		<slot></slot>
	</div>
</template>

<script>
	import Breadcrump from './_base-breadcrump.vue'
	export default {
		components: {
			Breadcrump
		},
		props: ['intitle','indexPage'],
	}
</script>

<style lang="scss">

	.controls {
		display: flex;
		border-bottom: 1px solid var(--color-border);
		position: fixed;
		background: var(--color-main-background);
		width: 100%;
		height: 45px;
		z-index: 1001;
		.button, button {
			flex-shrink: 0;
			height: 36px;
			padding: 7px 10px;
			border: 0;
			background: transparent;
			color: var(--color-text-lighter);
			&.primary {
				background: var(--color-primary);
				color: var(--color-primary-text);
			}
		}

		.breadcrump {
			overflow: hidden;
			min-width: 35px;
			div.crumb:last-child {
					flex-shrink: 1;
					overflow: hidden;
				> span {
					flex-shrink: 1;
					text-overflow: ellipsis;
				}
			}
		}
	}
</style>
