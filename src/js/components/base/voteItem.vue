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
	    <li :id="'voteid_' + option.id" :class="iconClass" :data-value="option.voteOptionText" />
</template>

<script>
import moment from 'moment'

export default {
	name: 'DatePollVoteItem',

	props: {
		option: {
			type: Object,
			default: undefined
		},
		pollType: {
			type: String,
			default: undefined
		}
	},

	data() {
		return {
			openedMenu: false,
			hostName: this.$route.query.page
		}

	},

	computed: {
		iconClass() {
			if (this.option.voteAnswer === "yes") {
				return "flex-column poll-cell yes icon-yes"
			} else if (this.option.voteAnswer === "maybe") {
				return "flex-column poll-cell maybe icon-maybe"
			} else {
				return "flex-column poll-cell no icon-no"
			}
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
	flex-grow: 1;
	width: 85px;
	// min-width: 85px;
	margin: 2px;
	align-items: center;

	&.yes {
		background-color: $bg-yes;
		color: $fg-yes;
	}

	&.no {
		background-color: $bg-no;
		color: $fg-no;
	}

	&.maybe {
		background-color: $bg-maybe;
		color: $fg-maybe;
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
		margin: auto !important;
		background-color: var(--color-main-background);
		// color: var(--color-primary);
		flex-grow: 0 !important;
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
