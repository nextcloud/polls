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
	<div :class="['combo-table', viewMode]">
		<div class="user-column">
			<div class="spacer" />
			<div v-for="(poll) in polls"
				:key="poll.id"
				v-tooltip.auto="poll.title"
				class="poll-group">
				<div v-for="(participant) in participantsByPoll(poll.id)"
					:key="`${participant.userId}_${participant.pollId}`"
					class="participant">
					<UserItem v-bind="participant" condensed />
				</div>
			</div>
		</div>

		<transition-group name="list" tag="div" class="vote-grid">
			<VoteColumn v-for="(option) in options"
				:key="option.id"
				:option="option" />
		</transition-group>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import VoteColumn from './VoteColumn.vue'

export default {
	name: 'ComboTable',
	components: {
		VoteColumn,
	},

	props: {
		viewMode: {
			type: String,
			default: 'table-view',
			validator(value) {
				return ['table-view', 'list-view'].includes(value)
			},
		},
	},

	computed: {
		...mapState({
			polls: (state) => state.combo.polls,
		}),

		...mapGetters({
			options: 'combo/uniqueOptions',
			participantsByPoll: 'combo/participantsInPoll',
		}),
	},

}

</script>

<style lang="scss" >
.combo-title {
	margin-bottom: 16px;
}

.combo-table {
	display: flex;
	flex: 1;

	.spacer {
		flex: 1;
	}

	.poll-group {
		display: flex;
		flex-direction: column;
	}

	.participant, .vote-item {
		flex: 0 0 auto;
		height: 4.5em;
		line-height: 1.5em;
		padding: 4px;
		border-top: solid 1px var(--color-border-dark);
	}

	.user-column {
		display: flex;
		flex-direction: column;
		overflow-x: scroll;
		margin-bottom: 4px;
		.participant {
			display: flex;
			max-width: 245px;
		}
	}

	.vote-grid {
		display: flex;
		flex: 1;
		overflow-x: scroll;
	}

	.user-column::after, .vote-column::after {
		content: '';
		height: 8px;
	}
}
</style>
