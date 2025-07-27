<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import ComboTable from '../components/Combo/ComboTable.vue'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import { ActionToggleSidebar } from '../components/Actions'
import HeaderBar from '../components/Base/modules/HeaderBar.vue'
import { useComboStore } from '../stores/combo.ts'
import PollsAppIcon from '../components/AppIcons/PollsAppIcon.vue'

const comboStore = useComboStore()
const isLoading = ref(false)
const title = t('polls', 'Combined polls')
const description = t('polls', 'Combine multiple date polls in a single view')

onMounted(() => {
	comboStore.verifyPollsFromSettings()
})
</script>

<template>
	<NcAppContent>
		<HeaderBar>
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

			<NcEmptyContent
				v-if="!comboStore.polls.length"
				:name="t('polls', 'No polls selected')"
				:description="
					t(
						'polls',
						'Select polls by clicking on them in the right sidebar!',
					)
				">
				<template #icon>
					<PollsAppIcon />
				</template>
			</NcEmptyContent>
		</div>

		<LoadingOverlay :name="t('polls', 'Loading …')" :show="isLoading" />
	</NcAppContent>
</template>
