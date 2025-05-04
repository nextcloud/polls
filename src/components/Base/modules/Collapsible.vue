<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'

const props = defineProps({
	initialCollapsed: {
		type: Boolean,
		default: false,
	},
	showMoreCaption: {
		type: String,
		default: t('polls', 'Show more'),
	},
	closeCaption: {
		type: String,
		default: t('polls', 'Collapse'),
	},
	noCollapse: {
		type: Boolean,
		default: false,
	},
})

const showMore = ref(!props.initialCollapsed || props.noCollapse)
</script>

<template>
	<div class="collapsible">
		<div
			id="collapsible_container"
			:class="['collapsible_container', { open: showMore || noCollapse }]">
			<slot />
		</div>
		<div
			v-show="!noCollapse"
			:class="['collapsible-toggle', { open: showMore }]"
			@click="showMore = !showMore">
			{{ showMore ? props.closeCaption : props.showMoreCaption }}
		</div>
	</div>
</template>

<style lang="scss">
.collapsible {
	overflow: hidden;

	.collapsible-toggle {
		cursor: pointer;
		position: relative;
		line-height: 2rem;
		font-weight: bold;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 100%;
		background-color: var(--color-background-plain);
		color: var(--color-primary-element-text);
		border-radius: var(--border-radius-element);

		&::before {
			content: '\25B8';
			margin: 0 0.3em;
			display: inline-block;
			transform: rotate(90deg);
			transition: transform 0.3s ease-in-out;
		}
		&.open {
			&::before {
				transform: rotate(-90deg);
			}
		}
	}

	.collapsible_container {
		transition: max-height 0.3s ease-in-out;
		overflow: auto;
		max-height: 0;

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
		}
	}
}
</style>
