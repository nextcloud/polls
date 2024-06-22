<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action change-view">
		<NcButton type="tertiary"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<ListViewIcon v-if="pollStore.viewMode === 'table-view'" />
				<TableViewIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcButton } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import ListViewIcon from 'vue-material-design-icons/ViewListOutline.vue' // view-sequential-outline
import TableViewIcon from 'vue-material-design-icons/Table.vue' // view-comfy-outline
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'
import { usePreferencesStore } from '../../../stores/preferences.ts'

export default {
	name: 'ActionChangeView',

	components: {
		ListViewIcon,
		TableViewIcon,
		NcButton,
	},

	computed: {
		...mapStores(usePollStore, usePreferencesStore),

		caption() {
			if (this.pollStore.viewMode === 'table-view') {
				return t('polls', 'Switch to list view')
			}
			return t('polls', 'Switch to table view')
		},

	},

	methods: {
		changeView() {
			if (this.pollStore.type === 'datePoll') {
				this.preferencesStore.setViewDatePoll(this.settings.manualViewDatePoll ? '' : this.pollStore.getNextViewMode)
			} else if (this.pollStore.type === 'textPoll') {
				this.preferencesStore.setViewTextPoll(this.settings.manualViewTextPoll ? '' : this.pollStore.getNextViewMode)
			}
		},

		clickAction() {
			emit('polls:transitions:off', 500)
			this.changeView()
		},
	},
}
</script>
