<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="vote-column">
		<OptionItem :option="option" poll-type="datePoll" display="dateBox" />
		<div v-for="(poll) in polls"
			:key="poll.id"
			:title="poll.configuration.title"
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
	border-inline-start: 1px solid var(--color-border-dark);
	margin-bottom: 4px;
}
</style>
