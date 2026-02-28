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
	tag?: string
}

const {
	option,
	draggable = false,
	showOwner = false,
	tag = 'div',
} = defineProps<Props>()

const containerClass = {
	'option-item': true,
	deleted: option.deleted !== 0,
	draggable,
}

const pollStore = usePollStore()
</script>

<template>
	<component :is="tag" :class="containerClass">
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
			:start-date="DateTime.fromISO(option.isoTimestamp)"
			:duration="Duration.fromISO(option.isoDuration)" />

		<slot name="actions" />
	</component>
</template>

<style lang="scss">
.option-item {
	display: grid;
	grid-template-columns: auto 1fr auto auto;
	grid-template-areas: 'drag option owner actions';
	align-items: center;
	position: relative;
	padding: 8px 0;
	background-color: var(--color-main-background);

	.confirmed & {
		background-color: var(--color-polls-background-yes);
		border-radius: var(--border-radius-container);
		border: 2px solid var(--color-border-success);
	}

	.list-view .confirmed & {
		padding-inline: 0.5rem;
		inset-inline-start: -0.5rem;
	}

	.side-bar-tab-options & {
		border-bottom: 1px solid var(--color-border);

		&:active,
		&:hover {
			transition: var(--background-dark) 0.3s ease;
			background-color: var(--color-background-dark);
		}
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
	overflow: clip;
	text-overflow: ellipsis;
	align-self: center;
	text-wrap: balance;
	hyphens: auto;
	display: -webkit-box !important;
	line-clamp: 2;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	max-height: 4em;
	transition: all 0.3s ease-in-out;

	.table-view & {
		text-align: center;
		margin: auto;
		-webkit-line-clamp: 6;
		line-clamp: 6;
		max-height: 12em;
		padding: 0 0.6em;
	}

	/* Unclamp on hover or active, unless dragging */
	:not(.sortable-chosen) & {
		&:hover,
		&:active {
			-webkit-line-clamp: initial;
			line-clamp: initial;
			max-height: 30vh;
			overflow-y: scroll;
		}
	}
	.sortable-chosen & {
		&:active {
			-webkit-line-clamp: 2;
			line-clamp: 2;
			max-height: 6rem;
		}
	}

	a {
		font-weight: bold;
		text-decoration: underline;
	}
}

.option-item__handle {
	margin-inline-end: 0.5em;
}
</style>
