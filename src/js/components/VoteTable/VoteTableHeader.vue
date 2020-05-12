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
		<Counter :show-maybe="Boolean(poll.allowMaybe)" :option="option" :bubble-style="!tableMode" />
		<div v-if="expired && !acl.allowEdit" class="confirmations">
			{{ confirmations }}
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionItem from '../Base/OptionItem'
import Counter from '../Base/Counter'

export default {
	name: 'VoteTableHeader',

	components: {
		OptionItem,
		Counter,
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
}

.confirmations {
	text-align: center;
	height: 2em;
}

.confirmAction {
	font-size: 80%;
}

</style>
