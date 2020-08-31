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
	<div class="vote-table-header-item"
		:class=" { winner: isWinner, confirmed: isConfirmed }">
		<OptionItem :option="option" :display="tableMode ? 'dateBox' : 'textBox'" />
		<Confirmation v-if="isConfirmed" :option="option" />
		<Counter v-else :show-maybe="Boolean(poll.allowMaybe)"
			:option="option"
			:counter-style="tableMode ? 'iconStyle' : 'barStyle'"
			:show-no="!tableMode" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionItem from '../Base/OptionItem'
import Counter from '../Base/Counter'
import Confirmation from '../Base/Confirmation'

export default {
	name: 'VoteTableHeaderItem',

	components: {
		OptionItem,
		Counter,
		Confirmation,
	},

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
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			expired: 'poll/expired',
			confirmedOptions: 'poll/options/confirmed',
		}),

		isWinner() {
			// highlight best option until poll is expired and
			// at least one option is confirmed
			return this.option.rank === 1 && !(this.expired && this.confirmedOptions.length)
		},

		isConfirmed() {
			return this.option.confirmed && this.expired
		},
	},
}

</script>

<style lang="scss" scoped>

.vote-table-header-item {
	display: flex;
	&.winner {
		.option-item {
			font-weight: bold;
			color: var(--color-polls-foreground-yes);
		}
	}
	.option-item {
		flex: 1;
	}
}

.confirmations {
	text-align: center;
	height: 2em;
}

.mobile {
	.vote-table-header-item {
		flex-direction: column;
		&.confirmed {
			flex-direction: row;
		}
	}
	.counter {
		order: 2;
	}

	.confirmation {
		background-position: left;
		order: 0;
		padding: 0 15px;
	}
}

</style>
