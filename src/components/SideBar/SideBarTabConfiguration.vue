<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import SpeakerIcon from 'vue-material-design-icons/BullhornOutline.vue'
import DeletePollIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import DescriptionIcon from 'vue-material-design-icons/TextBoxOutline.vue'
import PollConfigIcon from 'vue-material-design-icons/WrenchOutline.vue'
import LockedIcon from 'vue-material-design-icons/LockOutline.vue'
import UnlockedIcon from 'vue-material-design-icons/LockOpenVariantOutline.vue'
import ShowResultsIcon from 'vue-material-design-icons/Monitor.vue'
import HideResultsUntilClosedIcon from 'vue-material-design-icons/MonitorLock.vue'
import UserPreferenceIcon from 'vue-material-design-icons/AccountCogOutline.vue'
import ShowResultsNeverIcon from 'vue-material-design-icons/MonitorOff.vue'
import ListViewIcon from 'vue-material-design-icons/ViewListOutline.vue'
import TimezoneIcon from 'vue-material-design-icons/MapClockOutline.vue'
import TableViewIcon from 'vue-material-design-icons/Table.vue'

import CardDiv from '../Base/modules/CardDiv.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'
import ConfigAllowMayBe from '../Configuration/ConfigAllowMayBe.vue'
import ConfigAnonymous from '../Configuration/ConfigAnonymous.vue'
import ConfigAutoReminder from '../Configuration/ConfigAutoReminder.vue'
import ConfigClosing from '../Configuration/ConfigClosing.vue'
import ConfigDangerArea from '../Configuration/ConfigDangerArea.vue'
import ConfigDescription from '../Configuration/ConfigDescription.vue'
import ConfigForceViewMode from '../Configuration/ConfigForceViewMode.vue'
import ConfigOptionLimit from '../Configuration/ConfigOptionLimit.vue'
import ConfigShowResults from '../Configuration/ConfigShowResults.vue'
import ConfigTimezone from '../Configuration/ConfigTimezone.vue'
import ConfigTitle from '../Configuration/ConfigTitle.vue'
import ConfigUseNo from '../Configuration/ConfigUseNo.vue'
import ConfigVoteLimit from '../Configuration/ConfigVoteLimit.vue'

import { usePollStore } from '../../stores/poll'
import { useVotesStore } from '../../stores/votes'

const pollStore = usePollStore()
const votesStore = useVotesStore()
</script>

<template>
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

	<ConfigBox :name="t('polls', 'Poll configuration')">
		<template #icon>
			<PollConfigIcon />
		</template>
		<ConfigAllowMayBe @change="pollStore.write" />
		<ConfigUseNo @change="pollStore.write" />
		<ConfigAnonymous @change="pollStore.write" />

		<ConfigVoteLimit @change="pollStore.write" />
		<ConfigOptionLimit @change="pollStore.write" />
	</ConfigBox>

	<ConfigBox :name="t('polls', 'Poll closing status')">
		<template #icon>
			<LockedIcon v-if="pollStore.isClosed" />
			<UnlockedIcon v-else />
		</template>
		<ConfigClosing @change="pollStore.write" />
		<ConfigAutoReminder
			v-if="pollStore.type === 'datePoll' || pollStore.configuration.expire"
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

	<ConfigBox :name="t('polls', 'Forced display mode')">
		<template #icon>
			<UserPreferenceIcon
				v-if="pollStore.configuration.forcedDisplayMode === 'user-pref'" />
			<TableViewIcon
				v-if="pollStore.configuration.forcedDisplayMode === 'table-view'" />
			<ListViewIcon
				v-if="pollStore.configuration.forcedDisplayMode === 'list-view'" />
		</template>
		<ConfigForceViewMode @change="pollStore.write" />
	</ConfigBox>

	<ConfigBox :name="t('polls', 'Set default timezone for this poll')">
		<template #icon>
			<TimezoneIcon />
		</template>
		<ConfigTimezone @change="pollStore.write" />
	</ConfigBox>

	<ConfigBox :name="t('polls', 'Deletion and owner')">
		<template #icon>
			<DeletePollIcon />
		</template>
		<ConfigDangerArea />
	</ConfigBox>
</template>
