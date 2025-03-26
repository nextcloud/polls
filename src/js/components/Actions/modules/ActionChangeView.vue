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
			manualViewTextIndPoll: (state) => state.settings.manualViewTextIndPoll,
			manualViewTextRankPoll: (state) => state.settings.manualViewTextRankPoll,
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
			} else if (this.pollType === 'textIndPoll') {
				this.$store.commit('settings/setViewTextIndPoll', this.manualViewTextIndPoll ? '' : this.getNextViewMode)
			} else if (this.pollType === 'textRankPoll') {
				this.$store.commit('settings/setViewTextRankPoll', this.manualViewTextRankPoll ? '' : this.getNextViewMode)
			}
		},

		clickAction() {
			emit('polls:transitions:off', 500)
			this.changeView()
		},
	},
}
</script>
