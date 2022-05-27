<template>
	<span :aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon circles-app-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 57 57">
			<path d="M7.1 28.5A21.4 21.4 0 0 1 28.5 7.1m10.7 40A21.4 21.4 0 0 1 10 39M39.2 10A21.4 21.4 0 0 1 47 39.2"
				fill="none"
				:stroke="fillcolor"
				stroke-width="4"
				stroke-linecap="round"
				stroke-linejoin="round" />
			<circle cx="28.5"
				cy="7.1"
				r="6.5"
				:fill="fillColor" />
			<circle cx="39.2"
				cy="-10"
				r="6.5"
				transform="rotate(90)"
				:fill="fillColor" />
			<circle cx="39.2"
				cy="-47"
				r="6.5"
				transform="rotate(90)"
				:fill="fillColor" />
			<title v-if="title">{{ title }}</title>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'CirclesAppIcon',
	props: {
		title: {
			type: String,
			default: 'Circles',
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
		size: {
			type: Number,
			default: 24,
		},
	},
}
</script>
