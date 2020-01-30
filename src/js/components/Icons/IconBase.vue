<template>
	<svg xmlns="http://www.w3.org/2000/svg"
		:width="width"
		:height="height"
		viewBox="0 0 16 16"
		:aria-labelledby="iconName"
		role="presentation">
		<title :id="iconName" lang="en">{{ iconName }} icon</title>
		<g :fill="iconColor">
			<slot />
		</g>
	</svg>
</template>

<script>
export default {
	props: {
		iconName: {
			type: String,
			default: 'box'
		},
		width: {
			type: [Number, String],
			default: 16
		},
		height: {
			type: [Number, String],
			default: 16
		},
		iconColor: {
			type: String,
			default: 'currentColor'
		}
	}
}
</script>

<style scoped>
	svg {
		display: inline-block;
		vertical-align: baseline;
		margin-bottom: -2px;
	}
</style>
