<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { PropType } from 'vue'
import linkifyStr from 'linkify-string'
import DragIcon from 'vue-material-design-icons/DotsVertical.vue'
import { Option, PollType } from '../../Types/index.ts'
import DateBox from '../Base/modules/DateBox.vue'
import { usePollStore } from '../../stores/poll.ts'
import OptionItemOwner from './OptionItemOwner.vue'

const pollStore = usePollStore()

const props = defineProps({
	draggable: {
		type: Boolean,
		default: false,
	},
	option: {
		type: Object as PropType<Option>,
		required: true,
	},
	showOwner: {
		type: Boolean,
		default: false,
	},
})
</script>

<template>
	<div :class="['option-item', { draggable, deleted: option.deleted !== 0 }]">
		<DragIcon v-if="props.draggable" class="grid-area-drag-icon" />

		<OptionItemOwner
			v-if="pollStore.permissions.addOptions && showOwner"
			:avatar-size="24"
			:option="option"
			class="grid-area-owner" />

		<!-- eslint-disable vue/no-v-html -->
		<div
			v-if="pollStore.type === PollType.Text"
			:title="option.text"
			class="option-item__option--text"
			v-html="linkifyStr(option.text)" />
		<!-- eslint-enable vue/no-v-html -->

		<DateBox v-else class="option-item__option--date" :option="option" />

		<slot name="actions" />
	</div>
</template>

<style lang="scss">
.option-item {
	display: grid;
	grid-template-columns: auto 1fr auto auto;
	grid-template-areas: 'drag option owner actions';
	position: relative;
	padding: 8px 0;
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
		margin-right: 0.2rem;
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
	margin-right: 0.5em;
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
		padding-right: 0;
		transition: all 0.3s ease-in-out;
	}

	&:active,
	&:hover {
		.material-design-icon.draggable {
			width: initial;
			padding-right: 0.5px;
		}
	}
}
</style>
