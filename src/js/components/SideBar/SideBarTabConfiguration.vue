<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<CardDiv v-if="hasVotes || hasVotesStandard" type="warning">
			{{ t('polls', 'Please be careful when changing options, because it can affect existing votes in an unwanted manner.') }}
		</CardDiv>

		<CardDiv v-if="!isOwner" type="success">
			{{ t('polls', 'As an admin you may edit this poll') }}
		</CardDiv>

		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<ConfigTitle @change="writePoll" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Description')">
			<template #icon>
				<DescriptionIcon />
			</template>
			<ConfigDescription @change="writePoll" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll configurations')">
			<template #icon>
				<PollConfigIcon />
			</template>
			<ConfigAllowComment @change="writePoll" />
			<template v-if="pollType!=='textRankPoll'">
		   	<ConfigAllowMayBe @change="writePoll" />
			<ConfigUseNo @change="writePoll" />
			</template>
			
			<ConfigAnonymous @change="writePoll" />

			<ConfigVoteLimit @change="writePoll" />
			<ConfigOptionLimit @change="writePoll" />
		</ConfigBox>

		<ConfigBox v-if="pollType === 'textRankPoll'" :name="t('polls', 'Rank Options')">
			<template #icon>
				<PollConfigIcon />
			</template>
		<ConfigRankOptions :chosen-rank.sync="pollConfiguration.chosenRank" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll closing status')">
			<template #icon>
				<LockedIcon v-if="isPollClosed" />
				<UnlockedIcon v-else />
			</template>
			<ConfigClosing @change="writePoll" />
			<ConfigAutoReminder v-if="pollType === 'datePoll' || hasExpiration"
				@change="writePoll" />
		</ConfigBox>
		<ConfigBox :name="t('polls', 'Result display')">
			<template #icon>
				<ShowResultsIcon v-if="showResults === 'always'" />
				<HideResultsUntilClosedIcon v-if="showResults === 'closed'" />
				<ShowResultsNeverIcon v-if="showResults === 'never'" />
			</template>
			<ConfigShowResults @change="writePoll" />
		</ConfigBox>

		<div class="delete-area">
			<NcButton @click="toggleArchive()">
				<template #icon>
					<RestorePollIcon v-if="isPollArchived" />
					<ArchivePollIcon v-else />
				</template>
				<template #default>
					{{ isPollArchived ? t('polls', 'Restore poll') : t('polls', 'Archive poll') }}
				</template>
			</NcButton>

			<NcButton v-if="isPollArchived" type="error" @click="deletePoll()">
				<template #icon>
					<DeletePollIcon />
				</template>
				<template #default>
					{{ t('polls', 'Delete poll') }}
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import { mapGetters, mapState, mapActions } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import { ConfigBox, CardDiv } from '../Base/index.js'
import ConfigAllowComment from '../Configuration/ConfigAllowComment.vue'
import ConfigAllowMayBe from '../Configuration/ConfigAllowMayBe.vue'
import ConfigAnonymous from '../Configuration/ConfigAnonymous.vue'
import ConfigAutoReminder from '../Configuration/ConfigAutoReminder.vue'
import ConfigClosing from '../Configuration/ConfigClosing.vue'
import ConfigDescription from '../Configuration/ConfigDescription.vue'
import ConfigOptionLimit from '../Configuration/ConfigOptionLimit.vue'
import ConfigShowResults from '../Configuration/ConfigShowResults.vue'
import ConfigTitle from '../Configuration/ConfigTitle.vue'
import ConfigUseNo from '../Configuration/ConfigUseNo.vue'
import ConfigVoteLimit from '../Configuration/ConfigVoteLimit.vue'
import ConfigRankOptions from '../Configuration/ConfigRankOptions.vue'

import { writePoll } from '../../mixins/writePoll.js'

import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import DescriptionIcon from 'vue-material-design-icons/TextBox.vue'
import PollConfigIcon from 'vue-material-design-icons/Wrench.vue'
import LockedIcon from 'vue-material-design-icons/Lock.vue'
import UnlockedIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import ShowResultsIcon from 'vue-material-design-icons/Monitor.vue'
import HideResultsUntilClosedIcon from 'vue-material-design-icons/MonitorLock.vue'
import ShowResultsNeverIcon from 'vue-material-design-icons/MonitorOff.vue'
import RestorePollIcon from 'vue-material-design-icons/Recycle.vue'
import ArchivePollIcon from 'vue-material-design-icons/Archive.vue'


import { t } from '@nextcloud/l10n';

export default {
	name: 'SideBarTabConfiguration',
	

	components: {
		ArchivePollIcon,
		DeletePollIcon,
		DescriptionIcon,
		LockedIcon,
		HideResultsUntilClosedIcon,
		PollConfigIcon,
		RestorePollIcon,
		ShowResultsIcon,
		ShowResultsNeverIcon,
		SpeakerIcon,
		UnlockedIcon,
		ConfigBox,
		ConfigAllowComment,
		ConfigAllowMayBe,
		ConfigAnonymous,
		ConfigAutoReminder,
		ConfigClosing,
		ConfigDescription,
		ConfigOptionLimit,
		ConfigShowResults,
		ConfigTitle,
		ConfigUseNo,
		ConfigVoteLimit,
		ConfigRankOptions,
		NcButton,
		CardDiv,
	},

	mixins: [writePoll],

	computed: {

		...mapState({
			pollId: (state) => state.poll.id,
			pollType: (state) => state.poll.type,
			isPollArchived: (state) => state.poll.status.deleted,
			hasExpiration: (state) => state.poll.configuration.expire,
			showResults: (state) => state.poll.configuration.showResults,
			isOwner: (state) => state.poll.currentUserStatus.isOwner,
			pollConfiguration: (state) => state.poll.configuration,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
			hasVotesStandard: 'votesStandard/hasVotes',
			hasVotes: 'votes/hasVotes',
		}),
	},

	methods: {
		...mapActions({
		      	 toggleArchive: 'poll/toggleArchive',
     			 deletePoll: 'poll/delete',
    		}),

		async toggleArchive() {
			try {
				await this.$store.dispatch('poll/toggleArchive', { pollId: this.pollId })
			} catch {
				showError(t('polls', 'Error {action} poll.', { action: this.isPollArchived ? 'restoring' : 'archiving' }))
			}
			this.writePoll()
		},

		async deletePoll() {
			if (!this.isPollArchived) return
			try {
				await this.$store.dispatch('poll/delete', { pollId: this.pollId })
				this.$router.push({ name: 'list', params: { type: 'relevant' } })
			} catch {
				showError(t('polls', 'Error deleting poll.'))
			}
		},
   	},
};
</script>

<style lang="scss">
.delete-area {
	display: flex;
	gap: 8px;
	justify-content: space-between;
}
</style>
