<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import YesCounterIcon from 'vue-material-design-icons/AccountCheck.vue'
import MaybeCounterIcon from 'vue-material-design-icons/AccountCheckOutline.vue'
import CheckboxMarkedOutlinedIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import { Option } from '../../Types/index.ts'
import { usePollStore } from '../../stores/poll.ts'

const pollStore = usePollStore()
interface Props {
	option: Option
	showMaybe?: boolean
}

const { option, showMaybe = false } = defineProps<Props>()
</script>

<template>
	<div v-if="option.confirmed && pollStore.status.isExpired" class="counter">
		<CheckboxMarkedOutlinedIcon :size="20" />
	</div>
	<div v-else class="counter">
		<div class="yes">
			<YesCounterIcon
				fill-color="var(--color-polls-foreground-yes)"
				:size="20" />
			<span>{{ option.votes.yes }}</span>
		</div>
		<div v-show="showMaybe" class="maybe">
			<MaybeCounterIcon
				fill-color="var(--color-polls-foreground-maybe)"
				:size="20" />
			<span>{{ option.votes.maybe }}</span>
		</div>
	</div>
</template>

<style lang="scss">
.counter {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.25rem 1rem;
	padding: 0 0.75rem;
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
