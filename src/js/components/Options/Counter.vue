<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="counter">
		<div class="yes">
			<YesCounterIcon fill-color="var(--color-polls-foreground-yes)" :size="20" />
			<span>{{ option.votes.yes }}</span>
		</div>
		<div v-if="showMaybe" class="maybe">
			<MaybeCounterIcon fill-color="var(--color-polls-foreground-maybe)" :size="20" />
			<span>{{ option.votes.maybe }}</span>
		</div>
	</div>
</template>

<script>

import YesCounterIcon from 'vue-material-design-icons/AccountCheck.vue'
import MaybeCounterIcon from 'vue-material-design-icons/AccountCheckOutline.vue'

export default {
	name: 'Counter',

	components: {
		YesCounterIcon,
		MaybeCounterIcon,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		showMaybe: {
			type: Boolean,
			default: false,
		},
	},
}

</script>

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
