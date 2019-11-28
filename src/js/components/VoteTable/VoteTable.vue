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
		<div class="header">
			<div class="sticky" />

			<div v-if="noOptions" class="noOptions">
				<h2> {{ t('polls', 'there are no vote Options') }} </h2>
			</div>

			<VoteTableHeader v-for="(option) in sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="event.type"
				:mode="poll.mode" />
		</div>

		<div v-for="(participant) in participants" :key="participant" :class="{currentuser: (participant === currentUser) }">
			<UserDiv :key="participant"
				class="sticky"
				:class="{currentuser: (participant === currentUser) }"
				:user-id="participant" />
			<VoteTableItem v-for="(option) in sortedOptions"
				:key="option.id"
				:user-id="participant"
				:option="option"
				@voteClick="setVote(option, participant)" />
		</div>
	</div>
</template>

<script>
import VoteTableItem from './VoteTableItem'
import VoteTableHeader from './VoteTableHeader'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteTable',
	components: {
		VoteTableHeader,
		VoteTableItem
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
		},
		noOptions() {
			return (this.sortedOptions.length === 0)
		}
	},

	methods: {
		setVote(option, participant) {
			let nextAnswer = this.$store.getters.getNextAnswer({
				option: option,
				userId: participant
			})
			this.$store
				.dispatch('setVoteAsync', {
					option: option,
					userId: participant,
					setTo: nextAnswer
				})
				.then(() => {
					this.$emit('voteSaved')
				})
		}
	}
}
</script>

<style lang="scss" scoped>
	.user-row.sticky,
	.header > .sticky {
		position: sticky;
		left: 0;
		background-color: var(--color-main-background);
		width: 170px;
		flex: 0 0 auto;
	}

	.header {
		height: 150px;
	}
	.user {
		height: 44px;
		padding: 0 17px;
	}
	.vote-table {
		display: flex;
		flex: 0;
		flex-direction: column;
		justify-content: flex-start;
		overflow: scroll;

		& > div {
			display: flex;
			flex: 1;
			border-bottom: 1px solid var(--color-border-dark);
			order: 3;
			justify-content: space-between;
			min-width: max-content;

			& > div {
				width: 84px;
				min-width: 84px;
				flex: 1;
				margin: 2px;
			}

			& > .vote-header {
				flex: 1;
			}

			&.header {
				order: 1;
			}

			&.currentuser {
				order: 2;
			}
		}

		.vote-row {
			display: flex;
			justify-content: space-around;
			flex: 1;
			align-items: center;
		}
	}

	@media (max-width: (480px)) {
		.vote-table {
			flex: 1 0;
			flex-direction: row;
			min-width: 300px;

			&> div {
				display: none;
				&> div {
					width: unset;
					margin: 0;

				}
				// &.currentuser {
				// 	display: flex;
				// 	> .user-row.currentuser {
				// 		display: none;
				// 	}
				// }
			}

			&> .currentuser {
				display: flex;
				flex-direction: column;
				&> .user-row {
					display: none;
				}
			}

			&> .header, {
				height: initial;
				padding-left: initial;
				display: flex;
				flex-direction: column;
				flex: 3 1;
				justify-content: space-around;
				align-items: stretch;
				&> .vote-header {
					display: flex;
					flex-direction: row;
					&> .counter {
						align-items: baseline;
					}
				}
			}
		}
	}
</style>
