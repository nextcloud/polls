<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="['vote-indicator', active]" @click="onClick()">
		<MaybeIcon v-if="answer==='maybe'" :size="iconSize" />
		<CheckIcon v-if="answer==='yes'" :fill-color="foregroundColor" :size="iconSize" />
		<CloseIcon v-if="answer==='no'" :fill-color="foregroundColor" :size="iconSize" />
	</div>
</template>

<script>

import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { MaybeIcon } from '../AppIcons/index.js'

export default {
	name: 'VoteIndicator',
	components: {
		CloseIcon,
		CheckIcon,
		MaybeIcon,
	},

	props: {
		answer: {
			type: String,
			default: '',
		},
		active: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			iconSize: 31,
			colorCodeNo: getComputedStyle(document.documentElement).getPropertyValue('--color-error'),
			colorCodeYes: getComputedStyle(document.documentElement).getPropertyValue('--color-success'),
			colorCodeMaybe: getComputedStyle(document.documentElement).getPropertyValue('--color-warning'),
		}
	},

	computed: {
		foregroundColor() {
			if (this.answer === 'yes') {
				return this.colorCodeYes
			}
			if (this.answer === 'maybe') {
				return this.colorCodeMaybe
			}
			return this.colorCodeNo
		},
	},

	methods: {
		onClick() {
			if (this.active) {
				this.$emit('click')
			}
		},
	},
}
</script>

<style lang="scss">

.vote-indicator {
	display: flex;
	justify-content: center;
	align-content: end;
	color: var(--color-polls-foreground-no);
	width: 30px;
	height: 30px;

	&, * {
		transition: all 0.4s ease-in-out;
		.active & {
			cursor: pointer;
		}
	}

	.active & {
		border: 2px solid;
		border-radius: var(--border-radius);
		.material-design-icon {
			width: 26px;
			height: 26px;
		}
	}
	.yes & {
		color: var(--color-polls-foreground-yes);
	}

	.maybe & {
		color: var(--color-polls-foreground-maybe);
	}

	.active:hover & {
		width: 35px;
		height: 35px;
		.material-design-icon {
			width: 31px;
			height: 31px;
		}

	}

}
</style>
