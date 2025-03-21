<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { PropType } from 'vue'
import linkifyStr from 'linkify-string'
import DragIcon from 'vue-material-design-icons/DragHorizontalVariant.vue'
import { Option, PollType, BoxType } from '../../Types/index.ts'
import OptionItemDateBox from './OptionItemDateBox.vue'

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
	display: {
		type: String as PropType<BoxType>,
		default: BoxType.Text,
	},
	pollType: {
		type: String as PropType<PollType>,
		default: PollType.Text,
	},
})
</script>

<template>
	<Component
		:is="tag"
		class="option-item"
		:class="{ draggable: props.draggable, deleted: props.option.deleted !== 0 }">
		<DragIcon v-if="props.draggable" :class="{ draggable: props.draggable }" />

		<slot name="icon" />

		<!-- eslint-disable vue/no-v-html -->
		<div
			v-if="props.pollType === PollType.Text"
			:title="option.text"
			class="option-item__option--text"
			v-html="linkifyStr(props.option.text)" />
		<!-- eslint-enable vue/no-v-html -->

		<OptionItemDateBox
			v-if="props.pollType === PollType.Date"
			:display="props.display"
			:option="props.option" />

		<slot name="actions" />
	</Component>
</template>

<style lang="scss">
.option-item {
	display: flex;
	align-items: center;
	flex: 1;
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
	cursor: grab;
	&:active {
		cursor: grabbing;
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
