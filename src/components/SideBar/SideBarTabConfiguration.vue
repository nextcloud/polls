<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import DeletePollIcon from 'vue-material-design-icons/Delete.vue'
import DescriptionIcon from 'vue-material-design-icons/TextBox.vue'
import PollConfigIcon from 'vue-material-design-icons/Wrench.vue'
import LockedIcon from 'vue-material-design-icons/Lock.vue'
import UnlockedIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import ShowResultsIcon from 'vue-material-design-icons/Monitor.vue'
import HideResultsUntilClosedIcon from 'vue-material-design-icons/MonitorLock.vue'
import ShowResultsNeverIcon from 'vue-material-design-icons/MonitorOff.vue'

import CardDiv from '../Base/modules/CardDiv.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'
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

import { usePollStore } from '../../stores/poll'
import { useVotesStore } from '../../stores/votes'
import ConfigDangerArea from '../Configuration/ConfigDangerArea.vue'
import ConfigForceConfidentialComments from '../Configuration/ConfigForceConfidentialComments.vue'

const pollStore = usePollStore()
const votesStore = useVotesStore()
</script>

<template>
	<div>
		<CardDiv v-if="!pollStore.currentUserStatus.isOwner" type="success">
			{{ t('polls', 'You have been granted administrative rights.') }}
		</CardDiv>

		<CardDiv v-if="votesStore.hasVotes" type="warning">
			{{ t('polls', 'Changes may affect existing votes.') }}
		</CardDiv>

		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<ConfigTitle @change="pollStore.write" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Description')">
			<template #icon>
				<DescriptionIcon />
			</template>
			<ConfigDescription @change="pollStore.write" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll configurations')">
			<template #icon>
				<PollConfigIcon />
			</template>
			<ConfigAllowComment @change="pollStore.write" />
			<ConfigForceConfidentialComments
				v-if="pollStore.configuration.allowComment"
				@change="pollStore.write" />
			<template v-if="pollStore.votingVariant === 'generic'">
				<ConfigAllowMayBe @change="pollStore.write" />
				<ConfigUseNo @change="pollStore.write" />
				<ConfigAnonymous @change="pollStore.write" />
			</template>
			<ConfigVoteLimit @change="pollStore.write" />
			<ConfigOptionLimit @change="pollStore.write" />
		</ConfigBox>

		<ConfigBox
			v-if="pollStore.votingVariant === 'generic'"
			:name="t('polls', 'Generic Options')">
			<template #icon>
				<PollConfigIcon />
			</template>
			<ConfigRankOptions />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll closing status')">
			<template #icon>
				<LockedIcon v-if="pollStore.isClosed" />
				<UnlockedIcon v-else />
			</template>
			<ConfigClosing @change="pollStore.write" />
			<ConfigAutoReminder
				v-if="
					pollStore.type === 'datePoll' || pollStore.configuration.expire
				"
				@change="pollStore.write" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Result display')">
			<template #icon>
				<ShowResultsIcon
					v-if="pollStore.configuration.showResults === 'always'" />
				<HideResultsUntilClosedIcon
					v-if="pollStore.configuration.showResults === 'closed'" />
				<ShowResultsNeverIcon
					v-if="pollStore.configuration.showResults === 'never'" />
			</template>
			<ConfigShowResults @change="pollStore.write" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Deletion and owner')">
			<template #icon>
				<DeletePollIcon />
			</template>
			<ConfigDangerArea />
		</ConfigBox>
	</div>
</template>
