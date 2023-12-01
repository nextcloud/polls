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
	<div>
		<CardDiv v-if="hasVotes" type="warning">
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
			<ConfigAllowMayBe @change="writePoll" />
			<ConfigUseNo @change="writePoll" />
			<ConfigAnonymous @change="writePoll" />

			<ConfigVoteLimit @change="writePoll" />
			<ConfigOptionLimit @change="writePoll" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Poll closing status')">
			<template #icon>
				<LockedIcon v-if="closed" />
				<UnlockedIcon v-else />
			</template>
			<ConfigClosing @change="writePoll" />
			<ConfigAutoReminder v-if="pollType === 'datePoll' || hasEpiration"
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
import { mapGetters, mapState } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import moment from '@nextcloud/moment'
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
		NcButton,
		CardDiv,
	},

	mixins: [writePoll],

	computed: {
		...mapState({
			isPollArchived: (state) => state.poll.deleted,
			pollType: (state) => state.poll.type,
			pollId: (state) => state.poll.id,
			hasEpiration: (state) => state.poll.expire,
			isOwner: (state) => state.poll.acl.isOwner,
			showResults: (state) => state.poll.showResults,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			hasVotes: 'votes/hasVotes',
		}),
	},

	methods: {
		toggleArchive() {
			if (this.isPollArchived) {
				this.$store.commit('poll/setProperty', { deleted: 0 })
			} else {
				this.$store.commit('poll/setProperty', { deleted: moment.utc().unix() })
			}
			this.writePoll() // from mixin
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
}
</script>

<style lang="scss">
.delete-area {
	display: flex;
	gap: 8px;
	justify-content: space-between;
}
</style>
