<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent>
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
			<ComboTable v-show="comboStore.polls.length" />

			<NcEmptyContent v-if="!comboStore.polls.length"
				:name="t('polls', 'No polls selected')"
				:description="t('polls', 'Select polls by clicking on them in the right sidebar!')">
				<template #icon>
					<PollsAppIcon />
				</template>
			</NcEmptyContent>
		</div>

		<LoadingOverlay v-if="isLoading" />
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent } from '@nextcloud/vue'
import ComboTable from '../components/Combo/ComboTable.vue'
import { ActionToggleSidebar } from '../components/Actions/index.js'
import { HeaderBar } from '../components/Base/index.js'
import { PollsAppIcon } from '../components/AppIcons/index.js'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import { t } from '@nextcloud/l10n'
import { mapStores } from 'pinia'
import { useComboStore } from '../stores/combo.ts'

export default {
	name: 'Combo',

	components: {
		ActionToggleSidebar,
		NcAppContent,
		ComboTable,
		NcEmptyContent,
		HeaderBar,
		PollsAppIcon,
		LoadingOverlay,
	},

	data() {
		return {
			isLoading: false,
			title: t('polls', 'Combined polls'),
			description: t('polls', 'Combine multiple date polls in a single view'),
		}
	},

	computed: {
		...mapStores(useComboStore),

		/* eslint-disable-next-line vue/no-unused-properties */
		windowTitle() {
			return `${t('polls', 'Polls')} - ${this.title}`
		},
	},

	watch: {
		'comboStore.pollCombo'() {
			this.settings.setPollCombo({ pollCombo: this.comboStore.pollCombo })
		},
		'settings.user.pollCombo'() {
			this.comboStore.verifyPollsFromSettings()
		},
	},

	created() {
		this.comboStore.verifyPollsFromSettings()
	},

	methods: {
		t,
	},

}

</script>
