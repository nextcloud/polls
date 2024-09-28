<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import { NcButton } from '@nextcloud/vue'
	import { emit } from '@nextcloud/event-bus'
	import ListViewIcon from 'vue-material-design-icons/ViewListOutline.vue' // view-sequential-outline
	import TableViewIcon from 'vue-material-design-icons/Table.vue' // view-comfy-outline
	import { t } from '@nextcloud/l10n'
	import { usePollStore, PollType, } from '../../../stores/poll.ts'
	import { usePreferencesStore, ViewMode } from '../../../stores/preferences.ts'
	import { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'

	const pollStore = usePollStore()
	const preferencesStore = usePreferencesStore()

	const caption = computed(() => {
		if (pollStore.viewMode === ViewMode.TableView) {
			return t('polls', 'Switch to list view')
		}
		return t('polls', 'Switch to table view')
	})

	/**
	 *
	 */
	function clickAction(): void {
		emit('polls:transitions:off', 500)
		changeView()
	}

	/**
	 *
	 */
	function changeView(): void {
		if (pollStore.type === PollType.Date) {
			preferencesStore.setViewDatePoll(pollStore.viewMode === ViewMode.TableView ? ViewMode.ListView : ViewMode.TableView)
		} else if (pollStore.type === PollType.Text) {
			preferencesStore.setViewTextPoll(pollStore.viewMode === ViewMode.TableView ? ViewMode.ListView : ViewMode.TableView)
		}
	}
</script>

<template>
	<div class="action change-view">
		<NcButton :type="ButtonType.Tertiary"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<ListViewIcon v-if="pollStore.viewMode === ViewMode.TableView" />
				<TableViewIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

