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
		:class=" { winner: isWinner }">
		<OptionItem :option="option" :display="optionStyle" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionItem from '../Options/OptionItem'

export default {
	name: 'VoteTableHeaderItem',

	components: {
		OptionItem,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		viewMode: {
			type: String,
			default: 'table-view',
		},
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
			acl: (state) => state.poll.acl,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			confirmedOptions: 'options/confirmed',
		}),

		optionStyle() {
			if (this.viewMode === 'table-view') {
				return 'dateBox'
			}
			return 'textBox'

		},
		counterStyle() {
			if (this.viewMode === 'table-view') {
				return 'iconStyle'
			}
			return 'barStyle'

		},
		showNo() {
			return (this.viewMode === 'list-view')
		},
		isWinner() {
			// highlight best option until poll is closed and
			// at least one option is confirmed
			return this.option.rank === 1 && this.option.yes && !(this.closed && this.confirmedOptions.length)
		},

		isConfirmed() {
			return this.option.confirmed && this.closed
		},
	},
}

</script>
