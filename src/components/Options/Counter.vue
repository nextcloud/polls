<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import YesCounterIcon from 'vue-material-design-icons/Check.vue'
import MaybeCounterIcon from 'vue-material-design-icons/Tilde.vue'
import CheckboxMarkedOutlinedIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'

import { usePollStore } from '../../stores/poll'

import type { Option } from '../../stores/options.types'
import VotersList from '../VoteTable/VotersList.vue'
import { NcPopover } from '@nextcloud/vue'

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
		<NcPopover no-focus-trap class="yes">
			<template #trigger>
				<YesCounterIcon
					fill-color="var(--color-polls-foreground-yes)"
					:size="20" />
				<span>{{ option.votes.yes }}</span>
			</template>
			<VotersList :option="option" answer-filter="yes" />
		</NcPopover>
		<NcPopover v-show="showMaybe" no-focus-trap class="maybe">
			<template #trigger>
				<MaybeCounterIcon
					fill-color="var(--color-polls-foreground-maybe)"
					:size="20" />
				<span>{{ option.votes.maybe }}</span>
			</template>
			<VotersList :option="option" answer-filter="maybe" />
		</NcPopover>
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
