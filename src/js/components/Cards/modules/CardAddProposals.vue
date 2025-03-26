<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'You are asked to propose more options. ') }}
		<p v-if="isProposalExpirySet && !isProposalExpired">
			{{ t('polls', 'The proposal period ends {timeRelative}.',
				{ timeRelative: proposalsExpireRelative }) }}
		</p>
		<OptionProposals v-if="pollType === 'textIndPoll'" />
		<OptionProposals v-else-if="pollType === 'textRankPoll'" />
		<template #button>
			<OptionProposals v-if="pollType === 'datePoll'" />
		</template>
	</CardDiv>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { CardDiv } from '../../Base/index.js'
import OptionProposals from '../../Options/OptionProposals.vue'

export default {
	name: 'CardAddProposals',
	components: {
		CardDiv,
		OptionProposals,
	},

	data() {
		return {
			cardType: 'info',
		}
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			isProposalExpirySet: 'poll/isProposalExpirySet',
			isProposalExpired: 'poll/isProposalExpired',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
		}),
	},
}
</script>
