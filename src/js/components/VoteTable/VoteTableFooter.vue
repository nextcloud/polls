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
	<div class="vote-table-footer" :class=" { confirmed: isConfirmed }">
		<div class="footer">
			{{ confirmations }}
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteTableHeader',

	props: {
		option: {
			type: Object,
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
		}),

		...mapGetters([
			'votesRank',
			'participantsVoted',
			'expired',
			'confirmedOptions',
		]),
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
}

</script>

<style lang="scss">

.vote-table-footer {
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
		border-bottom: 1px solid var(--color-polls-foreground-yes);
		border-radius: 0 0 10px 10px;
		padding: 8px 8px 2px 8px;
	}
}

.footer {
	text-align: center;
	height: 2em;
}

</style>
