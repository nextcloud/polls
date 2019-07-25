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
	<li v-if="edit" class="poll-cell active" :class="iconClass"
		@click="changeVote(option)"
	/>
	<li v-else class="poll-cell" :class="iconClass" />
</template>

<script>
export default {
	name: 'VoteItem',

	props: {
		option: {
			type: Object,
			default: undefined
		},
		pollType: {
			type: String,
			default: undefined
		},
		edit: {
			type: Boolean,
			default: false
		}
	},

	computed: {
		iconClass() {
			if (this.option.voteAnswer === 'yes') {
				return 'yes icon-yes '
			} else if (this.option.voteAnswer === 'maybe') {
				return 'maybe icon-maybe '
			} else if (this.option.voteAnswer === 'no') {
				return 'no icon-no '
			} else {
				return ''
			}
		}
	},
	methods: {
		changeVote(payload) {
			this.$emit('voteClick')
		}
	}
}
</script>

<style lang="scss">
	$bg-no: #ffede9;
	$bg-maybe: #fcf7e1;
	$bg-unvoted: #fff4c8;
	$bg-yes: #ebf5d6;

	$fg-no: #f45573;
	$fg-maybe: #f0db98;
	$fg-unvoted: #f0db98;
	$fg-yes: #49bc49;

	.poll-cell {
		background-position: center;
		background-repeat: no-repeat;
		background-size: 32px;
		height: 43px;
		display: flex;
		flex: 1;
		width: 85px;
		// min-width: 85px;
		margin: 2px;
		align-items: center;
		background-color: var(--color-background-dark);
		color: var(--color-main-text);
		background-image: var(--icon-close-000);

		&.yes {
			background-color: $bg-yes;
			color: $fg-yes;
			background-image: var(--icon-checkmark-49bc49);
		}

		&.no {
			background-color: $bg-no;
			color: $fg-no;
			background-image: var(--icon-close-f45573);
		}

		&.maybe {
			background-color: $bg-maybe;
			color: $fg-maybe;
			background-image: var(--icon-polls-maybe-vote-variant-f0db98);
		}

		&.unvoted {
			background-color: $bg-no;
			color: $fg-no;
			&:before {
				content: attr(data-unvoted);
				color: $fg-no;
				font-size: 11px;
				font-weight: bold;
				line-height: 25px;
			}
		}

		&.active {
			cursor: pointer;
			border: 2px solid;
			border-radius: var(--border-radius);
			box-sizing: border-box;
			width: 30px;
			// min-width: 30px;
			height: 30px;
			background-size: 20px;
			margin: 9px auto !important;
			background-color: var(--color-main-background);
			// color: var(--color-primary);
			flex: 0 auto !important;
			// box-shadow: 2px 2px 2px gray;
			&.icon-no {
				background-image: initial;
			}
			&.unvoted {
				background-color: $bg-maybe;
				color: $fg-maybe;
			}
			&:active {
				box-shadow: inherit;
			}
		}
	}
</style>
