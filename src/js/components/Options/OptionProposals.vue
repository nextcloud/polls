<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="option-proposals">
		<!-- <div>{{ proposalsStatus }}</div> -->
		<div v-if="isProposalOpen" class="option-proposals__add-proposal">
			<OptionsDateAdd v-if="pollType === 'datePoll'"
				:caption="t('polls', 'Propose a date')"
				class="add-date-proposal"
				show-caption
				primary />
			<OptionsTextAdd v-if="pollType === 'textIndPoll'" :placeholder="t('polls', 'Propose an option')" class="add-text-proposal" />
			<OptionsTextAdd v-if="pollType === 'textRankPoll'" :placeholder="t('polls', 'Propose an option')" class="add-text-proposal" />
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionsDateAdd from './OptionsDateAdd.vue'
import OptionsTextAdd from './OptionsTextAdd.vue'

export default {
	name: 'OptionProposals',

	components: {
		OptionsDateAdd,
		OptionsTextAdd,
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			isProposalOpen: 'poll/isProposalOpen',
		}),
	},
}

</script>

<style lang="scss">
	.option-proposals {
		align-self: center;
		display: flex;
		flex-direction: column;
	}

	.option-proposals__add-proposal {
		padding: 8px 0;
		.add-date-proposal {
			min-width: 85px;
		}
	}
</style>
