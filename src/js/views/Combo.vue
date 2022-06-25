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
	<AppContent>
		<HeaderBar class="area__header">
			<template #title>
				{{ title }}
			</template>
			<template #right>
				<div class="poll-header-buttons">
					<ActionToggleSidebar />
				</div>
			</template>
			{{ description }}
		</HeaderBar>

		<div class="area__main">
			<ComboTable v-show="polls.length" />

			<EmptyContent v-if="!polls.length">
				<template #icon>
					<PollsAppIcon />
				</template>
				<template #desc>
					{{ t('polls', 'Select polls by clicking on them in the right sidebar!') }}
				</template>
				{{ t('polls', 'No polls selected') }}
			</EmptyContent>
		</div>

		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { mapActions, mapGetters, mapState } from 'vuex'
import { AppContent, EmptyContent } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import ComboTable from '../components/Combo/ComboTable.vue'
import ActionToggleSidebar from '../components/Actions/ActionToggleSidebar.vue'
import HeaderBar from '../components/Base/HeaderBar.vue'
import PollsAppIcon from '../components/AppIcons/PollsAppIcon.vue'

export default {
	name: 'Combo',

	components: {
		ActionToggleSidebar,
		AppContent,
		ComboTable,
		EmptyContent,
		HeaderBar,
		PollsAppIcon,
		LoadingOverlay: () => import('../components/Base/LoadingOverlay.vue'),
	},

	data() {
		return {
			isLoading: false,
			title: t('polls', 'Combined polls'),
			description: t('polls', 'Combine multiple date polls in a single view'),
		}
	},

	computed: {
		...mapGetters({
			pollCombo: 'combo/pollCombo',
		}),
		...mapState({
			polls: (state) => state.combo.polls,
			savePollCombo: (state) => state.settings.user.pollCombo,
		}),

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.title}`
		},
	},

	watch: {
		pollCombo() {
			this.setPollCombo({ pollCombo: this.pollCombo })
		},
		savePollCombo() {
			this.verifyPolls()
		},
	},

	created() {
		// simulate @media:prefers-color-scheme until it is supported for logged in users
		// This simulates the theme--dark
		// TODO: remove, when completely supported by core
		if (!window.matchMedia) {
			return true
		}
		emit('polls:sidebar:toggle', { open: (window.innerWidth > 920) })
		this.verifyPolls()
	},

	methods: {
		...mapActions({
			setPollCombo: 'settings/setPollCombo',
			verifyPolls: 'combo/verifyPollsFromSettings',
		}),
	},

}

</script>
