<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action change-view">
		<NcButton variant="tertiary"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<ListViewIcon v-if="viewMode === 'table-view'" />
				<TableViewIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'
import ListViewIcon from 'vue-material-design-icons/ViewListOutline.vue' // view-sequential-outline
import TableViewIcon from 'vue-material-design-icons/Table.vue' // view-comfy-outline

export default {
	name: 'ActionChangeView',

	components: {
		ListViewIcon,
		TableViewIcon,
		NcButton,
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
			manualViewDatePoll: (state) => state.settings.manualViewDatePoll,
			manualViewTextPoll: (state) => state.settings.manualViewTextPoll,
		}),

		...mapGetters({
			viewMode: 'poll/viewMode',
			getNextViewMode: 'poll/getNextViewMode',
		}),

		caption() {
			if (this.viewMode === 'table-view') {
				return t('polls', 'Switch to list view')
			}
			return t('polls', 'Switch to table view')
		},

	},

	methods: {
		changeView() {
			if (this.pollType === 'datePoll') {
				this.$store.commit('settings/setViewDatePoll', this.manualViewDatePoll ? '' : this.getNextViewMode)
			} else if (this.pollType === 'textPoll') {
				this.$store.commit('settings/setViewTextPoll', this.manualViewTextPoll ? '' : this.getNextViewMode)
			}
		},

		clickAction() {
			emit('polls:transitions:off', 500)
			this.changeView()
		},
	},
}
</script>
