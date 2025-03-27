<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'

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

import { ConfigBox, CardDiv } from '../Base/index.ts'
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

import { usePollStore, PollType, ShowResults } from '../../stores/poll.ts'
import { useVotesStore } from '../../stores/votes.ts'

const pollStore = usePollStore()
const votesStore = useVotesStore()

/**
 *
 */
function toggleArchive() {
	try {
		pollStore.toggleArchive({ pollId: pollStore.id })
	} catch {
		showError(
			t('polls', 'Error {action} poll.', {
				action: pollStore.status.isDeleted ? 'restoring' : 'archiving',
			}),
		)
	}
}

/**
 *
 */
function deletePoll() {
	if (!pollStore.status.isDeleted) return
	try {
		pollStore.delete({ pollId: pollStore.id })
	} catch {
		showError(t('polls', 'Error deleting poll.'))
	}
}
</script>

<template>
	<div>
		<CardDiv v-if="votesStore.hasVotes" type="warning">
			{{
				t(
					'polls',
					'Please be careful when changing options, because it can affect existing votes in an unwanted manner.',
				)
			}}
		</CardDiv>

		<CardDiv v-if="!pollStore.currentUserStatus.isOwner" type="success">
			{{ t('polls', 'As an admin you may edit this poll') }}
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
				v-if="
					pollStore.type === PollType.Date ||
					pollStore.configuration.expire
				"
				@change="pollStore.write" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Result display')">
			<template #icon>
				<ShowResultsIcon
					v-if="
						pollStore.configuration.showResults === ShowResults.Always
					" />
				<HideResultsUntilClosedIcon
					v-if="
						pollStore.configuration.showResults === ShowResults.Closed
					" />
				<ShowResultsNeverIcon
					v-if="
						pollStore.configuration.showResults === ShowResults.Never
					" />
			</template>
			<ConfigShowResults @change="pollStore.write" />
		</ConfigBox>

		<div class="delete-area">
			<NcButton @click="toggleArchive()">
				<template #icon>
					<RestorePollIcon v-if="pollStore.status.isDeleted" />
					<ArchivePollIcon v-else />
				</template>
				<template #default>
					{{
						pollStore.status.isDeleted
							? t('polls', 'Restore poll')
							: t('polls', 'Archive poll')
					}}
				</template>
			</NcButton>

			<NcButton
				v-if="pollStore.status.isDeleted"
				:type="ButtonType.Error"
				@click="deletePoll()">
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

<style lang="scss">
.delete-area {
	display: flex;
	gap: 8px;
	justify-content: space-between;
}
</style>
