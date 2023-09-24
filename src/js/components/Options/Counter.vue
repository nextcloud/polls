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

<template>
	<div class="counter">
		<div class="yes">
			<YesCounterIcon fill-color="var(--color-polls-foreground-yes)" :size="20" />
			<span>{{ option.computed.yes }}</span>
		</div>
		<div v-if="showMaybe" class="maybe">
			<MaybeCounterIcon fill-color="var(--color-polls-foreground-maybe)" :size="20" />
			<span>{{ option.computed.maybe }}</span>
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
