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
	<div class="vote-column">
		<VoteTableHeaderItem :option="option" :view-mode="viewMode" />
		<div v-for="(poll) in polls"
			:key="poll.id"
			v-tooltip.auto="poll.title"
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
import VoteItem from './VoteItem'
import VoteTableHeaderItem from './VoteTableHeaderItem'

export default {
	name: 'VoteColumn',
	components: {
		VoteTableHeaderItem,
		VoteItem,
	},

	props: {
		viewMode: {
			type: String,
			default: 'table-view',
		},
		option: {
			type: Object,
			default: undefined,
		},
	},

	computed: {
		...mapGetters({
			participantsByPoll: 'combo/participantsInPoll',
			optionBelongsToPoll: 'combo/optionBelongsToPoll',
		}),
		...mapState({
			polls: (state) => state.combo.polls,
			participants: (state) => state.combo.participants,
		}),
	},
}
</script>
