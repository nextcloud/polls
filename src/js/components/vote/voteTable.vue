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

<template lang="html">
	<div class="vote-table">
		<transition-group v-if="event.type === 'datePoll'" name="list" tag="div"
			class="header">
			<vote-header v-for="(option) in sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="event.type"
				:mode="poll.mode"
				@remove="removeOption(option)" />
		</transition-group>

		<transition-group v-if="event.type === 'textPoll'" name="list" tag="div"
			class="header">
			<vote-header v-for="(option) in sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="event.type"
				:mode="poll.mode"
				@remove="removeOption(option)" />
		</transition-group>

		<ul class="participants">
			<div v-for="(participant) in participants" :key="participant" :class="{currentUser: (participant === currentUser) }">
				<user-div :key="participant"
					:class="{currentUser: (participant === currentUser) }"
					:user-id="participant"
					:fixed-width="true" />
				<transition-group name="list" tag="ul" class="vote-row">
					<vote-item v-for="(option) in sortedOptions"
						:key="option.id"
						:user-id="participant"
						:option="option"
						@voteSaved="voteSaved(vote)" />
				</transition-group>
			</div>
		</ul>
	</div>
</template>

<script>
import VoteItem from './voteItem'
import VoteHeader from './voteHeader'
import { mapState, mapGetters, mapActions } from 'vuex'

export default {
	name: 'VoteTable',
	components: {
		VoteHeader,
		VoteItem
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			event: state => state.event
		}),

		...mapGetters([
			'sortedOptions',
			'participants'
		]),

		currentUser() {
			return OC.getCurrentUser().uid
		}

	},

	methods: {
		...mapActions([
			'removeOption'
		]),

		voteSaved() {
			this.$emit('voteSaved')
		}
	}
}
</script>

<style lang="scss" scoped>
	* {
		display: flex;
	}

	.vote-table {
		flex: 0;
		flex-direction: column;
		justify-content: flex-start;

		.participants {
			flex-direction: column;
			flex: 1 0;

			& > div {
				flex: 1;
				order: 2;
				border-bottom: 1px solid var(--color-border-dark);
				height: 44px;
				padding: 0 17px;
				&.currentUser {
					order: 1;
				}
			}
		}

		.header {
			height: 150px;
			padding-left: 187px;
			padding-right: 17px;
			align-items: center;
			border-bottom: 1px solid var(--color-border-dark);
			& > div {
				flex: 1;
			}
		}

		.vote-row {
			justify-content: space-between;
			flex: 1 1 auto;
		}
	}
</style>
