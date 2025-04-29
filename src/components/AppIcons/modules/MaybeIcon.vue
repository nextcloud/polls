<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
const props = defineProps({
	title: {
		type: String,
		default: 'Maybe',
	},
	fillColor: {
		type: String,
		default: 'var(--color-warning)',
	},
	size: {
		type: Number,
		default: 24,
	},
})
</script>

<template>
	<span
		:aria-hidden="!props.title"
		:aria-label="props.title"
		class="material-design-icon polls-maybe-icon"
		role="img">
		<svg
			:fill="props.fillColor"
			:stroke="props.fillColor"
			class="material-design-icon__svg"
			:width="props.size"
			:height="props.size"
			viewBox="0 0 16 16">
			<g
				id="open-brace"
				style="
					font-style: normal;
					font-weight: normal;
					font-size: 10.03817844px;
					line-height: 1.25;
					font-family: sans-serif;
					letter-spacing: 0px;
					word-spacing: 0px;
					fill-opacity: 0.94117647;
					stroke: none;
					stroke-width: 0.6844213;
				"
				transform="matrix(1.2792345,0,0,1.0763308,-0.2918408,-1.3872412)"
				aria-label="(">
				<path
					id="path853"
					style="
						font-size: 9.81086349px;
						fill-opacity: 0.94117647;
						stroke-width: 0.6844213;
					"
					d="m 3.7425891,4.3526202 q -0.6419218,1.101806 -0.9533017,2.1796596 -0.31138,1.0778537 -0.31138,2.1844501 0,1.1065964 0.31138,2.1940311 0.3161704,1.082644 0.9533017,2.17966 H 2.9761153 Q 2.2575462,11.964662 1.8982617,10.877228 1.5437676,9.7897931 1.5437676,8.7167299 q 0,-1.0682727 0.3544941,-2.1509169 0.3544941,-1.0826441 1.0778536,-2.2131928 z" />
			</g>
			<path
				id="checkmark"
				d="M 4.2292965,7.4561992 6.8988209,10.399892 11.770704,5.0276561"
				style="
					fill: none;
					fill-opacity: 1;
					stroke-width: 1.40162849;
					stroke-opacity: 0.94117647;
				" />
			<g
				id="close-brace"
				style="
					font-style: normal;
					font-weight: normal;
					font-size: 10.03817844px;
					line-height: 1.25;
					font-family: sans-serif;
					letter-spacing: 0px;
					word-spacing: 0px;
					fill-opacity: 0.94117647;
					stroke: none;
					stroke-width: 0.6844213;
				"
				transform="matrix(-1.2792345,0,0,-1.0763308,-2.4031246,-1.3872405)"
				aria-label="(">
				<path
					id="path856"
					style="
						font-size: 9.81086349px;
						fill-opacity: 0.94117647;
						stroke-width: 0.6844213;
					"
					d="m -10.871757,-13.09042 q -0.641921,1.101806 -0.953301,2.179659 -0.31138,1.0778541 -0.31138,2.1844505 0,1.1065964 0.31138,2.194031 0.31617,1.0826441 0.953301,2.1796596 h -0.766473 q -0.718569,-1.1257582 -1.077854,-2.2131928 -0.354494,-1.0874346 -0.354494,-2.1604978 0,-1.0682727 0.354494,-2.1509165 0.354494,-1.082644 1.077854,-2.213193 z" />
			</g>
		</svg>
	</span>
</template>
