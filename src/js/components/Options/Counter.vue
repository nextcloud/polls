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
		<div class="counter--icon">
			<div class="yes">
				<span>{{ option.yes }}</span>
			</div>
			<div v-if="showMaybe" class="maybe">
				<span>{{ option.maybe }}</span>
			</div>
			<div v-if="showNo" class="no">
				<span>{{ option.no }}</span>
			</div>
		</div>
	</div>
</template>

<script>

export default {
	name: 'Counter',

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		showMaybe: {
			type: Boolean,
			default: false,
		},
		showNo: {
			type: Boolean,
			default: false,
		},
	},
}

</script>

<style lang="scss">
.counter {
	display: flex;
	justify-content: space-around;
}

.counter--icon {
	display: flex;
	justify-content: center;
	font-size: 1.1em;

	&> * {
		background-position: 0px 2px;
		padding-left: 23px;
		background-repeat: no-repeat;
		background-size: contain;
		margin: 4px;
	}

	.yes {
		color: var(--color-polls-foreground-yes);
		background-image: var(--icon-polls-yes);
	}
	.no {
		color: var(--color-polls-foreground-no);
		background-image: var(--icon-polls-no);
	}
	.maybe {
		color: var(--color-polls-foreground-maybe);
		background-image: var(--icon-polls-maybe);
	}
}

.list-view .counter {
	flex: 0;
	.counter--icon {
		flex-direction: column;
	}
}
</style>
