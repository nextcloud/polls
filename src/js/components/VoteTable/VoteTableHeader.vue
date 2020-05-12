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
	<div class="vote-table-header" :class=" { winner: isWinner, confirmed: isConfirmed }">
		<OptionItem :option="option" :type="poll.type" :display="tableMode ? 'dateBox' : 'textBox'" />
		<div class="counter">
			<div class="yes">
				<span>{{ option.yes }}</span>
			</div>
			<div v-if="poll.allowMaybe" class="maybe">
				<span>{{ option.maybe }}</span>
			</div>
		</div>

		<div class="counter2">
			<div class="no" :style="{flex: option.no }">
				<span />
			</div>

			<div v-if="option.maybe && poll.allowMaybe" class="maybe" :style="{flex: option.maybe }">
				<span> {{ option.maybe }} </span>
			</div>

			<div v-if="option.yes" class="yes" :style="{ flex: option.yes }">
				<span> {{ option.yes }} </span>
			</div>
		</div>
		<div v-if="expired && !acl.allowEdit" class="confirmations">
			{{ confirmations }}
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionItem from '../Base/OptionItem'

export default {
	name: 'VoteTableHeader',

	components: {
		OptionItem,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		pollType: {
			type: String,
			default: undefined,
		},
		tableMode: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			votes: state => state.votes.votes,
			acl: state => state.acl,
		}),

		...mapGetters([
			'votesRank',
			'participantsVoted',
			'expired',
			'confirmedOptions',
		]),
		isWinner() {
			// highlight best option until poll is expired and at least one option is confirmed
			return this.option.rank === 1 && !(this.expired && this.confirmedOptions.length)
		},
		isConfirmed() {
			return this.option.confirmed && this.expired
		},
		confirmations() {
			if (this.isConfirmed) {
				return t('polls', 'Confirmed')
			} else {
				return ' '
			}
		},
	},

	methods: {
		confirmOption(option) {
			this.$store.dispatch('updateOptionAsync', { option: { ...option, confirmed: !option.confirmed } })
		},
	},
}

</script>

<style lang="scss">

.vote-table-header {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	justify-content: center;
	background-color: var(--color-main-background);
	&.winner {
		font-weight: bold;
		color: var(--color-polls-foreground-yes);
	}
	&.confirmed {
		font-weight: bold;
		border-top: 1px solid var(--color-polls-foreground-yes);
		border-radius: 10px 10px 0 0;
		border-bottom: 0;
		padding: 8px 8px 2px 8px;
	}
	.option-item {
		flex: 1;
		.option-item__option--text {
			hyphens: auto;
		}
	}
	.counter {
		flex: 0;
	}
	.counter2 {
		display: none;
		flex: 0;
	}
}

.counter {
	display: flex;
	justify-content: center;
	font-size: 1.1em;
	padding: 14px 4px;

	&> * {
		background-position: 0px 2px;
		padding-left: 23px;
		background-repeat: no-repeat;
		background-size: contain;
		margin-right: 8px;
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

.counter2 {
	display: flex;
	width: 80px;
	flex: 1;
	align-self: center;

	> * {
		text-align: center;
		border-radius: 21px;
		margin: 2px;
	}

	.yes {
		background-color: var(--color-polls-foreground-yes);
	}

	.maybe {
		background-color: var(--color-polls-foreground-maybe);
	}

}

.confirmations {
	text-align: center;
	height: 2em;
}

.confirmAction {
	font-size: 80%;
}

</style>
