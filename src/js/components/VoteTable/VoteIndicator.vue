<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template lang="html">
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
	&, * {
		transition: all 0.4s ease-in-out;
		.active & {
			cursor: pointer;
		}
	}

	display: flex;
	justify-content: center;
	align-content: end;
	color: var(--color-polls-foreground-no);
	width: 30px;
	height: 30px;

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
