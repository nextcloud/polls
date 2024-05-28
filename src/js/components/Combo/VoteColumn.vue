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
	<div class="vote-column">
		<OptionItem :option="option" poll-type="datePoll" display="dateBox" />
		<div v-for="(poll) in polls"
			:key="poll.id"
			:title="poll.title"
			class="poll-group">
			<VoteItem v-for="(participant) in participantsByPoll(poll.id)"
				:key="`${participant.userId}_${participant.pollId}`"
				:poll-id="poll.id"
				:user="participant"
				:option="option" />
		</div>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import VoteItem from './VoteItem.vue'
import OptionItem from '../Options/OptionItem.vue'

export default {
	name: 'VoteColumn',
	components: {
		VoteItem,
		OptionItem,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
	},

	computed: {
		...mapGetters({
			participantsByPoll: 'combo/participantsInPoll',
		}),
		...mapState({
			polls: (state) => state.combo.polls,
		}),
	},
}
</script>

<style lang="scss">
.vote-column {
	display: flex;
	flex: 1 0 85px;
	flex-direction: column;
	align-items: stretch;
	max-width: 280px;
	border-left: 1px solid var(--color-border-dark);
	margin-bottom: 4px;
}
</style>
