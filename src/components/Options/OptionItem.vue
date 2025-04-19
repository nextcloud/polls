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
	tag: {
		type: String,
		default: 'div',
	},
})
</script>

<template>
	<Component
		:is="tag"
		:class="[
			'option-item',
			{
				draggable: props.draggable,
				deleted: props.option.deleted !== 0,
			},
		]">
		<DragIcon v-if="props.draggable" />

		<slot name="icon" />

		<!-- eslint-disable vue/no-v-html -->
		<div
			v-if="pollStore.type === PollType.Text"
			:title="option.text"
			class="option-item__option--text"
			v-html="linkifyStr(props.option.text)" />
		<!-- eslint-enable vue/no-v-html -->

		<DateBox v-else :option="props.option" />

		<slot name="actions" />

	</Component>
</template>

<style lang="scss">
.option-item {
	position: relative;
	padding: 8px 0;

	&.deleted {
		opacity: 0.6;
	}
}

[class*='option-item__option'] {
	flex: 1;
	opacity: 1;
	white-space: normal;
}

.deleted {
	[class*='option-item__option']::after {
		content: var(--content-deleted);
		font-weight: bold;
		color: var(--color-error-text);
	}
}

.option-item__option--text {
	overflow: hidden;
	text-overflow: ellipsis;

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
