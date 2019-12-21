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
	<div class="voteHeader">
		<h2>
			{{ event.title }}
			<span v-if="expired" class="label error">{{ t('polls', 'Expired since %n', 1, moment.utc(event.expire).local().format('LLLL')) }}</span>
			<span v-if="!expired && event.expiration" class="label success">{{ t('polls', 'Place your votes until %n', 1, moment.utc(event.expire).local().format('LLLL')) }}</span>
			<span v-if="event.deleted" class="label error">{{ t('polls', 'Deleted') }}</span>
		</h2>
		<h3>
			{{ event.description }}
		</h3>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteHeader',

	data() {
		return {
			voteSaved: false,
			delay: 50,
			newName: ''
		}
	},

	computed: {
		...mapState({
			event: state => state.event
		}),

		...mapGetters([
			'expired'
		])

	},

	methods: {
		indicateVoteSaved() {
			this.voteSaved = true
			window.setTimeout(this.timer, this.delay)
		}
	}
}
</script>

<style lang="scss" scoped>
	.voteHeader {
		margin: 8px 24px;
	}
</style>
