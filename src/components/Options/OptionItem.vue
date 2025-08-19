<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import linkifyStr from 'linkify-string'
import DragIcon from 'vue-material-design-icons/DotsVertical.vue'
import DateBox from '../Base/modules/DateBox.vue'
import { usePollStore } from '../../stores/poll'
import OptionItemOwner from './OptionItemOwner.vue'
import { DateTime, Duration } from 'luxon'

import type { Option } from '../../stores/options.types'

interface Props {
	option: Option
	draggable?: boolean
	showOwner?: boolean
}

const { option, draggable = false, showOwner = false } = defineProps<Props>()

const containerClass = {
	'option-item-container': true,
	deleted: option.deleted !== 0,
	draggable,
}

const pollStore = usePollStore()
</script>

<template>
	<div :class="containerClass">
		<DragIcon v-if="draggable" class="grid-area-drag-icon" />

		<OptionItemOwner
			v-if="pollStore.permissions.addOptions && showOwner"
			:avatar-size="24"
			:option="option"
			class="grid-area-owner" />

		<!-- eslint-disable vue/no-v-html -->
		<div
			v-if="pollStore.type === 'textPoll'"
			:title="option.text"
			class="option-item__option--text"
			v-html="linkifyStr(option.text)" />
		<!-- eslint-enable vue/no-v-html -->

		<DateBox
			v-else
			class="option-item__option--date"
			:date-time="DateTime.fromSeconds(option.timestamp)"
			:duration="Duration.fromMillis(option.duration * 1000)" />

		<slot name="actions" />
	</div>
</template>

<style lang="scss">
.option-item-container {
	display: grid;
	grid-template-columns: auto 1fr auto auto;
	grid-template-areas: 'drag option owner actions';
	position: relative;
	padding: 8px 0;
	background-color: var(--color-main-background);
	.confirmed & {
		background-color: var(--color-polls-background-yes);
		border-radius: var(--border-radius-container);
		border: 2px solid var(--color-success-text);
	}
	.list-view .confirmed & {
		padding-inline: 0.5rem;
		inset-inline-start: -0.5rem;
	}
}

.grid-area-drag-icon {
	grid-area: drag;
}

[class*='option-item__option'] {
	grid-area: option;
}

.grid-area-owner {
	grid-area: owner;
}

.grid-area-actions,
.option-menu {
	grid-area: actions;
}

.deleted {
	[class*='option-item__option'] {
		opacity: 0.6;
	}

	[class*='option-item__option']::before {
		content: var(--content-deleted);
		font-weight: bold;
		color: var(--color-error-text);
		margin-inline-end: 0.2rem;
	}
}

.option-item__option--date {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 100%;
}

.option-item__option--text {
	overflow: hidden;
	text-overflow: ellipsis;
	align-self: center;
	text-wrap: balance;
	hyphens: auto;

	.table-view .option-item & {
		text-align: center;
		/* Notice: https://caniuse.com/css-text-wrap-balance */
		padding: 0 0.6em;
		margin: auto;
	}

	a {
		font-weight: bold;
		text-decoration: underline;
	}
}

.option-item__handle {
	margin-inline-end: 0.5em;
}

.draggable {
	* {
		cursor: grab;
		&:active {
			cursor: grabbing;
		}
	}

	&:active {
		cursor: -moz-grabbing;
		cursor: -webkit-grabbing;
	}

	.material-design-icon.draggable {
		width: 0;
		padding-inline-end: 0;
		transition: all 0.3s ease-in-out;
	}

	&:active,
	&:hover {
		.material-design-icon.draggable {
			width: initial;
			padding-inline-end: 0.5px;
		}
	}
}
</style>
