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
				<h2> {{ t('polls', 'there are no vote Options')}} </h2>
			</div>

			<vote-header v-for="(option) in sortedOptions"
			             :key="option.id"
			             :option="option"
			             :poll-type="event.type"
			             :mode="poll.mode"
			             @remove="removeOption(option)" />
		</div>

		<div v-for="(participant) in participants" :key="participant" :class="{currentUser: (participant === currentUser) }">
			<user-div :key="participant"
			          class="sticky"
			          :class="{currentUser: (participant === currentUser) }"
			          :user-id="participant" />
			<vote-item v-for="(option) in sortedOptions"
			           :key="option.id"
			           :user-id="participant"
			           :option="option"
			           @voteSaved="voteSaved(vote)" />
		</div>
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
			VoteItem,
		},

		computed: {
			...mapState({
				poll: state => state.poll,
				event: state => state.event,
			}),

			...mapGetters(['sortedOptions', 'participants']),

			currentUser() {
				return OC.getCurrentUser().uid
			},
			noOptions() {
				return (this.sortedOptions.length === 0)
			}
		},

		methods: {
			...mapActions(['removeOption']),

			voteSaved() {
				this.$emit('voteSaved')
			},
		},
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

			&.currentUser {
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
			flex-direction: row;
			flex: 1 0;
			min-width: 300px;

			.header {
				height: initial;
				padding-left: initial;
				flex: 3 1;
				flex-direction: column;
				justify-content: space-around;
				align-items: stretch;
			}

			.vote-row {
				flex-direction: column;
			}

			.participants > div {
				display: none;
				&.currentUser {
					display: flex;
					> .user-row.currentUser {
						display: none;
					}
				}
			}
		}
	}
</style>
