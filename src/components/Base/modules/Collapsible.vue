<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
	initialCollapsed?: boolean
	noCollapse?: boolean
}
const {
	initialCollapsed = false,
	noCollapse = false,
} = defineProps<Props>()

const showMore = ref(!initialCollapsed || noCollapse)
</script>

<template>
	<div class="collapsible">
		<div
			v-show="!noCollapse"
			:class="['collapsible-toggle', { open: showMore }]"
			@click="showMore = !showMore">
		</div>
		<div
			id="collapsible_container"
			:class="['collapsible_container', { open: showMore || noCollapse }]">
			<slot />
		</div>
	</div>
</template>

<style lang="scss">
.collapsible {
	display: flex;

	.collapsible-toggle {
		cursor: pointer;
		position: relative;
		line-height: 2rem;
		font-weight: bold;
		white-space: nowrap;
		text-overflow: ellipsis;
		max-width: 100%;
		padding: 0.5rem 1rem;

		&::before {
			content: '\25B8';
			font-size: 1.5rem;
			margin: 0 0.3em;
			display: inline-block;
			transition: transform 0.3s ease-in-out;
		}
		&.open {
			&::before {
				transform: rotate(90deg);
			}
		}
	}

	.collapsible_container {
		transition: max-height 0.3s ease-in-out;
		max-height: 6rem;
		overflow: hidden;

		background:
		    /* Shadow covers */
			linear-gradient(
				var(--color-main-background) 30%,
				rgba(from var(--color-main-text) r g b / 0)
			),
			linear-gradient(
					rgba(from var(--color-main-text) r g b / 0),
					var(--color-main-background) 70%
				)
				0 100%,
			/* Shadows */
				radial-gradient(
					50% 0,
					farthest-side,
					rgba(from var(--color-main-text) r g b / 0.2),
					rgba(from var(--color-main-background) r g b / 0.2)
				),
			radial-gradient(
					50% 100%,
					farthest-side,
					rgba(from var(--color-main-text) r g b / 0.2),
					rgba(from var(--color-main-background) r g b / 0.2)
				)
				0 100%;

		background:
    		/* Shadow covers */
			linear-gradient(
				var(--color-main-background) 30%,
				rgba(from var(--color-main-text) r g b / 0)
			),
			linear-gradient(
					rgba(from var(--color-main-text) r g b / 0),
					var(--color-main-background) 70%
				)
				0 100%,
			/* Shadows */
				radial-gradient(
					farthest-side at 50% 0,
					rgba(from var(--color-main-text) r g b / 0.2),
					rgba(from var(--color-main-background) r g b / 0.2)
				),
			radial-gradient(
					farthest-side at 50% 100%,
					rgba(from var(--color-main-text) r g b / 0.2),
					rgba(from var(--color-main-background) r g b / 0.2)
				)
				0 100%;
		background-repeat: no-repeat;
		background-color: var(--color-main-background);
		background-size:
			100% 40px,
			100% 40px,
			100% 14px,
			100% 14px;

		/* Opera doesn't support this in the shorthand */
		background-attachment: local, local, scroll, scroll;

		&.open {
			max-height: max(51vh, 6rem);
			overflow: auto;
		}
	}
}
</style>
