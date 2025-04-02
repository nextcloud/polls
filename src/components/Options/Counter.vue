<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { PropType } from 'vue'
import YesCounterIcon from 'vue-material-design-icons/AccountCheck.vue'
import MaybeCounterIcon from 'vue-material-design-icons/AccountCheckOutline.vue'
import { Option } from '../../Types/index.ts'

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		required: true,
	},
	showMaybe: {
		type: Boolean,
		default: false,
	},
})
</script>

<template>
	<div class="counter">
		<div class="yes">
			<YesCounterIcon
				fill-color="var(--color-polls-foreground-yes)"
				:size="20" />
			<span>{{ props.option.votes.yes }}</span>
		</div>
		<div v-if="showMaybe" class="maybe">
			<MaybeCounterIcon
				fill-color="var(--color-polls-foreground-maybe)"
				:size="20" />
			<span>{{ props.option.votes.maybe }}</span>
		</div>
	</div>
</template>

<style lang="scss">
.counter {
	display: flex;
	align-items: center;
	justify-content: space-around;
	gap: 4px 16px;
	padding: 0 12px;
	font-size: 1.1em;

	div {
		display: flex;
		align-items: center;
		justify-content: space-around;
	}

	.yes {
		color: var(--color-polls-foreground-yes);
	}

	.no {
		color: var(--color-polls-foreground-no);
	}

	.maybe {
		color: var(--color-polls-foreground-maybe);
	}
}
</style>
