<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, useTemplateRef } from 'vue'
import { onClickOutside } from '@vueuse/core'

interface Props {
	noCollapse?: boolean
	openOnClick?: boolean
}

const { noCollapse = false, openOnClick = true } = defineProps<Props>()

const open = defineModel<boolean>('open', { default: true })

const collapsed = computed(() => {
	if (noCollapse) {
		return false
	}
	return !open.value
})

const collapsibleContainer = useTemplateRef<HTMLElement>('collapsible_container')
const useToggle = computed(() => !noCollapse && !openOnClick)

function toggleCollapsible(collapse: null | boolean = null) {
	open.value = (collapse ?? !open.value) && !noCollapse
}

onClickOutside(collapsibleContainer, () => {
	toggleCollapsible(false)
})
</script>

<template>
	<div :class="['collapsible', { collapsed }]">
		<div
			v-if="useToggle"
			class="collapsible-toggle"
			@click="toggleCollapsible()"></div>
		<div
			ref="collapsible_container"
			class="collapsible_container"
			@click="toggleCollapsible(true)">
			<slot />
		</div>
	</div>
</template>

<style lang="scss">
.collapsible {
	display: flex;

	.collapsible_container {
		// transition: max-height 0.3s ease-in-out;
		transition: max-height 0.4s cubic-bezier(1, 0, 0, 1);
		overflow: auto;
		max-height: max(51vh, 12rem);

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
	}

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
			transform: rotate(90deg);
		}
		.collapsed & {
			&::before {
				transform: rotate(0deg);
			}
		}
	}

	&.collapsed {
		.collapsible-toggle {
			&::before {
				transform: rotate(0deg);
			}
		}
		.collapsible_container {
			max-height: 6rem;
		}
	}
}
</style>
